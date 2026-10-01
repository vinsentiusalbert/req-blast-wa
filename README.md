# ShopAds — Laravel Admin & User

Aplikasi Laravel 12 dengan autentikasi session dan dua role: `admin` dan `user`.

## Fitur

- Login menggunakan username, registrasi, ingat saya, dan logout.
- Username unik (3–50 karakter), tidak membedakan huruf besar/kecil; email tetap menjadi informasi akun.
- Registrasi publik selalu menghasilkan role `user`.
- Dashboard terpisah sesuai role; akses tanpa izin menghasilkan HTTP 403.
- Admin dapat mencari pengguna, melihat daftar dengan pagination, dan mengubah role pengguna lain.
- Role akun sendiri tidak dapat diubah dari halaman admin.
- Password di-hash, proteksi CSRF, regenerasi session saat login, dan pembatasan percobaan login.
- Tampilan Blade responsif berbahasa Indonesia. CSS lokal, tidak membutuhkan build npm.
- Menu Broadcast WhatsApp untuk user: buat/edit template dengan preview, kemudian buat/edit draft broadcast.

## Tampilan ShopAds

UI diadaptasi dari source lokal `D:\1 Kerja\shop-myads`: stylesheet `auth-portal.css`, logo ShopAds, ikon sidebar, dan script navigasi mobile. Login mengikuti layout dua kolom merah/putih; registrasi, dashboard, dan daftar pengguna memakai identitas visual yang sama. Penyesuaian komponen akun ada di `public/css/app.css`.

Login tetap menggunakan username dan autentikasi Laravel lokal. Menu menyesuaikan role admin/user dan fitur yang tersedia. Font Plus Jakarta Sans dan Space Grotesk dimuat dari Bunny Fonts, dengan fallback Arial jika tidak tersedia.

## Broadcast WhatsApp

Menu **Template WhatsApp** dan **Broadcast WhatsApp** terpisah pada sidebar user. Admin meninjau pengajuan di menu **Persetujuan Template**. Susunan builder diadaptasi dari `shop-myads/resources/views/campaign-wa-template/partials/builder-form.blade.php`, menggunakan stylesheet ShopAds yang sudah disalin dan penyesuaian pada `public/css/whatsapp.css`.

1. User membuat template: nama unik per user, bahasa, display name opsional (kosong secara default), header teks/gambar, pesan, footer, dan tombol.
2. Ajukan template. Status awal adalah **Menunggu persetujuan**. Admin melihat preview, menyetujui, atau menolak dengan alasan.
3. User membuka menu **Broadcast WhatsApp**, memilih template miliknya yang **Disetujui**, lalu mengisi nama broadcast dan nomor penerima. Nomor `08` dinormalisasi menjadi `628`, duplikat disatukan, maksimal 1.000 nomor unik.
4. Simpan draft dan periksa preview serta ringkasan penerimanya.

Persetujuan template dilakukan oleh admin aplikasi. Pengiriman pesan dan koneksi ke provider WhatsApp belum tersedia. Persetujuan admin aplikasi berbeda dari persetujuan provider. Setiap edit template menaikkan revisi, menghapus persetujuan sebelumnya, dan mengajukan ulang. Draft yang memakai template tersebut terblokir sampai admin menyetujui revisi baru. Keputusan admin memeriksa revisi dan status dalam transaksi, sehingga form peninjauan yang sudah kedaluwarsa tidak dapat menyetujui konten baru. Batas 550 karakter pesan dan 10 tombol adalah batas aplikasi yang mengikuti builder referensi; validasi provider perlu disesuaikan ketika integrasi ditambahkan.

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
| `/login`, `/register` | Tamu |
| `/dashboard` | Pengguna login, diarahkan sesuai role |
| `/admin/dashboard` | Admin |
| `/admin/users` | Admin |
| `PATCH /admin/users/{user}/role` | Admin, untuk akun lain |
| `/user/dashboard` | User |
| `POST /logout` | Pengguna login |

## Struktur utama

- `app/Models/User.php`: konstanta dan pemeriksaan role; role tidak mass assignable.
- `app/Http/Middleware/EnsureUserHasRole.php`: pembatasan akses role di server.
- `app/Http/Controllers/AuthController.php`: login, registrasi, logout.
- `app/Http/Controllers/Admin/UserController.php`: daftar, pencarian, perubahan role.
- `bootstrap/app.php`: pendaftaran middleware `role`.
- `routes/web.php`: rute dan pembatasan akses.
- `resources/views`: halaman Blade.
- `public/css/app.css`: stylesheet yang langsung dapat digunakan.

## Pengujian

```sh
php artisan test
php vendor/bin/pint --test
```

Pengujian memakai SQLite `:memory:` sehingga tidak mengubah database aplikasi. Cakupan meliputi autentikasi, validasi registrasi, rate limiting, pemisahan akses, penolakan eskalasi role, perubahan role, pencarian, pagination, dan seeder.

Referensi: [autentikasi Laravel](https://laravel.com/docs/12.x/authentication), [middleware](https://laravel.com/docs/12.x/middleware).
