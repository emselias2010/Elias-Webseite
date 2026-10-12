/** Sport gallery: filter buttons and an enlarged view for photos. */
export function initGallery(root) {
  const buttons = Array.from(root.querySelectorAll("[data-filter]"));
  const items = Array.from(root.querySelectorAll("[data-category]"));
  const empty = root.querySelector("[data-gallery-empty]");
  const dialog = root.querySelector("[data-lightbox]");

  buttons.forEach((button) => {
    button.addEventListener("click", () => {
      const filter = button.dataset.filter;
      for (const other of buttons) other.setAttribute("aria-pressed", String(other === button));
      let visible = 0;
      for (const item of items) {
        const show = filter === "All" || item.dataset.category === filter;
        item.hidden = !show;
        if (show) {
          visible += 1;
          item.dataset.reveal = "shown";
        }
      }
      empty.hidden = visible > 0;
    });
  });

  if (!dialog || typeof dialog.showModal !== "function") return;
  const image = dialog.querySelector("[data-lightbox-img]");
  const heading = dialog.querySelector("[data-lightbox-heading]");
  const caption = dialog.querySelector("[data-lightbox-caption]");
  const prev = dialog.querySelector("[data-lightbox-prev]");
  const next = dialog.querySelector("[data-lightbox-next]");
  let current = -1;

  const photos = () => items.filter((item) => !item.hidden).map((item) => item.querySelector("[data-lightbox-src]")).filter(Boolean);

  const showPhoto = (index) => {
    const list = photos();
    if (list.length === 0) return;
    current = (index + list.length) % list.length;
    const source = list[current];
    image.src = source.dataset.lightboxSrc;
    image.alt = source.dataset.lightboxAlt || "";
    heading.textContent = source.dataset.lightboxTitle || "";
    caption.textContent = source.dataset.lightboxText || "";
    dialog.setAttribute("aria-label", source.dataset.lightboxTitle || "Photo");
    prev.hidden = next.hidden = list.length < 2;
    if (!dialog.open) dialog.showModal();
  };

  root.addEventListener("click", (event) => {
    const trigger = event.target.closest("[data-lightbox-src]");
    if (trigger) showPhoto(photos().indexOf(trigger));
  });
  prev.addEventListener("click", () => showPhoto(current - 1));
  next.addEventListener("click", () => showPhoto(current + 1));
  dialog.querySelector("[data-lightbox-close]").addEventListener("click", () => dialog.close());
  // A click on the backdrop (the dialog element itself) closes it.
  dialog.addEventListener("click", (event) => {
    if (event.target === dialog) dialog.close();
  });
  dialog.addEventListener("keydown", (event) => {
    if (event.key === "ArrowRight") showPhoto(current + 1);
    if (event.key === "ArrowLeft") showPhoto(current - 1);
  });
}
