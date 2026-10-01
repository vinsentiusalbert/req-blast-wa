(() => {
    'use strict';
    const form = document.getElementById('whatsapp-template-form');
    if (!form) return;
    const find = selector => form.querySelector(selector);
    const headerType = find('#template-header-type');
    const headerText = find('#template-header-text');
    const body = find('#template-body');
    const asset = find('#template-asset');
    const saveLabel = find('[data-save-template]').textContent;
    let imageUrl = asset.dataset.existingImage || '';
    let objectUrl = null;

    const syncPreview = () => {
        find('[data-body-count]').textContent = body.value.length + '/550 karakter';
        find('[data-header-count]').textContent = headerText.value.length + '/60 karakter';
        window.WhatsAppPreview.render({
            header_type: headerType.value, header_text: headerText.value,
            image: imageUrl, body: body.value,
        });
    };
    const syncHeader = () => {
        const text = headerType.value === 'TEXT';
        const image = headerType.value === 'IMAGE';
        find('[data-header-text-field]').hidden = !text;
        find('[data-header-image-field]').hidden = !image;
        headerText.disabled = !text;
        headerText.required = text;
        asset.disabled = !image;
        asset.required = image && !asset.dataset.existingImage;
        syncPreview();
    };
    [body, headerText].forEach(input => input.addEventListener('input', syncPreview));
    headerType.addEventListener('change', syncHeader);
    asset.addEventListener('change', () => {
        const file = asset.files[0];
        const error = find('[data-asset-error]');
        asset.setCustomValidity('');
        error.hidden = true;
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = null;
        imageUrl = asset.dataset.existingImage || '';
        if (file) {
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size >= 1024 * 1024) {
                const message = 'Pilih gambar JPG, PNG, atau WebP dengan ukuran di bawah 1 MB.';
                error.textContent = message;
                error.hidden = false;
                asset.setCustomValidity(message);
            } else {
                objectUrl = URL.createObjectURL(file);
                imageUrl = objectUrl;
            }
        }
        find('[data-asset-label]').textContent = file?.name || (asset.dataset.existingImage ? 'Menggunakan gambar yang tersimpan.' : 'Belum ada gambar dipilih.');
        syncPreview();
    });
    form.querySelectorAll('[data-format]').forEach(button => button.addEventListener('click', () => {
        const start = body.selectionStart, end = body.selectionEnd;
        const selected = body.value.slice(start, end);
        const markers = {bold: '*', italic: '_', strike: '~', code: String.fromCharCode(96).repeat(3)};
        const marker = markers[button.dataset.format];
        const replacement = marker ? marker + selected + marker : selected.split('\n').map((line, index) => (button.dataset.format === 'number' ? (index + 1) + '.' : '-') + ' ' + line).join('\n');
        if (body.value.length - selected.length + replacement.length > body.maxLength) return;
        body.setRangeText(replacement, start, end, 'select');
        body.focus();
        if (marker) body.setSelectionRange(start + marker.length, start + replacement.length - marker.length);
        syncPreview();
    }));
    form.addEventListener('submit', () => {
        const button = find('[data-save-template]');
        button.disabled = true;
        button.textContent = 'Menyimpan template...';
    });
    window.addEventListener('pageshow', () => {
        find('[data-save-template]').disabled = false;
        find('[data-save-template]').textContent = saveLabel;
    });
    window.addEventListener('pagehide', () => { if (objectUrl) URL.revokeObjectURL(objectUrl); });
    syncHeader();
})();
