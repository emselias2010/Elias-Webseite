/**
 * Admin: edit texts and Strava data. Forms are built from a small description
 * of each section; the server checks every value again before it is saved.
 */
import { el, requestJson } from "./util.js";

let uid = 0;
const nextId = () => `ac-${++uid}`;

/* ------------------------------ Field types ------------------------------ */

const text = (label, extra = {}) => ({ type: "text", label, ...extra });
const area = (label, extra = {}) => ({ type: "textarea", label, ...extra });
const number = (label, extra = {}) => ({ type: "number", label, ...extra });
const select = (label, options, extra = {}) => ({ type: "select", label, options, ...extra });
const object = (fields, extra = {}) => ({ type: "object", fields, ...extra });
const list = (label, item, extra = {}) => ({ type: "list", label, item, ...extra });

function emptyOf(spec) {
  switch (spec.type) {
    case "object":
      return Object.fromEntries(spec.fields.map(([key, field]) => [key, emptyOf(field)]));
    case "list":
      return [];
    case "number":
      return 0;
    case "select":
      return typeof spec.options === "function" ? spec.options()[0] : spec.options[0];
    case "hidden":
      return "";
    default:
      return "";
  }
}

/** Builds the form for a spec and a value. Returns { node, get }. */
function build(spec, value) {
  switch (spec.type) {
    case "text":
    case "number": {
      const id = nextId();
      const wrap = el("div", "admin-field");
      const input = el("input", "field");
      input.id = id;
      input.type = spec.type === "number" ? "number" : "text";
      if (spec.type === "number") input.step = "any";
      if (spec.max) input.maxLength = spec.max;
      if (spec.placeholder) input.placeholder = spec.placeholder;
      input.value = value ?? "";
      if (spec.label) {
        const label = el("label", "field-label", spec.label);
        label.htmlFor = id;
        wrap.append(label);
      }
      wrap.append(input);
      if (spec.hint) wrap.append(el("p", "small muted mt-xs", spec.hint));
      const check = () => (spec.type === "number" ? checkNumber(spec, input) : checkText(spec, input));
      return { node: wrap, get: () => (spec.type === "number" ? Number(input.value || 0) : input.value), check };
    }
    case "textarea": {
      const id = nextId();
      const wrap = el("div", "admin-field admin-field--wide");
      const input = el("textarea", "field");
      input.id = id;
      input.rows = spec.rows ?? 3;
      if (spec.max) input.maxLength = spec.max;
      input.value = value ?? "";
      if (spec.label) {
        const label = el("label", "field-label", spec.label);
        label.htmlFor = id;
        wrap.append(label);
      }
      wrap.append(input);
      if (spec.hint) wrap.append(el("p", "small muted mt-xs", spec.hint));
      return { node: wrap, get: () => input.value, check: () => checkText(spec, input) };
    }
    case "select": {
      const id = nextId();
      const wrap = el("div", "admin-field");
      const label = el("label", "field-label", spec.label);
      label.htmlFor = id;
      const input = el("select", "field");
      input.id = id;
      const options = typeof spec.options === "function" ? spec.options() : spec.options;
      for (const option of options) {
        const node = el("option", "", spec.optionLabel ? spec.optionLabel(option) : String(option));
        node.value = String(option);
        input.append(node);
      }
      input.value = String(value ?? options[0]);
      wrap.append(label, input);
      const isNumber = typeof options[0] === "number";
      return {
        node: wrap,
        get: () => (isNumber ? Number(input.value) : input.value),
        check: () => (options.map(String).includes(input.value) ? null : { message: "Please choose a valid option.", node: input }),
      };
    }
    case "hidden":
      return { node: document.createDocumentFragment(), get: () => value, check: () => null };
    case "object": {
      const wrap = el("div", spec.className ?? "admin-grid");
      if (spec.label) wrap.append(el("p", "admin-group-label", spec.label));
      const parts = spec.fields.map(([key, field]) => {
        const part = build(field, value?.[key]);
        wrap.append(part.node);
        return [key, part];
      });
      return {
        node: wrap,
        get: () => Object.fromEntries(parts.map(([key, part]) => [key, part.get()])),
        check: () => parts.reduce((problem, [, part]) => problem ?? part.check(), null),
      };
    }
    case "list":
      return buildList(spec, value ?? []);
  }
  throw new Error(`Unknown field type ${spec.type}`);
}

