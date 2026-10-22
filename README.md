# Ruang Pesan — Laravel Admin & User

Aplikasi Laravel 12 dengan autentikasi session dan dua role: `admin` dan `user`.

## Fitur

- Login menggunakan username, ingat saya, dan logout. Pembuatan akun hanya melalui admin.
- Username unik (3–50 karakter), tidak membedakan huruf besar/kecil; email tetap menjadi informasi akun.
- Pendaftaran publik dinonaktifkan. Admin membuat akun melalui **Pengguna → Tambah pengguna**; akun baru memiliki role `user` dan admin tetap login pada akunnya sendiri.
- Dashboard terpisah sesuai role; akses tanpa izin menghasilkan HTTP 403.
- Admin dapat mencari pengguna, melihat daftar dengan pagination, dan mengubah role pengguna lain.
- Role akun sendiri tidak dapat diubah dari halaman admin.
- Password di-hash, proteksi CSRF, regenerasi session saat login, dan pembatasan percobaan login.
- Tampilan Blade responsif berbahasa Indonesia. CSS lokal, tidak membutuhkan build npm.
- Menu Broadcast WhatsApp untuk user: buat/edit template dengan preview, kemudian buat/edit broadcast Pending.

## Tampilan Ruang Pesan

Identitas Ruang Pesan memakai dasar putih, teks charcoal, dan aksen hijau lembut. Sistem visual bersama ada di `public/css/white-theme.css`, dimuat setelah stylesheet komponen agar login, registrasi, dashboard, template, broadcast, persetujuan, dan laporan konsisten. Logo dan favicon ada di `public/assets/ruang-pesan.svg`. Navigasi mendukung desktop dan menu mobile dengan akses keyboard.

Login tetap menggunakan username dan autentikasi Laravel lokal. Menu menyesuaikan role admin/user dan fitur yang tersedia. Font Plus Jakarta Sans dan Space Grotesk dimuat dari Bunny Fonts, dengan fallback Arial jika tidak tersedia.

## Broadcast WhatsApp

Menu **Template WhatsApp** dan **Broadcast WhatsApp** terpisah pada sidebar user. Admin meninjau pengajuan di menu **Persetujuan Template**. Komponen builder dan pratinjau pesan menggunakan `public/css/whatsapp.css` dengan sistem visual bersama Ruang Pesan.

1. User membuat template: nama unik per user, bahasa, display name opsional (kosong secara default), header teks/gambar, pesan, footer, dan tombol.
2. Ajukan template. Status awal adalah **Menunggu persetujuan**. Admin melihat preview, menyetujui, atau menolak dengan alasan.
3. User membuka menu **Broadcast WhatsApp**, memilih template miliknya yang **Disetujui**, lalu mengisi nama broadcast dan mengunggah CSV penerima. Kolom pertama wajib `msisdn`, diikuti kolom variabel sesuai template. Nomor wajib diawali `62` dan hanya berisi angka. Duplikat ditolak, tanpa batas jumlah penerima.
4. Simpan broadcast: status awal `pending`. Admin membuka **Approval Broadcast**, memeriksa detail, lalu **Approve** (`approve`) dan **Finish** (`finish`). Admin juga dapat menolak broadcast Pending melalui **Reject** dengan alasan wajib (maksimal 2.000 karakter). User melihat alasan pada daftar, detail, dan halaman edit. User dapat mengedit saat Pending atau Reject; menyimpan perbaikan mengajukan ulang sebagai Pending dan menghapus alasan penolakan sebelumnya. Broadcast Approve/Finish tetap terkunci.

Persetujuan template dilakukan oleh admin aplikasi. Pengiriman pesan dan koneksi ke provider WhatsApp belum tersedia. Persetujuan admin aplikasi berbeda dari persetujuan provider. Setiap edit template menaikkan revisi, menghapus persetujuan sebelumnya, dan mengajukan ulang. Approval broadcast Pending yang memakai template tersebut terblokir sampai admin menyetujui revisi baru. Keputusan admin memeriksa revisi dan status dalam transaksi, sehingga form peninjauan yang sudah kedaluwarsa tidak dapat menyetujui konten baru. Batas 550 karakter pesan dan 10 tombol adalah batas aplikasi yang mengikuti builder referensi; validasi provider perlu disesuaikan ketika integrasi ditambahkan.

