<section id="ask" class="section tone-ivory" aria-labelledby="ask-title">
    <div class="wrap split">
        <div>
            <h2 id="ask-title" class="display reveal">Ask Elias.</h2>
            <p class="lead mt-m">An AI assistant that knows what I have told it about my projects and my training. It is not me, and it says so when it doesn't know something.</p>
            <p class="small muted mt-m measure-s">Your questions are sent to an AI provider to be answered. The conversation is not saved on this site. Please don't enter personal data.</p>
        </div>

        <div class="reveal" <?= style_var('--order', 1) ?> data-ask>
            <p class="notice mb-s" role="status" data-ask-off hidden>The assistant is not switched on yet. No AI provider is connected, so questions can't be answered at the moment.</p>
            <div class="ask">
                <div class="ask__log" role="log" aria-live="polite" aria-label="Conversation" tabindex="0" data-ask-log>
                    <p class="ask__message" data-role="assistant">Hi. I'm an AI assistant, not Elias himself. Ask me about his projects, his training or what he is learning. I answer in English and German.</p>
                </div>
                <form class="ask__form" data-ask-form>
                    <label for="ask-input" class="sr-only">Your question</label>
                    <input id="ask-input" class="field" type="text" maxlength="<?= LIMIT_QUESTION ?>" placeholder="Ask a question" autocomplete="off" enterkeyhint="send" disabled>
                    <button type="submit" class="btn" disabled>Send</button>
                </form>
            </div>
            <p class="notice notice-error mt-s" role="alert" data-ask-error hidden></p>
        </div>
    </div>
</section>