function buildList(spec, values) {
  const wrap = el("div", "admin-list admin-field--wide");
  if (spec.label) wrap.append(el("p", "admin-group-label", spec.label));
  if (spec.hint) wrap.append(el("p", "small muted", spec.hint));
  const items = el("ol", spec.item.type === "object" ? "admin-list__items" : "admin-list__items admin-list__items--compact");
  wrap.append(items);
  const entries = [];
  const simple = spec.item.type !== "object";

  const refresh = () => {
    entries.forEach((entry, index) => {
      entry.up.disabled = index === 0;
      entry.down.disabled = index === entries.length - 1;
      if (entry.title) entry.title.textContent = spec.itemTitle ? spec.itemTitle(entry.part.get(), index) : `#${index + 1}`;
    });
    if (add) add.disabled = spec.max !== undefined && entries.length >= spec.max;
  };

  const iconButton = (label, symbol, onClick) => {
    const button = el("button", "admin-icon-btn", symbol);
    button.type = "button";
    button.setAttribute("aria-label", label);
    button.title = label;
    button.addEventListener("click", onClick);
    return button;
  };

  const addEntry = (value, focus = false) => {
    const li = el("li", simple ? "admin-list__row" : "admin-item");
    const part = build(spec.item, value);
    const entry = { li, part };
    entry.up = iconButton("Move up", "↑", () => move(entry, -1));
    entry.down = iconButton("Move down", "↓", () => move(entry, 1));
    const remove = iconButton("Remove", "✕", () => {
      if (spec.min !== undefined && entries.length <= spec.min) return;
      entries.splice(entries.indexOf(entry), 1);
      li.remove();
      refresh();
    });
    remove.classList.add("admin-icon-btn--danger");
    const tools = el("div", "admin-item__tools");
    tools.append(entry.up, entry.down, remove);
    if (simple) {
      li.append(part.node, tools);
    } else {
      const head = el("div", "admin-item__head");
      entry.title = el("p", "admin-item__title");
      head.append(entry.title, tools);
      li.append(head, part.node);
      li.addEventListener("input", () => refresh());
    }
    entries.push(entry);
    items.append(li);
    if (focus) li.querySelector("input, textarea, select")?.focus();
  };

  const move = (entry, step) => {
    const index = entries.indexOf(entry);
    const target = index + step;
    if (target < 0 || target >= entries.length) return;
    entries.splice(index, 1);
    entries.splice(target, 0, entry);
    for (const item of entries) items.append(item.li);
    refresh();
    (step < 0 ? entry.up : entry.down).focus();
  };

  let add = null;
  if (!spec.fixed) {
    add = el("button", "btn btn-quiet admin-add", spec.addLabel ?? "Add");
    add.type = "button";
    add.addEventListener("click", () => {
      addEntry(emptyOf(spec.item), true);
      refresh();
    });
    wrap.append(add);
  }
  for (const value of values) addEntry(value);
  if (spec.fixed) wrap.querySelectorAll(".admin-item__tools").forEach((tools) => tools.remove());
  refresh();
  const check = () => {
    if (spec.min !== undefined && entries.length < spec.min) return { message: `${spec.label || "This list"} needs at least ${spec.min} entry.`, node: add ?? wrap };
    if (spec.max !== undefined && entries.length > spec.max) return { message: `${spec.label || "This list"} allows at most ${spec.max} entries.`, node: add ?? wrap };
    return entries.reduce((problem, entry) => problem ?? entry.part.check(), null);
  };
  return { node: wrap, get: () => entries.map((entry) => entry.part.get()), check };
}

/* ------------------------- Checks before saving -------------------------- */
// The server checks everything again; these only give quicker feedback.

const fieldName = (spec, input) => spec.label || input.placeholder || "This field";

function checkText(spec, input) {
  const value = input.value.trim();
  if (spec.required && value === "") return { message: `${fieldName(spec, input)} must not be empty.`, node: input };
  if (spec.max && value.length > spec.max) return { message: `${fieldName(spec, input)} is longer than ${spec.max} characters.`, node: input };
  if (value !== "" && spec.pattern && !spec.pattern.test(value)) return { message: spec.patternMessage ?? `${fieldName(spec, input)} has an invalid format.`, node: input };
  return null;
}

function checkNumber(spec, input) {
  const value = Number(input.value || 0);
  if (!Number.isFinite(value) || value < (spec.min ?? 0) || value > (spec.max ?? 10000000)) {
    return { message: `${fieldName(spec, input)} must be a number between ${spec.min ?? 0} and ${spec.max ?? 10000000}.`, node: input };
  }
  return null;
}

const LINK = /^(https:\/\/\S+|(mailto:)?[^\s@]+@[^\s@]+\.[^\s@]+)$/i;
const ROUTE = /^M\d{1,3}(\.\d)? \d{1,3}(\.\d)?(L\d{1,3}(\.\d)? \d{1,3}(\.\d)?)*$/;

