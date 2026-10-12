/** Small helpers shared by the scripts. */

/** URL prefix when the site lives in a subfolder (e.g. XAMPP: /Elias-Webseite), set in head.php. */
const BASE_PATH = document.querySelector('meta[name="base-path"]')?.content ?? "";

/**
 * fetch + JSON with a timeout. Never throws: returns { ok, status, data }.
 * `data` is null when the answer was not JSON (for example an error page).
 */
export async function requestJson(url, { method = "GET", body, headers = {}, timeout = 30000, signal } = {}) {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeout);
  signal?.addEventListener("abort", () => controller.abort());
  try {
    const response = await fetch(url.startsWith("/") ? BASE_PATH + url : url, {
      method,
      headers: body === undefined ? headers : { "Content-Type": "application/json", ...headers },
      body: body === undefined ? undefined : JSON.stringify(body),
      credentials: "same-origin",
      signal: controller.signal,
    });
    const data = await response.json().catch(() => null);
    return { ok: response.ok, status: response.status, data };
  } catch {
    return { ok: false, status: 0, data: null, aborted: controller.signal.aborted };
  } finally {
    clearTimeout(timer);
  }
}

/** Creates an element with text content (never HTML). */
export function el(tag, className, text) {
  const node = document.createElement(tag);
  if (className) node.className = className;
  if (text !== undefined) node.textContent = text;
  return node;
}

const dateFormat = new Intl.DateTimeFormat("en-GB", { day: "numeric", month: "long", year: "numeric", timeZone: "Europe/Vienna" });
const dateTimeFormat = new Intl.DateTimeFormat("en-GB", { dateStyle: "medium", timeStyle: "short", timeZone: "Europe/Vienna" });

export function formatDate(iso, withTime = false) {
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return "";
  return (withTime ? dateTimeFormat : dateFormat).format(date);
}
