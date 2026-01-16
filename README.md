# Sistem Informasi Manajemen Antrian (Queue Management System)

![Laravel](https://img.shields.io/badge/Laravel-11.x-red?style=for-the-badge&logo=laravel)
![Vue.js](https://img.shields.io/badge/Vue.js-3.x-green?style=for-the-badge&logo=vue.js)
![Tailwind CSS](https://img.shields.io/badge/Tailwind-3.x-blue?style=for-the-badge&logo=tailwind-css)
![MySQL](https://img.shields.io/badge/Database-MySQL-orange?style=for-the-badge&logo=mysql)

Sistem Manajemen Antrian Berbasis Web yang dikembangkan untuk mengotomatisasi proses antrian manual pada instansi pelayanan publik. Sistem ini dirancang untuk menggantikan tiket manual dengan tiket digital (thermal print), mendukung pemanggilan suara otomatis (*Text-to-Speech*), dan menyediakan tampilan layar ruang tunggu yang informatif.

Proyek ini dikembangkan sebagai bagian dari **Praktek Kerja Lapangan (PKL)** Mahasiswa Fakultas Ilmu Komputer, Universitas Brawijaya.

---

## 🌟 Fitur Unggulan

### 1. Modul Kiosk (Anjungan Peserta)
- **Input Data Peserta:** Mewajibkan input Nama dan NRP sebelum mengambil tiket.
- **UI Ramah Lansia:** Tombol besar, font jelas, dan input angka otomatis (*numeric keypad*) untuk kemudahan akses rentang usia 20-60+ tahun.
- **Auto-Print:** Integrasi langsung dengan printer thermal (58mm/80mm) menggunakan CSS Print khusus.
- **Cetak Struk:** Struk antrian berisi Nomor, Nama, NRP, dan Waktu Kedatangan.

### 2. Modul Petugas (Staff Dashboard)
- **Multi-Counter:** Mendukung login untuk berbagai loket (CS, Teller, Pengaduan, dll).
- **Kontrol Penuh:** Fitur "Panggil Berikutnya" (*Call Next*), "Panggil Ulang" (*Recall*), dan "Selesai" (*Complete*).
- **Real-time Status:** Jumlah sisa antrian diperbarui secara otomatis.

### 3. Modul Display (Layar Ruang Tunggu)
- **Voice Announcement:** Pemanggilan nomor antrian menggunakan suara bahasa Indonesia ("Nomor Antrian A-001, Silakan ke Loket 1").
- **Split Screen Layout:** Menampilkan daftar antrian di sisi kiri dan Video Profil/Iklan di sisi kanan.
- **Running Text:** Baris informasi berjalan di bagian bawah layar.
- **Auto-Refresh:** Data antrian diperbarui otomatis tanpa *refresh* halaman (Polling mechanism).

---

## Teknologi yang Digunakan

- **Backend:** Laravel 11 (PHP 8.2+)
- **Frontend:** Vue.js 3 + Inertia.js
- **Styling:** Tailwind CSS
- **Database:** MySQL / MariaDB
- **Tools:** Vite (Build tool), Axios (HTTP Client)

---

## Prasyarat Instalasi (Prerequisites)

Sebelum menjalankan proyek, pastikan komputer Anda telah terinstal:
1.  **PHP** >= 8.2
2.  **Composer**
3.  **Node.js** >= 20 & **NPM**
4.  **MySQL Server** (XAMPP / Laragon)

---

## Panduan Instalasi (Step-by-Step)

Ikuti langkah ini untuk menjalankan proyek di komputer lokal (Localhost):

### 1. Persiapan Software (Prerequisites)
Pastikan di komputer kalian sudah terinstall:
* **Laragon**.
* **PHP** (Minimal versi 8.2). Cek: `php -v`
* **Composer**. Cek: `composer -v`
* **Node.js** (Minimal versi 18/20). Cek: `node -v`
* Pastikan **PostgreSQL** sudah terinstall dan berjalan.
* Pastikan driver PHP untuk Postgres sudah aktif. Cek `php.ini` dan hapus titik koma (;) pada baris:
  ```ini
  extension=pgsql
  extension=pdo_pgsql
  ```

### 2. Clone Repository
```bash
git clone [https://github.com/Erpan1945/pkl_antreanWeb.git]
cd nama-repo
```

### 3. Setup Backend (Laravel)
```bash
composer install
```
Konfigurasi .env:
1. Copy file .env.example lalu ubah namanya menjadi .env.
2. Buka file .env di VS Code, ubah bagian database menjadi seperti ini:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=db_antrian
DB_USERNAME=postgres
DB_PASSWORD=password_postgres_anda

SESSION_DRIVER=file
```

Generate Key:
```bash
php artisan key:generate
```

### 4. Setup Frontend (Vue.js)
Install library JavaScript. PENTING: Gunakan perintah ini agar tidak error versi Vite.
```bash
npm install --legacy-peer-deps
```

### 5. Setup Database
Jalankan migrasi data untuk membuat tabel dan data dummy:
```bash
php artisan migrate:fresh --seed
```

### 6. Run Web
* Terminal 1 (Backend)
```bash
php artisan serve
```

* Terminal 2 (Frontend)
```bash
npm run dev
```

---

## Cara Penggunaan & Akses
Buka browser dan akses URL berikut:

1. Kiosk (Pengambilan Tiket)
👉 http://localhost:8000/kiosk

* Pilih Layanan.
* Isi Nama & No HP pada popup.
* Klik "Cetak Tiket".

B. Dashboard Petugas
👉 http://localhost:8000/staff

* Pilih Loket (Misal: Loket 1).
* Klik "Panggil Berikutnya" atau "Panggil Ulang" (ikon lonceng).

C. Display TV
👉 http://localhost:8000/display

* PENTING: Klik tombol "MULAI SISTEM LAYAR" di tengah layar agar suara browser aktif.

---

## Konfigurasi Printer Thermal
Agar struk tercetak rapi (tidak pakai kertas A4):

1. Saat window Print muncul (Ctrl+P).
2. Pilih Printer Thermal.
3. Paper Size: 80mm / Custom.
4. Margins: None.
5. Headers and Footers: Uncheck (Hapus Centang).

---

## Troubleshooting (Masalah Umum)
* **Error "Vite manifest not found"**: Terminal npm run dev belum dijalankan.
* **Error Database**: Pastikan nama database di phpMyAdmin adalah db_antrian.
* **Tampilan Hancur/CSS Hilang**: Matikan npm run dev (Ctrl+C), lalu jalankan lagi. Hard refresh browser (Ctrl+F5).
* **Suara Display Tidak Keluar**: Pastikan sudah klik tombol "Mulai Sistem Layar" di halaman Display.

---

## Author
1. Irfan Abdurrahman
2. Putu Adelia Devani
3. Feni Nur Aisyah

