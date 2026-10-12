/**
 * Ask Elias. The conversation lives only in this page: nothing is stored on
 * the server or in the browser.
 */
import { el, requestJson } from "./util.js";

const MAX_QUESTION = 500;
const CONTEXT = 8;

export async function initAsk(root) {
  const log = root.querySelector("[data-ask-log]");
  const form = root.querySelector("[data-ask-form]");
  const input = form.querySelector("input");
  const send = form.querySelector('button[type="submit"]');
  const chips = Array.from(root.querySelectorAll("[data-question]"));
  const errorBox = root.querySelector("[data-ask-error]");
  const offNotice = root.querySelector("[data-ask-off]");

  const history = [];
  let pending = false;
  let available = false;

  const refresh = () => {
    const disabled = pending || !available;
    input.disabled = !available;
    send.disabled = disabled || input.value.trim() === "";
    for (const chip of chips) chip.disabled = disabled;
  };

  const showError = (message) => {
    errorBox.hidden = !message;
    errorBox.textContent = message || "";
  };

  const addMessage = (role, text) => {
    const bubble = el("p", "ask__message");
    bubble.dataset.role = role;
    bubble.append(el("span", "sr-only", role === "user" ? "You: " : "Assistant: "), document.createTextNode(text));
    log.append(bubble);
    log.scrollTop = log.scrollHeight;
    return bubble;
  };

  async function ask(text) {
    const question = text.trim();
    if (!question || pending || !available) return;
    if (question.length > MAX_QUESTION) {
      showError(`Questions can be at most ${MAX_QUESTION} characters long.`);
      return;
    }
    history.push({ role: "user", content: question });
    addMessage("user", question);
    input.value = "";
    showError("");
    pending = true;
    refresh();

    const typing = el("p", "ask__message");
    typing.dataset.role = "assistant";
    typing.dataset.typing = "";
    const dots = el("span", "typing");
    dots.setAttribute("aria-hidden", "true");
    dots.append(el("span"), el("span"), el("span"));
    typing.append(dots, el("span", "sr-only", "The assistant is writing"));
    log.append(typing);
    log.scrollTop = log.scrollHeight;

    const result = await requestJson("/api/ask.php", { method: "POST", body: { messages: history.slice(-CONTEXT) } });
    typing.remove();
    pending = false;

    if (result.ok && result.data?.ok && typeof result.data.reply === "string") {
      history.push({ role: "assistant", content: result.data.reply });
      addMessage("assistant", result.data.reply);
    } else {
      // The question stays visible, but is not part of the history sent next time.
      history.pop();
      if (result.data?.code === "not_configured") {
        available = false;
        offNotice.hidden = false;
      }
      showError(result.data?.error ?? (result.status === 0 ? "No answer arrived. Check your connection and try again." : "The assistant could not answer. Please try again."));
    }
    refresh();
  }

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    ask(input.value);
  });
  input.addEventListener("input", refresh);
  for (const chip of chips) chip.addEventListener("click", () => ask(chip.dataset.question));

  const status = await requestJson("/api/ask.php", { timeout: 10000 });
  available = Boolean(status.data?.configured);
  offNotice.hidden = available || status.status === 0;
  refresh();
}
