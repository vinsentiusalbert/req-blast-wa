(() => {
    'use strict';
    const form = document.getElementById('whatsapp-broadcast-form');
    if (!form) return;
    const previews = JSON.parse(document.getElementById('whatsapp-template-previews').textContent);
    const saved = JSON.parse(document.getElementById('broadcast-first-record').textContent);
    const select = document.getElementById('broadcast-template');
    const file = document.getElementById('broadcast-csv');
    const error = form.querySelector('[data-csv-error]');
    const count = form.querySelector('[data-recipient-count]');
    const button = form.querySelector('[data-save-broadcast]');
    let first = saved;
    let sequence = 0;
    let pending = false;
    let invalid = false;
    const render = () => {
        const data = {...(previews[select.value] || {})};
        const replace = text => (text || '').replace(/\{\{\s*([^{}]+?)\s*\}\}/gu, (match, key) => first && Object.hasOwn(first, key.trim()) ? first[key.trim()] : match);
        data.body = replace(data.body);
        data.header_text = replace(data.header_text);
        window.WhatsAppPreview.render(data);
        button.disabled = pending || invalid || button.dataset.unavailable === 'true';
    };
    const update = async () => {
        const current = ++sequence;
        error.hidden = true;
        invalid = false;
        pending = false;
        first = null;
        count.textContent = '';
        if (!file.files.length) {
            first = saved;
            render();
            return;
        }
        if (!select.value) {
            count.textContent = 'Pilih template untuk melihat preview CSV.';
            render();
            return;
        }
        pending = true;
        count.textContent = 'Memeriksa CSV...';
        render();
        try {
            const data = new FormData();
            data.append('_token', form.querySelector('[name="_token"]').value);
            data.append('recipients_csv', file.files[0]);
            data.append('whatsapp_template_id', select.value);
            const response = await fetch(form.dataset.previewUrl, {method: 'POST', body: data, headers: {'Accept': 'application/json'}});
            const result = await response.json();
            if (!response.ok) throw new Error(Object.values(result.errors || {}).flat().join(' ') || 'CSV tidak dapat diproses. Periksa template atau muat ulang halaman.');
            if (current !== sequence) return;
            first = result.first;
            count.textContent = `${result.count} penerima valid. Preview record pertama: +${first.msisdn}.`;
        } catch (e) {
            if (current !== sequence) return;
            invalid = true;
            error.textContent = e.message;
            error.hidden = false;
            count.textContent = '';
        } finally {
            if (current === sequence) { pending = false; render(); }
        }
    };
    select.addEventListener('change', update);
    file.addEventListener('change', update);
    form.addEventListener('submit', event => {
        if (pending || invalid) { event.preventDefault(); return; }
        button.disabled = true;
        button.textContent = 'Menyimpan broadcast...';
    });
    window.addEventListener('pageshow', () => { button.textContent = 'Simpan Broadcast'; update(); });
    render();
})();
