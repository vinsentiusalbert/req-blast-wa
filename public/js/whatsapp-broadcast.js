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
    const billing = form.querySelector('[data-billing-estimate]');
    let recipientCount = Number(billing?.dataset.count || 0);
    const money = value => 'IDR ' + new Intl.NumberFormat('en-US').format(value);
    const render = () => {
        const data = {...(previews[select.value] || {})};
        const replace = text => (text || '').replace(/\{\{\s*([^{}]+?)\s*\}\}/gu, (match, key) => first && Object.hasOwn(first, key.trim()) ? first[key.trim()] : match);
        data.body = replace(data.body);
        data.header_text = replace(data.header_text);
        window.WhatsAppPreview.render(data);
        if (billing) {
            const total = recipientCount * Number(billing.dataset.rate);
            billing.querySelector('[data-broadcast-cost]').textContent = recipientCount
                ? `${recipientCount} numbers × ${money(Number(billing.dataset.rate))} = ${money(total)}.${total > Number(billing.dataset.balance) ? ' Insufficient balance. Contact your administrator before the broadcast is approved.' : ''}`
                : 'Upload a CSV to calculate the cost.';
        }
        button.disabled = pending || invalid || button.dataset.unavailable === 'true';
    };
    const update = async () => {
        const current = ++sequence;
        error.hidden = true;
        invalid = false;
        pending = false;
        first = null;
        count.textContent = '';
        recipientCount = 0;
        if (!file.files.length) {
            first = saved;
            recipientCount = Number(billing?.dataset.count || 0);
            render();
            return;
        }
        if (!select.value) {
            count.textContent = 'Choose a template to preview the CSV.';
            render();
            return;
        }
        pending = true;
        count.textContent = 'Checking CSV...';
        render();
        try {
            const data = new FormData();
            data.append('_token', form.querySelector('[name="_token"]').value);
            data.append('recipients_csv', file.files[0]);
            data.append('whatsapp_template_id', select.value);
            const response = await fetch(form.dataset.previewUrl, {method: 'POST', body: data, headers: {'Accept': 'application/json'}});
            const result = await response.json();
            if (!response.ok) throw new Error(Object.values(result.errors || {}).flat().join(' ') || 'The CSV could not be processed. Check the template or reload the page.');
            if (current !== sequence) return;
            first = result.first;
            recipientCount = result.count;
            count.textContent = `${result.count} valid recipients. First record preview: +${first.msisdn}.`;
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
        button.textContent = 'Saving broadcast...';
    });
    window.addEventListener('pageshow', () => { button.textContent = 'Save Broadcast'; update(); });
    render();
})();
