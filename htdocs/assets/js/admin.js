/** Admin: comments (approve, hide, delete), texts and Strava data. */
import { loadContent } from "./admin-content.js";
import { el, formatDate, requestJson } from "./util.js";

const root = document.querySelector("[data-admin]");
const checking = root.querySelector("[data-admin-checking]");
const off = root.querySelector("[data-admin-off]");
const loginForm = root.querySelector("[data-admin-login]");
const panel = root.querySelector("[data-admin-panel]");
const list = root.querySelector("[data-admin-list]");
const stateText = root.querySelector("[data-admin-state]");
const errorBox = root.querySelector("[data-admin-error]");
const tabs = Array.from(root.querySelectorAll("[data-status]"));
const areaTabs = Array.from(root.querySelectorAll("[data-area]"));
const areaPanels = Object.fromEntries(Array.from(root.querySelectorAll("[data-area-panel]")).map((panel) => [panel.dataset.areaPanel, panel]));
let contentLoaded = false;

let csrf = "";

/** Number of reported comments on the "Reported" filter and the "Comments" tab. */
function setReportedCount(count) {
  for (const node of root.querySelectorAll("[data-reported-count]")) {
    node.textContent = count > 0 ? String(count) : "";
    node.hidden = count === 0;
  }
}
let status = "published";

const showError = (message) => {
  errorBox.hidden = !message;
  errorBox.textContent = message || "";
};

function showView(view) {
  checking.hidden = true;
  off.hidden = view !== "off";
  loginForm.hidden = view !== "login";
  panel.hidden = view !== "panel";
  if (view === "login") loginForm.querySelector("input").focus();
}

async function change(comment, action, body, item, buttons) {
  for (const button of buttons) button.disabled = true;
  showError("");
  const result = await requestJson(`/api/admin.php?action=${action}`, { method: "POST", body, headers: { "X-CSRF-Token": csrf } });
  if (result.status === 401) return showView("login");
  if (!result.ok || !result.data?.ok) {
    for (const button of buttons) button.disabled = false;
    return showError(result.data?.error ?? "The change could not be saved.");
  }
  // Only removed from the list after the server confirmed the change.
  if (typeof result.data.reported === "number") setReportedCount(result.data.reported);
  item.remove();
  if (!list.children.length) stateText.textContent = status === "reported" ? "No reported comments." : "Nothing here.";
}

// Clears the reports on a comment without deleting it.
async function unreport(comment, item, buttons, unreportBtn) {
  for (const button of buttons) button.disabled = true;
  showError("");
  const result = await requestJson("/api/admin.php?action=unreport", { method: "POST", body: { id: comment.id }, headers: { "X-CSRF-Token": csrf } });
  if (result.status === 401) return showView("login");
  if (!result.ok || !result.data?.ok) {
    for (const button of buttons) button.disabled = false;
    return showError(result.data?.error ?? "The change could not be saved.");
  }
  if (typeof result.data.reported === "number") setReportedCount(result.data.reported);
  if (status === "reported") {
    item.remove();
    if (!list.children.length) stateText.textContent = "No reported comments.";
    return;
  }
  // Keep the comment in this list, just clear the report marks.
  comment.reports = [];
  item.classList.remove("comment--reported");
  item.querySelector(".admin-report")?.remove();
  item.querySelector(".comment__reports")?.remove();
  for (const button of buttons) button.disabled = false;
  unreportBtn.remove();
}