Gambar header mendukung JPG/PNG/WebP hingga 5 MB, disimpan di disk `local` (privat), dan disajikan lewat route yang memeriksa pemilik. Tidak memerlukan `storage:link`. Untuk instalasi dari versi sebelumnya, jalankan `php artisan migrate`.

| Bagian | Lokasi dan tanggung jawab |
| --- | --- |
| Model | `app/Models/WhatsappTemplate.php`, `WhatsappBroadcast.php`: atribut, cast, dan relasi milik user |
| Controller | `app/Http/Controllers/User/WhatsApp`: halaman user; `app/Http/Controllers/Admin/WhatsApp`: antrean dan keputusan persetujuan |
| Form Request | `app/Http/Requests/WhatsApp`: validasi input, file, tombol, dan template milik user |
| Policy | `app/Policies/Whatsapp*Policy.php`: akses pemilik; admin dapat membaca template/gambar untuk peninjauan |
| Action | `app/Actions/WhatsApp`: simpan/ajukan ulang template, tinjau revisi oleh admin, dan simpan draft dengan pemeriksaan persetujuan |
| Service / Rule | `app/Services/WhatsApp/RecipientParser.php`, `app/Rules/RecipientList.php`: normalisasi, deduplikasi, dan validasi penerima |
| View | `resources/views/user/whatsapp`: halaman template/broadcast dan partial preview/navigasi |
| Asset | `public/css/whatsapp.css`, `public/js/whatsapp-*.js`: styling, editor, tombol dinamis, preview aman memakai `textContent` |

Rute user: `/user/whatsapp/templates` dan `/user/whatsapp/broadcasts`, masing-masing memiliki index, create, store, show, edit, dan update. Semua rute berada di middleware `auth` dan `role:user`. Rute admin: `/admin/whatsapp/templates`, detail template, dan `PATCH /admin/whatsapp/templates/{template}/review`, dengan middleware `auth` dan `role:admin`. Tidak ada endpoint pengiriman atau penghapusan. Ikon WhatsApp menggunakan Font Awesome 6.7.2; CSS, font Brands, dan lisensinya berada di `public/vendor/fontawesome`.

## Menjalankan proyek

Persyaratan: PHP 8.2+, Composer, dan ekstensi PDO SQLite.

Untuk workspace ini, dependensi, `.env`, database SQLite, dan akun demo sudah disiapkan. Jalankan:

```sh
php artisan serve
```

Buka http://127.0.0.1:8000.

Untuk pemasangan ulang dari salinan source:

```sh
composer install
```

Salin `.env.example` menjadi `.env`, buat file kosong `database/database.sqlite` jika belum ada, lalu:

