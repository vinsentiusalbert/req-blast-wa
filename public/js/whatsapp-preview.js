(() => {
    'use strict';
    // Use textContent for user-authored content; previews never inject HTML.
    window.WhatsAppPreview = {
        render(data = {}) {
            const write = (selector, value) => {
                const node = document.querySelector(selector);
                if (node) node.textContent = value || '';
                return node;
            };
            write('[data-preview-body]', data.body || 'Isi pesan Anda akan tampil di sini.');
            const header = write('[data-preview-header]', data.header_text);
            if (header) header.hidden = data.header_type !== 'TEXT' || !data.header_text;
            const image = document.querySelector('[data-preview-image]');
            if (image) {
                image.hidden = data.header_type !== 'IMAGE' || !data.image;
                if (data.image) image.src = data.image;
                else image.removeAttribute('src');
            }
        }
    };
})();
