/**
 * Entry point for every page. Each part only starts when its section exists.
 * Loaded as a module, so it runs after the HTML has been parsed.
 */
import { initCounters, initReveal, initScrollEffects } from "./effects.js";
import { initNav } from "./nav.js";

initNav();
initScrollEffects();
initReveal();
initCounters();

const gallery = document.querySelector("[data-gallery]");
if (gallery) import("./gallery.js").then(({ initGallery }) => initGallery(gallery));

const ask = document.querySelector("[data-ask]");
if (ask) import("./ask.js").then(({ initAsk }) => initAsk(ask));

const guestbook = document.querySelector("[data-guestbook]");
if (guestbook) import("./guestbook.js").then(({ initGuestbook }) => initGuestbook(guestbook));
