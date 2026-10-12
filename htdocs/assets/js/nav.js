/** Mobile menu: opens a floating panel, traps focus, closes with Escape, a link or a tap outside. */
export function initNav() {
  const toggle = document.querySelector("[data-menu-toggle]");
  const menu = document.getElementById("site-menu");
  if (!toggle || !menu) return;
  const label = toggle.querySelector("[data-menu-label]");
  const wide = window.matchMedia("(min-width: 64rem)");
  let previousOverflow = "";

  const setOpen = (open, returnFocus = false) => {
    if (open === !menu.hidden) return;
    menu.hidden = !open;
    toggle.setAttribute("aria-expanded", String(open));
    label.textContent = open ? "Close" : "Menu";
    if (open) {
      previousOverflow = document.body.style.overflow;
      document.body.style.overflow = "hidden";
      menu.querySelector("a")?.focus();
    } else {
      document.body.style.overflow = previousOverflow;
      if (returnFocus) toggle.focus();
    }
  };

  toggle.addEventListener("click", () => setOpen(menu.hidden));
  for (const link of menu.querySelectorAll("a")) link.addEventListener("click", () => setOpen(false));
  menu.querySelector("[data-menu-close]")?.addEventListener("click", () => setOpen(false, true));
  wide.addEventListener("change", () => {
    if (wide.matches) setOpen(false);
  });

  document.addEventListener("keydown", (event) => {
    if (menu.hidden) return;
    if (event.key === "Escape") {
      setOpen(false, true);
      return;
    }
    if (event.key !== "Tab") return;
    const focusable = [toggle, ...menu.querySelectorAll("a")];
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });
}
