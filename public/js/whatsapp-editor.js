(() => {
    'use strict';
    const form = document.getElementById('whatsapp-template-form');
    const editor = document.querySelector('[data-message-editor]');
    if (!form || !editor || !window.WhatsAppText) return;
    const body = form.querySelector('#template-body');
    const toolbar = form.querySelector('[data-message-toolbar]');
    const error = form.querySelector('[data-body-error]');
    const count = form.querySelector('[data-body-count]');
    const picker = form.querySelector('[data-emoji-picker]');
    const emojiToggle = form.querySelector('[data-emoji-toggle]');
    const max = body.maxLength;
    let savedRange = null;
    let composing = false;
    let submitted = false;
    editor.innerHTML = window.WhatsAppText.toHtml(body.value);
    editor.hidden = false;
    toolbar.hidden = false;
    body.hidden = true;
    body.required = false;
    form.querySelector('[id="template-body-label"]')?.addEventListener('click', event => { event.preventDefault(); editor.focus(); });

    const remember = () => {
        const selection = window.getSelection();
        if (selection.rangeCount && editor.contains(selection.anchorNode) && editor.contains(selection.focusNode)) savedRange = selection.getRangeAt(0).cloneRange();
    };
    const restore = () => {
        editor.focus();
        const selection = window.getSelection();
        if (!savedRange || !editor.contains(savedRange.commonAncestorContainer)) {
            savedRange = document.createRange();
            savedRange.selectNodeContents(editor);
            savedRange.collapse(false);
        }
        selection.removeAllRanges();
        selection.addRange(savedRange);
    };
    const commands = {bold: 'bold', italic: 'italic', strike: 'strikeThrough', bullet: 'insertUnorderedList', number: 'insertOrderedList'};
    const updateToolbar = () => {
        toolbar.querySelectorAll('[data-format][aria-pressed]').forEach(button => {
            const command = commands[button.dataset.format];
            const active = command ? document.queryCommandState(command) : button.dataset.format === 'code' && /monospace/i.test(document.queryCommandValue('fontName'));
            button.setAttribute('aria-pressed', String(!!active));
        });
    };
    const sync = () => {
        body.value = window.WhatsAppText.fromEditor(editor);
        const length = window.WhatsAppText.length(body.value);
        const empty = !editor.textContent.trim();
        const message = length > max ? `The message exceeds the limit of ${max} characters. Remove ${length - max} characters.` : empty && submitted ? 'The message body is required.' : '';
        count.textContent = `${length}/${max} characters`;
        count.classList.toggle('is-over-limit', length > max);
        error.textContent = message;
        error.hidden = !message;
        editor.setAttribute('aria-invalid', String(!!message));
        body.dispatchEvent(new Event('input', {bubbles: true}));
        remember();
        updateToolbar();
        return !message && !empty;
    };
    const closePicker = () => { picker.hidden = true; emojiToggle.setAttribute('aria-expanded', 'false'); };
    // Native editing commands preserve browser undo/redo and IME behavior.
    const command = (name, value = null) => { restore(); document.execCommand(name, false, value); sync(); };
    toolbar.addEventListener('mousedown', event => { if (event.target.closest('button')) { remember(); event.preventDefault(); } });
    toolbar.querySelectorAll('[data-format]').forEach(button => button.addEventListener('click', () => {
        const format = button.dataset.format;
        if (format === 'code') {
            restore();
            command('fontName', /monospace/i.test(document.queryCommandValue('fontName')) ? 'sans-serif' : 'monospace');
        } else if (format === 'clear') {
            command('removeFormat');
        } else command(commands[format]);
    }));
    emojiToggle.addEventListener('click', () => {
        const open = picker.hidden;
        picker.hidden = !open;
        emojiToggle.setAttribute('aria-expanded', String(open));
        if (open) picker.querySelector('button').focus();
    });
    picker.querySelectorAll('[data-emoji]').forEach(button => button.addEventListener('click', () => {
        command('insertText', button.dataset.emoji);
        closePicker();
    }));
    picker.addEventListener('keydown', event => {
        if (event.key === 'Escape') { closePicker(); emojiToggle.focus(); }
    });
    document.addEventListener('click', event => { if (!picker.contains(event.target) && !emojiToggle.contains(event.target)) closePicker(); });
    form.querySelector('[data-insert-variable]').addEventListener('click', () => command('insertText', '{{name}}'));
    editor.addEventListener('input', () => { if (!composing) sync(); });
    editor.addEventListener('compositionstart', () => { composing = true; });
    editor.addEventListener('compositionend', () => { composing = false; sync(); });
    editor.addEventListener('paste', event => {
        event.preventDefault();
        remember();
        command('insertText', event.clipboardData.getData('text/plain'));
    });
    // A drop may contain arbitrary HTML; accept plain text through the paste path only.
    editor.addEventListener('drop', event => event.preventDefault());
    editor.addEventListener('keydown', event => {
        if ((event.ctrlKey || event.metaKey) && ['b', 'i'].includes(event.key.toLowerCase())) {
            event.preventDefault(); remember(); command(event.key.toLowerCase() === 'b' ? 'bold' : 'italic');
        }
    });
    document.addEventListener('selectionchange', () => {
        const selection = window.getSelection();
        if (editor.contains(selection.anchorNode)) { remember(); updateToolbar(); }
    });
    form.addEventListener('submit', event => {
        submitted = true;
        if (!sync()) { event.preventDefault(); editor.focus(); }
    });
    sync();
})();
