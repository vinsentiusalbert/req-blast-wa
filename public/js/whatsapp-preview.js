(() => {
    'use strict';
    // Only the shared formatter may generate HTML; headers remain plain text.
    window.WhatsAppPreview = {
        render(data = {}) {
            const write = (selector, value) => {
                const node = document.querySelector(selector);
                if (node) node.textContent = value || '';
                return node;
            };
            const body = document.querySelector('[data-preview-body]');
            if (body) body.innerHTML = window.WhatsAppText.toHtml(data.body || 'Your message will appear here.');
            const headerText = (data.header_text || '').trim().replace(/^\*+|\*+$/g, '').trim();
            const header = write('[data-preview-header]', headerText);
            if (header) header.hidden = data.header_type !== 'TEXT' || !data.header_text;
            const image = document.querySelector('[data-preview-image]');
            if (image) {
                image.hidden = data.header_type !== 'IMAGE' || !data.image;
                if (data.image) image.src = data.image;
                else image.removeAttribute('src');
            }
        }
    };
    document.querySelectorAll('[data-preview-body]').forEach(body => {
        body.innerHTML = window.WhatsAppText.toHtml(body.textContent);
    });
})();