/* -------------------------------- Sections -------------------------------- */

let categories = [];

const SECTIONS = {
  texts: [
    {
      key: "profile",
      title: "Texts on the page",
      hint: "Top of the page and the About section.",
      spec: object(
        [
          ["role", text("Role next to the name", { max: 80 })],
          ["headline", list("Big words at the top", text("", { max: 40, required: true, placeholder: "Word" }), { min: 1, max: 5, addLabel: "Add word" })],
          ["intro", area("Intro under the big words", { max: 600 })],
          ["description", area("Description for Google and link previews", { max: 300, rows: 2 })],
          [
            "about",
            object(
              [
                ["title", text("About: heading", { max: 120, required: true })],
                [
                  "paragraphs",
                  list("About: paragraphs", area("Paragraph", { max: 1500, rows: 4, required: true }), {
                    min: 1,
                    max: 6,
                    addLabel: "Add paragraph",
                    hint: "The first paragraph lights up word by word while scrolling.",
                  }),
                ],
                [
                  "facts",
                  list("About: tiles", object([["label", text("Label", { max: 40 })], ["value", text("Text", { max: 80 })]]), {
                    max: 8,
                    addLabel: "Add tile",
                    itemTitle: (value, index) => value.label || `Tile ${index + 1}`,
                  }),
                ],
                ["interests", list("About: interests", text("", { max: 60 }), { max: 30, addLabel: "Add interest" })],
              ],
              { className: "admin-grid admin-field--wide" },
            ),
          ],
        ],
        { className: "admin-grid" },
      ),
    },
    {
      key: "social",
      title: "Links",
      hint: "Contact tiles. Links must start with https://, for e-mail the address is enough. Empty = “Not linked yet”.",
      spec: list(
        "",
        object([
          ["platform", { type: "hidden" }],
          ["url", text("Link", { max: 300, pattern: LINK, patternMessage: "Links must start with https:// (or be an e-mail address)." })],
          ["handle", text("Name shown (optional)", { max: 80 })],
          ["note", text("Short note", { max: 80 })],
        ]),
        { fixed: true, itemTitle: (value) => value.platform.charAt(0).toUpperCase() + value.platform.slice(1) },
      ),
    },
    {
      key: "sport_intro",
      title: "Sport section",
      hint: "Heading, text and the big quote in the Endurance section.",
      spec: object([
        ["title", text("Heading", { max: 120 })],
        ["quote", text("Quote", { max: 160 })],
        ["strava_as_of", text("Date under “Strava in numbers”", { max: 60, placeholder: "As of 12 October 2026" })],
        ["text", area("Text", { max: 800 })],
      ]),
    },
  ],
  strava: [
    {
      key: "gallery",
      title: "Activities",
      hint: "The cards in the Endurance section, in this order.",
      spec: list(
        "",
        object([
          ["title", text("Title", { max: 80, required: true })],
          ["category", select("Sport", () => categories)],
          ["date", text("Date", { max: 40, placeholder: "October 2026" })],
          ["stat", object([["value", text("Big number", { max: 16, placeholder: "42.2" })], ["unit", text("Unit", { max: 16, placeholder: "km" })]], { className: "admin-grid admin-grid--pair" })],
          ["facts", list("Small facts", text("", { max: 60 }), { max: 6, addLabel: "Add fact" })],
          ["description", area("Description", { max: 400, rows: 2 })],
          ["route", area("Route (optional)", { max: 8000, rows: 2, pattern: ROUTE, patternMessage: "The route has an invalid format.", hint: "The green line on the card. Leave empty for activities without GPS." })],
        ]),
        { max: 40, addLabel: "Add activity", itemTitle: (value, index) => value.title || `Activity ${index + 1}` },
      ),
    },
    {
      key: "strava_stats",
      title: "Strava in numbers",
      hint: "Large numbers that count up while scrolling.",
      spec: list(
        "",
        object([
          ["value", number("Number", { min: 0, max: 10000000 })],
          ["decimals", select("Decimals", [0, 1, 2])],
          ["unit", text("Unit", { max: 12, placeholder: "km" })],
          ["label", text("Label", { max: 40 })],
          ["note", text("Note", { max: 120 })],
        ]),
        { max: 8, addLabel: "Add number", itemTitle: (value, index) => value.label || `Number ${index + 1}` },
      ),
    },
    {
      key: "sport_stats",
      title: "Highlight tiles",
      hint: "The four tiles at the top of the Endurance section.",
      spec: list(
        "",
        object([["label", text("Label", { max: 40 })], ["value", text("Value", { max: 60 })], ["note", text("Note", { max: 120 })]]),
        { max: 8, addLabel: "Add tile", itemTitle: (value, index) => value.label || `Tile ${index + 1}` },
      ),
    },
  ],
};

