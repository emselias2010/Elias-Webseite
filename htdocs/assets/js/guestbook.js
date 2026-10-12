/**
 * Guestbook: form, list of published comments, likes, "show older" and reporting.
 * Comments are written into the page as text (textContent), never as HTML.
 */
import { el, formatDate, requestJson } from "./util.js";

const MAX_MESSAGE = 500;
const MAX_REASON = 200;

const HEART = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20.5s-7.5-4.6-9.3-9.4C1.4 7.6 3.6 4 7.2 4c2 0 3.6 1.1 4.8 2.7C13.2 5.1 14.8 4 16.8 4c3.6 0 5.8 3.6 4.5 7.1-1.8 4.8-9.3 9.4-9.3 9.4z"/></svg>';
const heartIcon = new DOMParser().parseFromString(HEART, "image/svg+xml").documentElement;

/** Heart button: shows the count, toggles this visitor's like. */
function likeButton(comment) {
  const button = el("button", "comment__like");
  button.type = "button";
  const count = el("span", "comment__like-count");
  const label = el("span", "sr-only");
  button.append(heartIcon.cloneNode(true), count, label);
  let liked = Boolean(comment.liked);
  let likes = Number(comment.likes) || 0;
  let busy = false;
  const render = () => {
    button.setAttribute("aria-pressed", String(liked));
    count.textContent = likes > 0 ? String(likes) : "";
    label.textContent = `${liked ? "Unlike" : "Like"} comment by ${comment.name}, ${likes} like${likes === 1 ? "" : "s"}`;
  };
  button.addEventListener("click", async () => {
    if (busy) return;
    busy = true;
    // Shown right away, corrected if the server says otherwise.
    const before = { liked, likes };
    liked = !liked;
    likes = Math.max(0, likes + (liked ? 1 : -1));
    render();
    button.classList.toggle("is-popping", liked);
    const result = await requestJson("/api/like.php", { method: "POST", body: { commentId: comment.id, liked } });
    if (result.ok && result.data?.ok) {
      liked = result.data.liked;
      likes = result.data.likes;
    } else {
      ({ liked, likes } = before);
    }
    render();
    busy = false;
  });
  render();
  return button;
}

