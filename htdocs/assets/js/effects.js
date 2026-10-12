/**
 * Scroll-linked effects and reveal-on-scroll.
 *
 * Every element with a `data-scroll` attribute gets a CSS variable `--p`
 * (0 to 1) that follows the scroll position. The CSS decides what to do with
 * it (scale, fade, fill a line, light up words). Modes:
 *
 *   page     0 at the top of the page, 1 after scrolling one element height
 *   enter    0 when the element's top enters the viewport, 1 when it reaches the middle
 *   read     0 when the top is near the bottom, 1 when the bottom has passed the middle
 *   through  0 when the top is at the middle, 1 when the bottom is at the middle
 *   view     0 when the element enters at the bottom, 1 when it has left at the top
 *
 * One passive scroll listener, one animation frame per scroll event, and only
 * elements near the viewport are measured. Nothing moves when the visitor
 * prefers reduced motion; the CSS then shows the final state.
 */

const clamp = (value) => (value < 0 ? 0 : value > 1 ? 1 : value);

function progress(mode, rect, viewport, scrollY) {
  switch (mode) {
    case "page":
      return clamp(scrollY / Math.max(rect.height, 1));
    case "read":
      return clamp((viewport * 0.86 - rect.top) / (viewport * 0.36 + rect.height));
    case "through":
      return clamp((viewport * 0.5 - rect.top) / Math.max(rect.height, 1));
    case "view":
      return clamp((viewport - rect.top) / (viewport + rect.height));
    default:
      return clamp((viewport * 0.98 - rect.top) / (viewport * 0.5));
  }
}

const reducedMotion = () => window.matchMedia("(prefers-reduced-motion: reduce)").matches;

export function initScrollEffects() {
  if (reducedMotion() || typeof IntersectionObserver === "undefined") return;
  const elements = Array.from(document.querySelectorAll("[data-scroll]"));
  if (elements.length === 0) return;

  const near = new Set();
  const last = new WeakMap();
  let frame = 0;

  const update = () => {
    frame = 0;
    const viewport = window.innerHeight;
    const scrollY = window.scrollY;
    for (const element of near) {
      const mode = element.dataset.scroll || "enter";
      const value = Math.round(progress(mode, element.getBoundingClientRect(), viewport, scrollY) * 1000) / 1000;
      if (last.get(element) === value) continue;
      last.set(element, value);
      element.style.setProperty("--p", String(value));
      if (mode === "page") element.toggleAttribute("data-scroll-done", value >= 1);
    }
  };
  const schedule = () => {
    if (frame === 0) frame = requestAnimationFrame(update);
  };

  const observer = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (entry.isIntersecting) near.add(entry.target);
        else if (entry.target.dataset.scroll !== "page") near.delete(entry.target);
      }
      schedule();
    },
    { rootMargin: "25% 0px 25% 0px" },
  );
  for (const element of elements) {
    // "page" elements are pinned behind the content and always tracked.
    if (element.dataset.scroll === "page") near.add(element);
    observer.observe(element);
  }

  document.documentElement.dataset.scrollEffects = "on";
  update();
  window.addEventListener("scroll", schedule, { passive: true });
  window.addEventListener("resize", schedule, { passive: true });
}

/**
 * Elements with the class "reveal" rise into place when they scroll into view,
 * and again every time they come back (scrolling down or up). They are visible
 * in the HTML, so nothing depends on JavaScript. A ".fly-in" element starts
 * off-screen, so its parent is watched instead.
 */
export function initReveal(root = document) {
  if (reducedMotion() || typeof IntersectionObserver === "undefined") return;
  const targetsOf = (watched) => (watched.matches(".reveal") ? [watched] : watched.querySelectorAll(":scope > .fly-in"));
  const set = (watched, state) => {
    for (const target of targetsOf(watched)) if (!target.hidden) target.dataset.reveal = state;
  };
  // Shown once it is a little inside the viewport ...
  const show = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) if (entry.isIntersecting) set(entry.target, "shown");
    },
    { rootMargin: "0px 0px -6% 0px" },
  );
  // ... and reset only when it has left the screen completely, so it animates again next time.
  const hide = new IntersectionObserver((entries) => {
    for (const entry of entries) if (!entry.isIntersecting) set(entry.target, "pending");
  });
  const watched = new Set();
  for (const element of root.querySelectorAll(".reveal")) {
    const rect = element.getBoundingClientRect();
    const inView = rect.top < window.innerHeight * 0.9 && rect.bottom > 0;
    element.dataset.reveal = inView ? "shown" : "pending";
    watched.add(element.classList.contains("fly-in") ? element.parentElement : element);
  }
  for (const element of watched) {
    show.observe(element);
    hide.observe(element);
  }
}

/** Numbers with data-count-to count up from 0 every time they scroll into view. */
export function initCounters(root = document) {
  const counters = root.querySelectorAll("[data-count-to]");
  if (counters.length === 0 || reducedMotion() || typeof IntersectionObserver === "undefined") return;
  const format = (value, decimals) => value.toLocaleString("en-GB", { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
  const runs = new WeakMap();
  const observer = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (!entry.isIntersecting) continue;
        const node = entry.target;
        const target = Number(node.dataset.countTo);
        const decimals = Number(node.dataset.decimals || 0);
        const start = performance.now();
        const run = Symbol("run");
        runs.set(node, run);
        const tick = (now) => {
          if (runs.get(node) !== run) return;
          const t = Math.min((now - start) / 1600, 1);
          node.textContent = format(target * (1 - Math.pow(1 - t, 4)), decimals);
          if (t < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
      }
    },
    { rootMargin: "0px 0px -10% 0px" },
  );
  for (const node of counters) observer.observe(node);
}
