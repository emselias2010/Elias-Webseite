<section id="guestbook" class="section tone-soft" aria-labelledby="guestbook-title">
    <div class="wrap">
        <div class="section-head">
            <h2 id="guestbook-title" class="display reveal">Leave a comment.</h2>
            <p class="lead reveal" <?= style_var('--order', 1) ?>>Made it this far? Leave a message.</p>
        </div>

        <div class="split split--tight mt-xl" data-guestbook>
            <form class="tile guestbook-form" novalidate data-guestbook-form>
                <p class="notice mb-m" role="status" data-guestbook-off hidden>The guestbook is not connected to a database yet, so comments can't be saved at the moment.</p>
                <div>
                    <label for="gb-name" class="field-label">Name or nickname</label>
                    <input id="gb-name" name="name" class="field" type="text" maxlength="<?= LIMIT_NAME ?>" autocomplete="nickname" required>
                </div>
                <div class="mt-m">
                    <label for="gb-message" class="field-label">Message</label>
                    <textarea id="gb-message" name="message" class="field" maxlength="<?= LIMIT_MESSAGE ?>" rows="5" required aria-describedby="gb-hint"></textarea>
                    <p id="gb-hint" class="small muted mt-xs"><span data-guestbook-count><?= LIMIT_MESSAGE ?></span> characters left. Your comment appears right away.</p>
                </div>
                <?php /* Honeypot: hidden from people, tempting for bots. */ ?>
                <div class="hp-field" aria-hidden="true">
                    <label for="gb-website">Leave this field empty</label>
                    <input id="gb-website" name="website" type="text" tabindex="-1" autocomplete="off">
                </div>
                <button type="submit" class="btn mt-m">Post comment</button>
                <div class="mt-m" aria-live="polite" data-guestbook-status></div>
            </form>

            <div>
                <h3 class="heading-3">Comments</h3>
                <p class="muted mt-s" data-guestbook-state>Loading comments…</p>
                <ul class="comment-list mt-s" data-guestbook-list></ul>
                <button type="button" class="btn btn-quiet mt-m" data-guestbook-more hidden>Show older comments</button>
            </div>
        </div>
    </div>
</section>
