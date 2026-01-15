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
- **Input Data Peserta:** Mewajibkan input Nama dan NIK/No. HP sebelum mengambil tiket.
- **UI Ramah Lansia:** Tombol besar, font jelas, dan input angka otomatis (*numeric keypad*) untuk kemudahan akses rentang usia 20-60+ tahun.
- **Auto-Print:** Integrasi langsung dengan printer thermal (58mm/80mm) menggunakan CSS Print khusus.
- **Cetak Struk:** Struk antrian berisi Nomor, Nama, NIK, dan Waktu Kedatangan.

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

### 1. Clone Repository
```bash
git clone [https://github.com/username-anda/nama-repo-antrian.git](https://github.com/username-anda/nama-repo-antrian.git)
cd nama-repo-antrian