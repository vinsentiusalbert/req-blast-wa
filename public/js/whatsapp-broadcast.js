(() => {
    'use strict';
    const form = document.getElementById('whatsapp-broadcast-form');
    if (!form) return;
    const previews = JSON.parse(document.getElementById('whatsapp-template-previews').textContent);
    const select = document.getElementById('broadcast-template');
    const preview = () => window.WhatsAppPreview.render(previews[select.value] || {});
    select.addEventListener('change', preview);
    const scheduleInputs = [...form.querySelectorAll('[data-schedule-input]')];
    const startDate = document.getElementById('broadcast-send-date');
    const startTime = document.getElementById('broadcast-send-time');
    const endDate = document.getElementById('broadcast-end-date');
    const endTime = document.getElementById('broadcast-end-time');
    const scheduleSummary = document.getElementById('broadcast-schedule-summary');
    const refreshSchedule = () => {
        const hasSchedule = scheduleInputs.some(input => input.value !== '');
        scheduleInputs.forEach(input => { input.required = hasSchedule; });
        endDate.min = startDate.value;
        endTime.setCustomValidity('');
        if (!hasSchedule) {
            scheduleSummary.textContent = 'Jadwal belum ditentukan.';
            return;
        }
        if (scheduleInputs.some(input => input.value === '')) {
            scheduleSummary.textContent = 'Lengkapi tanggal dan jam mulai serta batas akhir pengiriman.';
            return;
        }
        if (`${endDate.value}T${endTime.value}` <= `${startDate.value}T${startTime.value}`) {
            endTime.setCustomValidity('Batas akhir pengiriman harus setelah tanggal dan jam mulai.');
            scheduleSummary.textContent = 'Batas akhir harus setelah waktu mulai pengiriman.';
            return;
        }
        const formatDate = value => value.split('-').reverse().join('/');
        scheduleSummary.textContent = `Mulai ${formatDate(startDate.value)} pukul ${startTime.value} sampai ${formatDate(endDate.value)} pukul ${endTime.value} (UTC+7 / WIB).`;
    };
    scheduleInputs.forEach(input => input.addEventListener('input', refreshSchedule));
    document.getElementById('clear-broadcast-schedule').addEventListener('click', () => {
        scheduleInputs.forEach(input => { input.value = ''; });
        refreshSchedule();
        startDate.focus();
    });
    form.addEventListener('submit', () => {
        const button = form.querySelector('[data-save-broadcast]');
        button.disabled = true;
        button.textContent = 'Membuat campaign...';
    });
    window.addEventListener('pageshow', () => {
        const button = form.querySelector('[data-save-broadcast]');
        button.disabled = button.dataset.unavailable === 'true';
        button.textContent = 'Buat Campaign →';
    });
    preview();
    refreshSchedule();
})();