SECTIONS.ask = [
  {
    key: "knowledge",
    title: "What Ask Elias knows",
    hint: "The assistant answers only from these facts. Everything here can be told to any visitor: no address, phone number, passwords or other private details. The rules of the assistant stay fixed.",
    spec: object(
      [
        [
          "sections",
          list(
            "Topics",
            object([
              ["topic", text("Topic", { max: 80, required: true })],
              ["facts", list("Facts (true today)", area("Fact", { max: 500, rows: 2, required: true }), { max: 30, addLabel: "Add fact" })],
              ["plans", list("Plans and ideas (not done yet)", area("Plan", { max: 300, rows: 2, required: true }), { max: 15, addLabel: "Add plan" })],
            ]),
            { min: 1, max: 30, addLabel: "Add topic", itemTitle: (value, index) => value.topic || `Topic ${index + 1}` },
          ),
        ],
      ],
      { className: "admin-grid admin-field--wide" },
    ),
  },
];

/* --------------------------------- Panel --------------------------------- */

function renderSection(section, entry, getCsrf, onSignedOut) {
  const card = el("section", "tile admin-card mt-m");
  const head = el("div", "admin-card__head");
  const title = el("h2", "heading-3", section.title);
  const badge = el("span", "admin-badge", "Edited");
  head.append(title, badge);
  card.append(head);
  if (section.hint) card.append(el("p", "small muted mt-xs", section.hint));

  const body = el("div", "mt-m");
  card.append(body);
  let form = null;
  const status = el("p", "small mt-s");
  status.setAttribute("role", "status");

  const show = (value, changed) => {
    form = build(section.spec, value);
    body.replaceChildren(form.node);
    badge.hidden = !changed;
    reset.hidden = !changed;
  };

  const save = el("button", "btn", "Save");
  save.type = "button";
  const reset = el("button", "btn btn-quiet", "Reset to original");
  reset.type = "button";
  const row = el("div", "button-row mt-m");
  row.append(save, reset);
  card.append(row, status);

  const send = async (action, payload, button, done) => {
    save.disabled = reset.disabled = true;
    const label = button.textContent;
    button.textContent = action === "content_save" ? "Saving…" : "Resetting…";
    status.className = "small mt-s";
    status.textContent = "";
    const result = await requestJson(`/api/admin.php?action=${action}`, { method: "POST", body: payload, headers: { "X-CSRF-Token": getCsrf() } });
    save.disabled = reset.disabled = false;
    button.textContent = label;
    if (result.status === 401) return onSignedOut();
    if (!result.ok || !result.data?.ok) {
      status.className = "small mt-s admin-error";
      status.textContent = result.data?.error ?? "Could not be saved.";
      return;
    }
    show(result.data.value, result.data.changed);
    status.textContent = done;
  };

  save.addEventListener("click", () => {
    const problem = form.check();
    if (problem) {
      status.className = "small mt-s admin-error";
      status.textContent = problem.message;
      problem.node.focus?.();
      return;
    }
    send("content_save", { key: section.key, value: form.get() }, save, "Saved. The page shows the new version now.");
  });
  reset.addEventListener("click", () => {
    if (!reset.dataset.confirm) {
      reset.dataset.confirm = "1";
      reset.textContent = "Really reset?";
      return;
    }
    delete reset.dataset.confirm;
    reset.textContent = "Reset to original";
    send("content_reset", { key: section.key }, reset, "Reset to the original version.");
  });

  show(entry.value, entry.changed);
  return card;
}

/** Loads the content and fills the "texts" and "strava" panels. */
export async function loadContent(panels, getCsrf, onSignedOut) {
  for (const panel of Object.values(panels)) panel.replaceChildren(el("p", "muted", "Loading…"));
  const result = await requestJson("/api/admin.php?action=content");
  if (result.status === 401) return onSignedOut();
  if (!result.ok || !result.data?.ok) {
    for (const panel of Object.values(panels)) panel.replaceChildren(el("p", "notice notice-error", result.data?.error ?? "Could not be loaded."));
    return;
  }
  categories = result.data.categories;
  for (const [area, sections] of Object.entries(SECTIONS)) {
    panels[area].replaceChildren(...sections.map((section) => renderSection(section, result.data.content[section.key], getCsrf, onSignedOut)));
  }
}