export function initGuestbook(root) {
  const form = root.querySelector("[data-guestbook-form]");
  const status = root.querySelector("[data-guestbook-status]");
  const offNotice = root.querySelector("[data-guestbook-off]");
  const count = root.querySelector("[data-guestbook-count]");
  const list = root.querySelector("[data-guestbook-list]");
  const state = root.querySelector("[data-guestbook-state]");
  const more = root.querySelector("[data-guestbook-more]");
  const submit = form.querySelector('button[type="submit"]');
  const nameField = form.elements.namedItem("name");
  const messageField = form.elements.namedItem("message");
  const honeypot = form.elements.namedItem("website");

  let shownAt = Date.now();
  let nextBefore = null;
  let sending = false;
  const known = new Set();

  const setStatus = (kind, text) => {
    status.replaceChildren();
    if (!text) return;
    const box = el("p", kind === "error" ? "notice notice-error" : "notice", text);
    box.setAttribute("role", kind === "error" ? "alert" : "status");
    status.append(box);
  };

  const setState = (text, isError = false) => {
    state.hidden = !text;
    state.textContent = text || "";
    state.className = isError ? "notice notice-error mt-s" : "muted mt-s";
  };

  const disableForm = () => {
    offNotice.hidden = false;
    for (const field of [nameField, messageField, submit]) field.disabled = true;
  };

  function renderComment(comment) {
    const item = el("li", "comment");
    item.append(el("p", "comment__name", comment.name), el("p", "comment__message", comment.message));

    const meta = el("div", "comment__meta");
    const time = el("time", "small muted", formatDate(comment.publishedAt));
    time.dateTime = comment.publishedAt;
    const actions = el("div", "comment__actions");
    meta.append(time, likeButton(comment), actions);
    item.append(meta);

    const showIdle = () => {
      const report = el("button", "text-link small muted", "Report");
      report.type = "button";
      report.append(el("span", "sr-only", ` comment by ${comment.name}`));
      report.addEventListener("click", showConfirm);
      actions.replaceChildren(report);
    };

    const showConfirm = () => {
      const question = el("span", "small", "Report this comment as a problem?");
      const reason = el("input", "field comment__reason");
      reason.type = "text";
      reason.maxLength = MAX_REASON;
      reason.placeholder = "Reason (optional)";
      reason.setAttribute("aria-label", "Reason for the report (optional)");
      const yes = el("button", "text-link small", "Report");
      const no = el("button", "text-link small muted", "Cancel");
      yes.type = no.type = "button";
      no.addEventListener("click", showIdle);
      yes.addEventListener("click", async () => {
        yes.disabled = no.disabled = true;
        yes.textContent = "Reporting…";
        const result = await requestJson("/api/report.php", { method: "POST", body: { commentId: comment.id, reason: reason.value.trim().slice(0, MAX_REASON) } });
        if (result.status === 201 && result.data?.ok) {
          const done = el("span", "small muted", "Reported. Thank you.");
          done.setAttribute("role", "status");
          actions.replaceChildren(done);
        } else {
          const failed = el("span", "small", result.data?.error ?? "The report could not be sent.");
          failed.setAttribute("role", "alert");
          actions.replaceChildren(failed);
        }
      });
      actions.replaceChildren(question, reason, yes, no);
      reason.focus();
    };

    showIdle();
    return item;
  }

  async function load(before = null) {
    const query = before ? `?before=${encodeURIComponent(before)}` : "";
    const result = await requestJson(`/api/guestbook.php${query}`, { timeout: 15000 });
    const data = result.data;

    if (!result.ok || !data?.ok || !Array.isArray(data.comments)) {
      if (data?.code === "not_configured") {
        disableForm();
        setState("No comments can be shown yet.");
      } else {
        setState(data?.error ?? "Comments could not be loaded right now.", true);
      }
      return false;
    }

    for (const comment of data.comments) {
      if (known.has(comment.id)) continue;
      known.add(comment.id);
      list.append(renderComment(comment));
    }
    nextBefore = data.nextBefore ?? null;
    more.hidden = !nextBefore;
    setState(known.size === 0 ? "No comments yet. Yours could be the first." : "");
    return true;
  }

  /** New comments from other visitors appear without reloading (every 20 s while the tab is open). */
  async function checkNew() {
    if (document.visibilityState !== "visible") return;
    const result = await requestJson("/api/guestbook.php", { timeout: 15000 });
    if (!result.ok || !result.data?.ok || !Array.isArray(result.data.comments)) return;
    const fresh = result.data.comments.filter((comment) => !known.has(comment.id));
    // The newest comes first in the answer; insert from oldest to newest at the top.
    for (const comment of fresh.reverse()) {
      known.add(comment.id);
      list.prepend(renderComment(comment));
    }
    if (fresh.length) setState("");
  }
  setInterval(checkNew, 20000);
  document.addEventListener("visibilitychange", checkNew);

  more.addEventListener("click", async () => {
    if (!nextBefore) return;
    more.disabled = true;
    more.textContent = "Loading…";
    await load(nextBefore);
    more.disabled = false;
    more.textContent = "Show older comments";
  });

  messageField.addEventListener("input", () => {
    count.textContent = String(MAX_MESSAGE - messageField.value.length);
  });

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    if (sending) return;
    const name = nameField.value.trim();
    const message = messageField.value.trim();
    if (!name) return setStatus("error", "Please enter a name or nickname.");
    if (!message) return setStatus("error", "Please write a message.");

    sending = true;
    submit.disabled = true;
    submit.textContent = "Posting…";
    setStatus("", "");
    const result = await requestJson("/api/guestbook.php", {
      method: "POST",
      body: { name, message, website: honeypot.value, elapsedMs: Date.now() - shownAt },
      timeout: 20000,
    });
    sending = false;
    submit.disabled = false;
    submit.textContent = "Post comment";

    // Success is only shown when the server confirmed that the comment was stored.
    if (result.status === 201 && result.data?.ok) {
      setStatus("ok", "Thank you. Your comment is now in the guestbook.");
      const comment = result.data.comment;
      if (comment && !known.has(comment.id)) {
        known.add(comment.id);
        list.prepend(renderComment(comment));
        setState("");
      }
      messageField.value = "";
      count.textContent = String(MAX_MESSAGE);
      shownAt = Date.now();
      return;
    }
    if (result.data?.code === "not_configured") disableForm();
    setStatus(
      "error",
      result.data?.error ?? (result.status === 0 ? "Your comment could not be sent. Check your connection and try again." : "Your comment could not be saved. Please try again later."),
    );
  });

  load();
}
