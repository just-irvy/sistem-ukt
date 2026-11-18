# Sistem Informasi Pembayaran Kuliah & UKT

Aplikasi web untuk membantu pengelolaan tagihan Uang Kuliah Tunggal (UKT), pembuatan Virtual Account, dan pencatatan hasil pembayaran mahasiswa.

> **Status pengembangan saat ini:** backend UC-08 dan integrasi Midtrans Sandbox telah diuji end-to-end. Layanan pembuatan BCA Virtual Account untuk UC-07 (PS-08) telah tersedia. Implementasi PS-06 (penetapan tagihan) serta halaman UI-03 dan UI-06 masih menjadi pekerjaan lanjutan. README ini menjelaskan kondisi proyek berdasarkan implementasi yang sudah dikerjakan, bukan mengklaim seluruh modul sudah selesai.

---

## 1. Gambaran Sistem

Sistem mengelola proses pembayaran UKT dengan alur umum berikut:

1. Bagian Keuangan menetapkan tagihan UKT mahasiswa berdasarkan semester.
2. Mahasiswa melihat tagihan, nominal, batas waktu, dan status pembayaran.
3. Mahasiswa meminta Virtual Account untuk membayar tagihan.
4. Midtrans memproses pembayaran melalui BCA Virtual Account pada lingkungan Sandbox.
5. Midtrans mengirim notifikasi pembayaran ke endpoint Laravel.
6. Sistem memverifikasi transaksi, mencocokkan nomor VA dan nominal dengan tagihan, lalu menyimpan hasil rekonsiliasi.

### Aktor utama

- **Mahasiswa:** melihat tagihan dan melakukan pembayaran.
- **Bagian Keuangan:** menetapkan dan memantau tagihan UKT.
- **Bank/payment gateway:** memproses pembayaran dan mengirimkan notifikasi transaksi.
- **Sistem:** menyimpan tagihan, membuat VA, memverifikasi notifikasi, dan merekonsiliasi pembayaran.

## 2. Lingkup Implementasi dan Status

| Kode | Fungsi | Status berdasarkan implementasi saat ini |
|---|---|---|
| UC-06 / PS-06 | Menetapkan tagihan UKT | Belum diimplementasikan pada aplikasi |
| UC-07 / PS-08 | Membuat Virtual Account pembayaran UKT | Service backend Midtrans sudah tersedia dan diuji |
| UI-03 | Halaman tagihan mahasiswa | Belum dibuat |
| UI-06 | Halaman penetapan tagihan Bagian Keuangan | Belum dibuat |
| UC-08 | Rekonsiliasi pembayaran dan penandaan transaksi bermasalah | Backend dan webhook Sandbox sudah diuji end-to-end |

Catatan: pembuatan VA dan rekonsiliasi sudah dapat diuji melalui service/API. Hal tersebut tidak berarti halaman UI-03 dan UI-06 sudah tersedia.

## 3. Teknologi

Lingkungan pengembangan yang digunakan pada saat implementasi:

- PHP 8.3.x
- Laravel 13
- Composer 2.x
- MySQL
- Node.js 22.x dan npm 10.x untuk aset frontend Laravel
- Midtrans PHP SDK (`midtrans/midtrans-php`)
- Midtrans Core API Sandbox untuk BCA Virtual Account
- ngrok untuk meneruskan notifikasi dari Midtrans ke server lokal

Versi aktual di komputer masing-masing dapat diperiksa dengan:

```powershell
php -v
composer --version
node -v
npm -v
```

## 4. Struktur Modul Penting

Struktur yang relevan untuk pekerjaan pembayaran UKT:

```text
app/
├── Http/
│   └── Controllers/
│       └── Api/
│           ├── MidtransNotificationController.php
│           └── RekonsiliasiController.php
├── Models/
│   ├── Mahasiswa.php
│   ├── TagihanUKT.php
│   └── TransaksiRekonsiliasi.php
└── Services/
    ├── MidtransService.php
    └── RekonsiliasiService.php

config/
└── midtrans.php

database/
└── migrations/

routes/
└── api.php
```

