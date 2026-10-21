(() => {
    const date = document.getElementById('schedule-date');
    const time = document.getElementById('schedule-time');
    if (!date || !time) return;
    const formatter = new Intl.DateTimeFormat('en-GB', { timeZone: 'Asia/Jakarta', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' });
    const update = () => {
        const parts = Object.fromEntries(formatter.formatToParts(new Date()).map(p => [p.type, p.value]));
        const today = `${parts.year}-${parts.month}-${parts.day}`;
        date.min = today;
        date.required = Boolean(time.value);
        time.required = Boolean(date.value);
        if (date.value === today) time.min = `${parts.hour}:${parts.minute}`;
        else time.removeAttribute('min');
    };
    date.addEventListener('change', update);
    time.addEventListener('change', update);
    date.form.addEventListener('submit', event => {
        update();
        if (!date.form.reportValidity()) event.preventDefault();
    });
    update();
    setInterval(update, 15000);
})();
