<div class="template-builder-field">
    <label for="template-body" id="template-body-label">Message body <span class="wa-required">*</span></label>
    <div class="template-builder-editor wa-visual-editor">
        <div class="template-builder-editor__toolbar" data-message-toolbar role="group" aria-label="Message formatting" hidden>
            <button type="button" data-format="bold" aria-label="Bold" title="Bold (Ctrl/Cmd+B)" aria-pressed="false"><b>B</b></button>
            <button type="button" data-format="italic" aria-label="Italic" title="Italic (Ctrl/Cmd+I)" aria-pressed="false"><i>I</i></button>
            <button type="button" data-format="strike" aria-label="Strikethrough" title="Strikethrough" aria-pressed="false"><s>S</s></button>
            <button type="button" data-format="code" aria-label="Monospace" title="Monospace" aria-pressed="false">&lt;/&gt;</button>
            <button type="button" data-format="bullet" aria-label="Bulleted list" title="Bulleted list" aria-pressed="false">&bull;</button>
            <button type="button" data-format="number" aria-label="Numbered list" title="Numbered list" aria-pressed="false">1.</button>
            <button type="button" data-format="clear" aria-label="Clear text formatting" title="Clear text formatting">Tx</button>
            <span class="wa-editor-divider" aria-hidden="true"></span>
            <button type="button" data-emoji-toggle aria-label="Choose emoji" title="Choose emoji" aria-expanded="false" aria-controls="message-emoji-picker">😊</button>
            <button type="button" data-insert-variable aria-label="Insert name variable" title="Insert name variable">@{{name}}</button>
        </div>
        <div id="message-emoji-picker" class="wa-emoji-picker" data-emoji-picker role="group" aria-label="Message emoji" hidden>
            @foreach(['😊' => 'Smile', '😀' => 'Happy', '😍' => 'Love', '🥰' => 'Affection', '😎' => 'Cool', '🙏' => 'Thank you', '👍' => 'Thumbs up', '👋' => 'Hello', '❤️' => 'Heart', '💚' => 'Green heart', '🎉' => 'Celebration', '🎁' => 'Gift', '🔥' => 'Fire', '✨' => 'Sparkles', '⭐' => 'Star', '✅' => 'Done', '📢' => 'Announcement', '📣' => 'Promotion', '🛍️' => 'Shopping', '💰' => 'Money', '🚀' => 'Rocket', '📍' => 'Location', '📅' => 'Calendar', '⏰' => 'Time'] as $emoji => $label)
            <button type="button" data-emoji="{{ $emoji }}" aria-label="{{ $label }}" title="{{ $label }}">{{ $emoji }}</button>
            @endforeach
        </div>
        <textarea id="template-body" name="body" maxlength="{{ \App\Models\WhatsappTemplate::MAX_BODY_LENGTH }}" rows="7" placeholder="Write your message here..." required>{{ old('body', $template->body) }}</textarea>
        <div class="wa-message-editor" data-message-editor contenteditable="true" role="textbox" aria-labelledby="template-body-label" aria-describedby="template-body-help template-body-error" aria-multiline="true" aria-required="true" data-placeholder="Write your message here..." hidden></div>
        <span class="template-builder-editor__count" data-body-count>0/550 characters</span>
    </div>
    <p id="template-body-error" class="wa-editor-error" data-body-error role="alert" hidden></p>
    <small id="template-body-help">Select text to apply formatting. Add emoji with the 😊 button or your keyboard. Use variables such as <code>@{{name}}</code> or <code>@{{product}}</code> matching the CSV column names after <code>msisdn</code>. Maximum 550 characters, including WhatsApp formatting markers.</small>
</div>
