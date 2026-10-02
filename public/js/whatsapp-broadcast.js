(() => {
    'use strict';
    const form = document.getElementById('whatsapp-broadcast-form');
    if (!form) return;
    const previews = JSON.parse(document.getElementById('whatsapp-template-previews').textContent);
    const select = document.getElementById('broadcast-template');
    const preview = () => window.WhatsAppPreview.render(previews[select.value] || {});
    select.addEventListener('change', preview);
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
})();