```sh
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## Akun demo lokal

| Role | Username | Kata sandi |
| --- | --- | --- |
| Admin | admin | Admin123! |
| User | user | User12345! |

Untuk database lama, jalankan `php artisan migrate`. Username dibuat dari bagian email sebelum `@`, dinormalisasi menjadi huruf kecil; benturan diberi akhiran angka. Password dan role lama dipertahankan. Username dapat dilihat pada daftar pengguna oleh admin.

Seeder hanya membuat akun demo pada environment `local` / `testing` dan tidak menimpa akun yang sudah ada. Akun demo ditujukan untuk pengembangan; jangan gunakan database demo sebagai database produksi.

## Rute

| Rute | Akses |
| --- | --- |
| `/login` | Tamu |
| `/dashboard` | Pengguna login, diarahkan sesuai role |
| `/admin/dashboard` | Admin |
| `/admin/users` | Admin |
| `GET /admin/users/create`, `POST /admin/users` | Admin, membuat akun pengguna |
| `PATCH /admin/users/{user}/role` | Admin, untuk akun lain |
| `/user/dashboard` | User |
| `POST /logout` | Pengguna login |

## Struktur utama

- `app/Models/User.php`: konstanta dan pemeriksaan role; role tidak mass assignable.
- `app/Http/Middleware/EnsureUserHasRole.php`: pembatasan akses role di server.
- `app/Http/Controllers/AuthController.php`: login dan logout.
- `app/Http/Controllers/Admin/UserController.php`: pembuatan akun, daftar, pencarian, perubahan role.
- `bootstrap/app.php`: pendaftaran middleware `role`.
- `routes/web.php`: rute dan pembatasan akses.
- `resources/views`: halaman Blade.
- `public/css/app.css`: stylesheet yang langsung dapat digunakan.

## Pengujian

### Broadcast dengan CSV dan variabel

Pada halaman broadcast, unggah CSV UTF-8 dengan pemisah koma (tanpa batas jumlah penerima di aplikasi). Kolom pertama wajib `msisdn`; nama kolom selanjutnya bebas dan peka huruf besar/kecil:

```csv
msisdn,nama,produk
6281234567890,Budi,Sepatu
6281234567891,Siti,Tas
```

Template `Halo {{nama}}, produk {{produk}} tersedia.` ditampilkan pada preview sebagai `Halo Budi, produk Sepatu tersedia.` Variabel didukung pada isi pesan dan header teks. Semua variabel template wajib memiliki kolom dan nilai pada setiap record. Nomor wajib diawali `62` dan hanya berisi angka; nomor duplikat ditolak agar data personalisasi tidak ambigu. Preview upload dan halaman detail menggunakan record pertama. Data setiap record tersimpan bersama draft; saat edit, upload CSV baru mengganti seluruh penerima, sedangkan tanpa upload data lama dipertahankan. Setelah validasi form gagal, file perlu dipilih ulang.

Jalankan `php artisan migrate` untuk menambahkan penyimpanan record CSV pada instalasi lama. Broadcast mengikuti status Pending ? Approve ? Finish yang diproses manual oleh admin. Waktu dan admin pemroses tersimpan. Migrasi juga mengubah draft lama menjadi Pending. Perubahan status ini tidak memanggil layanan pengiriman WhatsApp.

```sh
php artisan test
php vendor/bin/pint --test
```

Pengujian memakai SQLite `:memory:` sehingga tidak mengubah database aplikasi. Cakupan meliputi autentikasi, penutupan pendaftaran publik, pembuatan akun khusus admin beserta validasinya, rate limiting, pemisahan akses, penolakan eskalasi role, perubahan role, pencarian, pagination, dan seeder.

Referensi: [autentikasi Laravel](https://laravel.com/docs/12.x/authentication), [middleware](https://laravel.com/docs/12.x/middleware).

## Report WhatsApp

Menu **Report WhatsApp** tersedia untuk user (broadcast sendiri) dan admin (seluruh user). Filter meliputi tanggal pembuatan broadcast, status broadcast, serta user khusus admin. Detail menampilkan status per penerima, jumlah percobaan, waktu pengiriman/gagal, dan alasan gagal. Pencarian nomor dan filter status juga berlaku pada unduhan CSV.

Jalankan `php artisan migrate` untuk menambahkan kolom pelaporan penerima. Status pengiriman `pending`, `sent`, dan `failed` dibaca dari tabel penerima; status broadcast Finish tidak dianggap sebagai bukti terkirim. Fitur ini tidak mengirim pesan atau mengisi hasil pengiriman secara otomatis; integrasi pengiriman perlu mencatat hasil ke tabel penerima.

Report mendukung unduhan Excel `.xlsx` dan CSV dari halaman detail, mengikuti filter aktif. Excel mempertahankan nomor sebagai teks, membekukan baris judul, dan menyediakan filter kolom. Informasi kegagalan lama disembunyikan pada penerima yang sudah terkirim; kartu Gagal hanya tampil bila ada penerima berstatus gagal. Ekspor Excel memerlukan ekstensi PHP zip dan xmlwriter.
