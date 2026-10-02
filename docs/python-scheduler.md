# Kontrak database scheduler WhatsApp

Konfigurasi dikelola hanya melalui halaman admin. Worker Python belum disertakan;
skema berikut menyediakan konfigurasi dan antrean untuk integrasi tersebut.

## Tabel

- `whatsapp_senders`: nomor pengirim dalam format internasional tanpa `+`,
  dan `is_active`. Nonaktif berarti worker harus berhenti memakai nomor itu,
  termasuk untuk jadwal yang telah tersimpan. Ini bukan status koneksi WhatsApp.
- `whatsapp_campaign_schedules`: `whatsapp_broadcast_id`,
  `send_date`, `timezone` (`Asia/Bangkok`, UTC+7), `message_count`, `status`.
  Satu baris adalah satu partisi. Beberapa partisi boleh berada pada tanggal yang sama.
- `whatsapp_broadcast_recipients`: setiap penerima memiliki
  `whatsapp_campaign_schedule_id`, `phone_number`, JSON `variables`,
  `delivery_status`, `sent_at`, dan `last_error`.

Admin menyimpan seluruh pembagian sekaligus. Total `message_count` harus sama
dengan jumlah penerima; setiap penerima ditugaskan ke tepat satu partisi.
Nomor duplikat dari CSV menggunakan variabel pada baris pertama.

## Perilaku worker yang harus diimplementasikan

1. Ambil jadwal yang tanggalnya sudah tiba (termasuk tanggal lewat yang belum
   selesai), dengan status `pending` atau `processing`, campaign `accepted` atau
   `processing`, template `approved`, dan minimal satu pengirim `is_active = true`.
   Tanggal dibandingkan dalam zona waktu jadwal, bukan zona waktu server.
2. Dalam transaksi, kunci campaign, template, jadwal, dan penerima yang akan
   diproses. Pilih secara acak satu nomor dari semua pengirim aktif untuk setiap
   pesan (bukan satu nomor tetap per partisi). Periksa kembali seluruh status
   tersebut serta status nomor terpilih. Jika tidak ada nomor aktif, tahan antrean.
   Gunakan urutan penguncian yang konsisten dengan aplikasi: campaign, template,
   penerima, pengirim. Klaim penerima dari `pending` menjadi `processing`, lalu
   commit sebelum melakukan panggilan jaringan. Jadwal/campaign menjadi
   `processing` ketika worker mulai. Jangan mengirim seluruh campaign sekaligus;
   gunakan penerima yang ditugaskan ke partisi itu.
3. Ganti placeholder `{{var1}}`, `{{ var2 }}`, dan seterusnya pada body/header
   TEXT menggunakan JSON `variables` penerima. Penggantian satu kali, tanpa
   mengevaluasi nilai sebagai kode atau mengganti placeholder di dalam nilai CSV.
   Template tersimpan di `whatsapp_templates`, melalui `whatsapp_template_id`.
4. Setelah provider mengonfirmasi pengiriman, tandai penerima `sent` dan isi
   `sent_at` dalam UTC. Simpan kegagalan sebagai `failed` dengan `last_error`.
   Worker perlu menyediakan retry dan rekonsiliasi klaim yang terhenti;
   gunakan kunci idempotensi berbasis ID penerima bila provider mendukungnya.
   Jangan mengulang penerima `sent`. Database saja tidak menjamin pengiriman
   tepat satu kali saat proses mati setelah provider menerima pesan.
5. Partisi menjadi `completed` hanya jika semua penerimanya `sent`.
   Campaign menjadi `completed` hanya jika semua penerima campaign `sent`.
   Kegagalan tidak dihitung sebagai selesai; retry tetap memakai partisi asal.
6. Campaign `draft`, `rejected`, `cancelled`, atau `completed` tidak boleh
   menjalankan pengiriman. Periksa status campaign dan nomor pengirim kembali
   sebelum setiap kirim. Nomor nonaktif dikeluarkan dari pilihan acak. Jika semua
   nomor nonaktif, tahan antrean tanpa menghapusnya.

Jadwal dapat diganti oleh admin hanya saat campaign `accepted`, semua jadwal
`pending`, dan semua penerima `pending`. Campaign yang telah memiliki jadwal
tidak bisa dikembalikan ke draft. Ini melindungi pembagian dari edit client.
Status yang diubah manual oleh admin tetap harus dihormati worker.

Contoh: 1.000 penerima dibagi 300 pesan pada 5 Oktober, 400 pada 6 Oktober,
dan 300 pada 7 Oktober. Setiap pesan memakai nomor yang dipilih acak dari pool aktif.
Jika worker tidak berjalan pada 5 Oktober, partisi itu tetap eligible sesudahnya
hingga tuntas; worker tidak boleh hanya mencari jadwal dengan tanggal hari ini.

Kolom lama `whatsapp_senders.name` dan `whatsapp_campaign_schedules.whatsapp_sender_id`
dipertahankan nullable untuk kompatibilitas data. Worker harus mengabaikannya:
nama tidak dikonfigurasi, dan pengirim tidak ditetapkan oleh jadwal.
