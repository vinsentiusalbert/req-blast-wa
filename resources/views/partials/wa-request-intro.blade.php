<dialog id="wa-request-intro" class="request-intro" aria-labelledby="request-intro-title" aria-describedby="request-intro-description" data-auto-open="{{ session()->pull('show_wa_request_intro', false) ? 'true' : 'false' }}">
    <div class="request-intro__body">
        <span class="request-intro__eyebrow">PANDUAN PORTAL</span>
        <h2 id="request-intro-title">Request WA Long Number</h2>
        <p id="request-intro-description">Portal ini digunakan untuk mengajukan dan memantau request campaign WhatsApp Long Number. Setiap pengajuan ditinjau oleh admin.</p>
        <ol class="request-intro__steps">
            <li><strong>Siapkan template</strong><span>Buat isi pesan dan header, lalu tunggu persetujuan template dari admin.</span></li>
            <li><strong>Ajukan campaign</strong><span>Pilih template yang disetujui dan unggah CSV. Kolom pertama wajib msisdn, dengan nomor diawali 62. Kolom berikutnya mengikuti variabel pesan.</span></li>
            <li><strong>Pantau hasil review</strong><span>Pending: menunggu review. Approve: disetujui. Finish: ditandai selesai oleh admin. Jika Reject, baca alasan, perbaiki, lalu ajukan ulang.</span></li>
        </ol>
        @if(auth()->user()->isAdmin())
            <p class="request-intro__note">Sebagai admin, mulai dari Persetujuan Template atau Approval Broadcast. Gunakan filter status dan user untuk memilih request yang perlu ditinjau.</p>
        @else
            <p class="request-intro__note">Menyimpan campaign adalah pengajuan request. Status Finish merupakan penanda penyelesaian oleh admin.</p>
        @endif
        <form method="dialog"><button class="button" autofocus>Saya mengerti</button></form>
    </div>
</dialog>