Folder dan file lain dari kerangka Laravel tetap digunakan. Jika struktur pada branch terbaru berubah, ikuti isi repository, bukan hanya diagram di README ini.

### Tabel database utama

| Tabel | Fungsi |
|---|---|
| `mahasiswa` | Menyimpan identitas dasar mahasiswa yang diperlukan oleh proses tagihan |
| `tagihan_ukt` | Menyimpan NIM, semester, nominal, batas waktu, status pembayaran, dan nomor VA |
| `transaksi_rekonsiliasi` | Menyimpan transaksi bank, referensi transaksi, nominal, waktu transaksi, dan status rekonsiliasi |

Relasi utama: satu mahasiswa dapat memiliki banyak tagihan UKT; satu tagihan dapat memiliki beberapa catatan transaksi rekonsiliasi. `id_tagihan` pada transaksi dapat bernilai `NULL` ketika nomor VA tidak ditemukan.

## 5. Persiapan Menjalankan Proyek

Petunjuk berikut ditulis untuk Windows dengan Laragon/PowerShell. Sesuaikan nama database dan kredensial database pada komputer masing-masing.

### Prasyarat

Pastikan sudah terpasang:

- Laragon atau MySQL lokal yang aktif.
- PHP yang memenuhi kebutuhan Laravel 13.
- Composer.
- Node.js dan npm.
- Git.

### Langkah instalasi setelah clone

1. Buka PowerShell dan masuk ke folder hasil clone.
2. Pasang dependency PHP:

```powershell
composer install
```

3. Buat file konfigurasi lokal dari template:

```powershell
Copy-Item .env.example .env
```

Jika file `.env` sudah ada, jangan menimpanya. File `.env` berisi konfigurasi pribadi pada komputer masing-masing.

4. Buat application key:

```powershell
php artisan key:generate
```

5. Buat database MySQL, misalnya `sistem_ukt`, lalu sesuaikan bagian database di `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistem_ukt
DB_USERNAME=root
DB_PASSWORD=
```

6. Jalankan migration:

```powershell
php artisan migrate
```

7. Pasang dan build aset frontend Laravel:

```powershell
npm install
npm run build
```

8. Jalankan aplikasi:

```powershell
php artisan serve
```

Buka `http://127.0.0.1:8000` pada browser.

> Migration membuat struktur tabel. Data mahasiswa/tagihan untuk demonstrasi tidak otomatis tersedia kecuali repository menyediakan seeder yang sesuai. Periksa `database/seeders` dan `DatabaseSeeder.php` sebelum menjalankan `php artisan db:seed`.

## 6. Konfigurasi Midtrans Sandbox

Integrasi pembayaran backend menggunakan Midtrans Core API. Konfigurasi disimpan di `.env` lokal dan dibaca melalui `config/midtrans.php`.

Tambahkan atau sesuaikan variabel berikut pada `.env` lokal:

```env
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_MERCHANT_ID=MERCHANT_ID_SANDBOX
MIDTRANS_SERVER_KEY=SERVER_KEY_SANDBOX
MIDTRANS_CLIENT_KEY=
```

Keterangan:

- `MIDTRANS_IS_PRODUCTION=false` memastikan aplikasi menggunakan lingkungan Sandbox.
- `MIDTRANS_MERCHANT_ID` diisi Merchant ID dari akun Sandbox yang digunakan.
- `MIDTRANS_SERVER_KEY` diisi Sandbox Server Key. Key ini rahasia dan tidak boleh dibagikan melalui GitHub atau chat.
- `MIDTRANS_CLIENT_KEY` dapat dikosongkan untuk alur backend Core API yang saat ini digunakan, selama tidak ada integrasi frontend yang memerlukannya.

Setelah mengubah konfigurasi:

```powershell
php artisan config:clear
```

Restart `php artisan serve` jika sedang berjalan.

### Endpoint API yang tersedia

