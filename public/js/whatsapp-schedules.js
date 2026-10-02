(() => {
    const form = document.getElementById('campaign-schedule-form');
    if (!form) return;
    const rows = document.getElementById('schedule-rows');
    const prototype = rows.firstElementChild.cloneNode(true);
    const refresh = () => {
        let total = 0;
        [...rows.children].forEach((row, index) => {
            row.querySelectorAll('[name]').forEach(input => {
                input.name = input.name.replace(/schedules\[\d+\]/, `schedules[${index}]`);
                if (input.type === 'number') total += Number(input.value) || 0;
            });
        });
        document.getElementById('schedule-total').textContent = `Total pesan terjadwal: ${total}`;
    };
    document.getElementById('add-schedule').addEventListener('click', () => {
        if (rows.children.length >= 100) return;
        const row = prototype.cloneNode(true);
        row.querySelectorAll('input, select').forEach(input => { input.value = ''; });
        rows.append(row);
        refresh();
    });
    rows.addEventListener('click', event => {
        if (event.target.closest('[data-remove-schedule]') && rows.children.length > 1) {
            event.target.closest('.schedule-row').remove();
            refresh();
        }
    });
    rows.addEventListener('input', refresh);
    refresh();
})();