function renderComment(comment) {
  const item = el("li", "comment");
  item.append(el("p", "comment__name", comment.name), el("p", "comment__message", comment.message));
  const likes = comment.likes ? `, ${comment.likes} like${comment.likes === 1 ? "" : "s"}` : "";
  item.append(el("p", "small muted mt-xs", `Sent ${formatDate(comment.createdAt, true)}${likes}`));
  if (comment.reports.length) {
    item.classList.add("comment--reported");
    const flag = el("p", "admin-report mt-xs", `⚑ Reported ${comment.reports.length} time${comment.reports.length === 1 ? "" : "s"}${comment.status !== "published" ? ` (${comment.status === "rejected" ? "already hidden" : "waiting"})` : ""}`);
    item.append(flag);
  }
  const reasons = comment.reports.filter((report) => report.reason);
  if (reasons.length) {
    const ul = el("ul", "comment__reports small muted");
    for (const report of reasons) ul.append(el("li", "", report.reason));
    item.append(ul);
  }

  const row = el("div", "button-row mt-s");
  const buttons = [];
  const add = (label, quiet, onClick) => {
    const button = el("button", quiet ? "btn btn-quiet" : "btn", label);
    button.type = "button";
    button.addEventListener("click", onClick);
    buttons.push(button);
    row.append(button);
    return button;
  };
  if (comment.status !== "published") add("Approve", false, () => change(comment, "update", { id: comment.id, status: "published" }, item, buttons));
  if (comment.status !== "rejected") {
    add(comment.status === "published" ? "Unpublish" : "Reject", true, () => change(comment, "update", { id: comment.id, status: "rejected" }, item, buttons));
  }
  const remove = add("Delete", true, () => {
    if (remove.dataset.confirm) return change(comment, "delete", { id: comment.id }, item, buttons);
    remove.dataset.confirm = "1";
    remove.textContent = "Delete for good";
  });
  if (comment.reports.length) {
    const unreportBtn = add("Clear reports", true, () => unreport(comment, item, buttons, unreportBtn));
  }
  item.append(row);
  return item;
}

async function loadList() {
  showError("");
  list.replaceChildren();
  stateText.textContent = "Loading…";
  const result = await requestJson(`/api/admin.php?action=list&status=${status}`);
  if (result.status === 401) return showView("login");
  if (!result.ok || !result.data?.ok) {
    stateText.textContent = "";
    return showError(result.data?.error ?? "Comments could not be loaded.");
  }
  stateText.textContent = result.data.comments.length ? "" : status === "reported" ? "No reported comments." : "Nothing here.";
  setReportedCount(result.data.reported ?? 0);
  for (const comment of result.data.comments) list.append(renderComment(comment));
}

tabs.forEach((tab) => {
  tab.addEventListener("click", () => {
    status = tab.dataset.status;
    for (const other of tabs) other.setAttribute("aria-pressed", String(other === tab));
    loadList();
  });
});

function showArea(area) {
  for (const tab of areaTabs) tab.setAttribute("aria-pressed", String(tab.dataset.area === area));
  for (const [name, panel] of Object.entries(areaPanels)) panel.hidden = name !== area;
  showError("");
  if (area !== "comments" && !contentLoaded) {
    contentLoaded = true;
    loadContent({ texts: areaPanels.texts, strava: areaPanels.strava, ask: areaPanels.ask }, () => csrf, () => {
      contentLoaded = false;
      showView("login");
    });
  }
}

areaTabs.forEach((tab) => tab.addEventListener("click", () => showArea(tab.dataset.area)));

loginForm.addEventListener("submit", async (event) => {
  event.preventDefault();
  const field = loginForm.querySelector("input");
  showError("");
  const result = await requestJson("/api/admin.php?action=login", { method: "POST", body: { password: field.value } });
  if (result.ok && result.data?.ok) {
    field.value = "";
    csrf = result.data.csrf;
    showView("panel");
    return loadList();
  }
  showError(result.data?.error ?? "Sign-in failed.");
});

root.querySelector("[data-admin-logout]").addEventListener("click", async () => {
  await requestJson("/api/admin.php?action=logout", { method: "POST", body: {} });
  csrf = "";
  contentLoaded = false;
  list.replaceChildren();
  showView("login");
});

const session = await requestJson("/api/admin.php?action=session");
if (!session.data?.configured) {
  showView(session.status === 0 ? "login" : "off");
} else if (session.data.signedIn) {
  csrf = session.data.csrf;
  showView("panel");
  loadList();
} else {
  showView("login");
}