| Method | Endpoint | Kegunaan |
|---|---|---|
| `POST` | `/api/reconciliation/test` | Endpoint pengujian rekonsiliasi lokal |
| `POST` | `/api/midtrans/notification` | Endpoint penerima notifikasi pembayaran Midtrans |

Untuk menguji pembuatan VA dari Tinker (gunakan tagihan yang benar-benar masih `Belum Dibayar` dan belum memiliki VA):

```php
$hasilVA = app(App\Services\MidtransService::class)
    ->buatVirtualAccount(ID_TAGIHAN);

dump($hasilVA);
```

Ganti `ID_TAGIHAN` dengan ID tagihan yang valid. Jangan menjalankan method ini berulang kali pada tagihan yang sudah memiliki VA karena service memang menolak pembuatan VA kedua untuk tagihan yang sama.

### Notifikasi Midtrans pada server lokal

Midtrans tidak dapat mengakses `127.0.0.1` secara langsung. Untuk pengujian lokal, jalankan ngrok pada port Laravel:

```powershell
ngrok http 8000
```

Gunakan alamat HTTPS yang diberikan ngrok sebagai **Payment Notification URL** di dashboard Midtrans Sandbox, dengan path:

```text
https://ALAMAT-NGROK/api/midtrans/notification
```

Terminal Laravel dan ngrok harus tetap berjalan selama pengujian. URL ngrok gratis dapat berubah ketika tunnel dibuat ulang, sehingga URL pada dashboard perlu diperbarui.

## 7. Pengujian

Jalankan seluruh pengujian otomatis dari root project:

```powershell
php artisan test
```

Hasil pengujian terakhir yang tercatat selama pengembangan:

```text
Tests:    9 passed (25 assertions)
```

Tujuh test pada `RekonsiliasiServiceTest` mencakup:

1. Pembayaran sesuai mengubah tagihan menjadi lunas.
2. VA tidak ditemukan menandai transaksi tidak sesuai.
3. Nominal kurang menandai transaksi tidak sesuai.
4. Nominal berlebih menandai transaksi tidak sesuai.
5. Tagihan yang sudah lunas tidak diproses sebagai pembayaran baru.
6. Referensi transaksi duplikat ditolak.
7. Format nominal tidak valid menghasilkan exception.

Hasil tersebut adalah hasil pengujian terakhir yang pernah dijalankan. Jalankan kembali `php artisan test` setelah menarik perubahan terbaru dari GitHub.

## 8. Alur Bisnis Rekonsiliasi (UC-08)

1. Midtrans mengirim notifikasi ke `POST /api/midtrans/notification`.
2. Laravel memvalidasi struktur payload dan identitas merchant.
3. Sistem memverifikasi signature. Pada implementasi saat ini, jika signature tidak cocok, sistem meminta status transaksi langsung ke Midtrans melalui GET Status API menggunakan Server Key, lalu mencocokkan data penting sebelum melanjutkan.
4. Sistem mengambil nomor VA, referensi transaksi, nominal, dan waktu transaksi dari data yang telah diverifikasi.
5. `RekonsiliasiService` mencari tagihan berdasarkan nomor VA.
6. Sistem membandingkan nominal pembayaran dengan nominal tagihan dan memeriksa status tagihan.
7. Jika sesuai, status tagihan menjadi `Lunas` dan transaksi dicatat sebagai `Sesuai`.
8. Jika tidak sesuai, transaksi dicatat sebagai `Tidak Sesuai` dan tagihan tidak dilunaskan.
9. Referensi transaksi yang sudah pernah diproses tidak dicatat kembali.

## 9. Catatan untuk Anggota Kelompok

Sebelum mengerjakan fitur, baca bagian berikut secara berurutan:

1. README ini untuk memahami tujuan proyek, instalasi, dan status implementasi.
2. Dokumen Modul 1 untuk kebutuhan pengguna dan system requirement.
3. Dokumen Modul 2 untuk use case, activity diagram, sequence diagram, class diagram, PDM, dan pseudo-code.
4. Kode di `app/Models`, `app/Services`, `app/Http/Controllers`, `routes`, dan `database/migrations`.

