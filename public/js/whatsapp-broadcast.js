(() => {
    'use strict';
    const form = document.getElementById('whatsapp-broadcast-form');
    if (!form) return;
    const previews = JSON.parse(document.getElementById('whatsapp-template-previews').textContent);
    const select = document.getElementById('broadcast-template');
    const recipients = document.getElementById('broadcast-recipients');
    const preview = () => window.WhatsAppPreview.render(previews[select.value] || {});
    const count = () => {
        const entries = recipients.value.split(/[\r\n,;]+/).map(value => value.trim()).filter(Boolean);
        form.querySelector('[data-recipient-count]').textContent = entries.length + ' entri dimasukkan. Nomor valid dan unik dihitung saat disimpan.';
    };
    select.addEventListener('change', preview);
    recipients.addEventListener('input', count);
    form.addEventListener('submit', () => {
        const button = form.querySelector('[data-save-broadcast]');
        button.disabled = true;
        button.textContent = 'Menyimpan draft...';
    });
    window.addEventListener('pageshow', () => {
        const button = form.querySelector('[data-save-broadcast]');
        button.disabled = button.dataset.unavailable === 'true';
        button.textContent = 'Simpan Draft →';
    });
    preview();
    count();
})();