Jangan mengubah nama tabel, kolom, route, class, atau method tanpa memeriksa dampaknya pada bagian lain. Jika mengubah rancangan SRS/SDD, diskusikan dengan anggota kelompok dan perbarui dokumentasinya.

## 10. Panduan Kolaborasi GitHub

Gunakan branch masing-masing agar pekerjaan teman tidak saling menimpa.

### A. Aturan sederhana kolaborasi

- `main` digunakan sebagai branch stabil.
- Setiap anggota mengerjakan fitur pada branch sendiri.
- Jangan mengerjakan perubahan langsung di `main`.
- Sebelum mulai bekerja, tarik perubahan terbaru dari `main`.
- Commit dibuat dengan pesan yang menjelaskan perubahan.
- Gabungkan pekerjaan ke `main` melalui Pull Request (PR) setelah diperiksa.
- Jangan pernah memasukkan `.env`, Server Key, password database, Authtoken ngrok, atau kredensial lain ke repository.

Contoh nama branch:

```text
feature/ahe-uc06-penetapan-tagihan
feature/ahe-uc07-virtual-account
feature/nama-anggota-nama-fitur
fix/perbaikan-validasi-tagihan
```

### B. Pemilik proyek: push pertama ke GitHub

1. Buat repository baru di GitHub. Untuk langkah awal yang paling sederhana, buat repository kosong tanpa menambahkan README, `.gitignore`, atau License dari GitHub karena file proyek lokal sudah tersedia.
2. Buka PowerShell pada folder proyek:

```powershell
cd D:\laragon\www\sistem-ukt
```

3. Periksa status Git dan remote:

```powershell
git status
git remote -v
git branch --show-current
```

4. Pastikan `.env` tidak ikut dilacak Git:

```powershell
git check-ignore -v .env
git ls-files .env
```

Perintah kedua seharusnya tidak menampilkan `.env`. Jika `.env` ternyata sudah pernah masuk tracking Git, keluarkan dari tracking tanpa menghapus file lokal:

```powershell
git rm --cached .env
```

Pastikan `.gitignore` mengabaikan `.env`, `vendor/`, `node_modules/`, dan file hasil runtime Laravel.

5. Jika repository lokal belum diinisialisasi Git:

```powershell
git init
```

6. Pastikan branch utama bernama `main`:

```powershell
git branch -M main
```

7. Tambahkan dan commit file proyek:

```powershell
git add .
git status
```

Periksa daftar file pada `git status` sekali lagi. Pastikan `.env`, file rahasia, `vendor/`, dan `node_modules/` tidak ikut staged. Jika sudah aman:

```powershell
git commit -m "chore: initial project setup"
```

8. Hubungkan repository GitHub. Ganti URL dengan URL repository milik kelompok:

```powershell
git remote add origin https://github.com/USERNAME/NAMA-REPOSITORY.git
git push -u origin main
```

Jika `git remote -v` sebelumnya sudah menampilkan `origin`, jangan menambahkan remote kedua. Periksa URL-nya dan gunakan remote yang sudah ada.

> Jika repository GitHub sudah berisi commit awal (misalnya README yang dibuat dari halaman GitHub), jangan langsung memaksa push. Sinkronkan riwayat repository terlebih dahulu atau gunakan repository kosong untuk menghindari konflik riwayat.

### C. Anggota lain: clone pertama kali

Bagikan URL repository kepada anggota kelompok. Pada komputer anggota tersebut, buka PowerShell dan jalankan:

```powershell
git clone https://github.com/USERNAME/NAMA-REPOSITORY.git
cd NAMA-REPOSITORY
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Kemudian:

1. Buat database MySQL lokal, misalnya `sistem_ukt`.
2. Atur koneksi database di `.env` miliknya sendiri.
3. Isi konfigurasi Midtrans Sandbox miliknya sendiri jika perlu menguji pembayaran; jangan meminta atau memakai `.env` milik anggota lain.
4. Jalankan migration dan aset frontend:

```powershell
php artisan migrate
npm install
npm run build
php artisan serve
```

Jika repository menyediakan seeder yang sesuai, anggota dapat menjalankan `php artisan db:seed`. Periksa isi seeder terlebih dahulu. Jangan menjalankan seeder yang belum diperiksa pada database yang berisi data penting.

### D. Anggota lain: mulai mengerjakan fitur

Setelah clone dan instalasi selesai:

```powershell
git switch main
git pull origin main
git switch -c feature/nama-anggota-nama-fitur
```

Lakukan perubahan pada branch tersebut. Setelah selesai:

```powershell
git status
git add .
git commit -m "feat: deskripsi perubahan"
git push -u origin feature/nama-anggota-nama-fitur
```

Buka GitHub, buat **Pull Request** dari branch fitur menuju `main`, lalu minta anggota lain memeriksa perubahan sebelum merge.

### E. Pemilik proyek: mengambil pekerjaan teman (pull)

Sebelum mengambil pekerjaan teman, simpan perubahan lokal terlebih dahulu:

```powershell
git status
```

Jika tidak ada perubahan lokal yang belum di-commit:

```powershell
git switch main
git pull origin main
```

Jika sedang mengerjakan branch sendiri dan ingin mengambil pembaruan terbaru dari `main`:

```powershell
git fetch origin
git merge origin/main
```

Jika muncul konflik, buka file yang ditandai Git, selesaikan konflik secara manual, lalu:

```powershell
git add .
git commit -m "chore: resolve merge conflict"
```

Jangan menggunakan `git push --force` pada branch bersama atau `main` tanpa koordinasi kelompok.

## 11. Pembagian Tanggung Jawab dan Koordinasi

Sebelum mulai mengerjakan, sepakati bersama siapa yang bertanggung jawab atas setiap UC, file, dan dokumen. Pembagian tugas pada SRS/SDD menjadi acuan awal. Jangan menganggap semua fitur yang tercantum dalam dokumen sudah selesai di aplikasi.

Untuk lingkup AHE yang sedang dikerjakan:

- UC-06: PS-06 penetapan tagihan dan UI-06 halaman penetapan tagihan.
- UC-07: PS-08 pembuatan Virtual Account dan UI-03 halaman tagihan mahasiswa.
- UC-08: backend rekonsiliasi dan integrasi notifikasi pembayaran yang sudah diuji.

Catat perubahan yang dibuat, file yang diubah, cara menjalankan, dan hasil pengujian pada Pull Request agar anggota lain memahami pekerjaan yang digabungkan.

## 12. Keamanan dan Hal yang Tidak Boleh Diunggah

**Jangan commit atau mengirimkan file berikut ke GitHub: **

- `.env` dan file konfigurasi yang berisi kredensial.
- Midtrans Server Key dan Client Key rahasia.
- Authtoken ngrok.
- Password MySQL atau akun aplikasi.
- Log yang mengandung payload sensitif atau kredensial.
- File hasil build/dependency yang seharusnya dihasilkan ulang, seperti `vendor/` dan `node_modules/`.

Jika Server Key atau Authtoken pernah terunggah ke repository publik, segera rotasi/revoke credential tersebut melalui dashboard penyedia terkait. Menghapus file dari commit terbaru saja tidak cukup untuk menghilangkan rahasia dari riwayat Git.

---

## 13. Informasi Pengembangan

- Proyek: Sistem Informasi Pembayaran Kuliah & UKT
- Institusi: Fakultas Ilmu Komputer, Universitas Brawijaya
- Framework: Laravel 13
- Database: MySQL
- Integrasi pembayaran: Midtrans Core API Sandbox (BCA Virtual Account)
- Lingkungan pengembangan: Windows / Laragon
- Folder kerja lokal yang digunakan saat pengembangan: `D:\laragon\www\sistem-ukt`

**Catatan akhir:** README ini perlu diperbarui setiap kali PS-06, UI-03, dan UI-06 selesai diimplementasikan, serta ketika route, struktur database, atau cara instalasi berubah.
