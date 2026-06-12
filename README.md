# Virus Scanner Project

Aplikasi berbasis web untuk melakukan pemindaian (scanning) file guna mendeteksi potensi ancaman atau virus. Proyek ini dikembangkan menggunakan **Laravel** sebagai framework utama.

## Fitur
- Pemindaian file secara real-time.
- Riwayat pemindaian tersimpan dalam database.
- Antarmuka yang intuitif untuk pengguna.

## Instalasi
1. Clone repositori:
   `git clone https://github.com/galangpramudito/fam-viruscanner.git`
2. Masuk ke folder: `cd viruscanner`
3. Install dependensi: `composer install`
4. Buat file environment: `cp .env.example .env`
5. Generate key: `php artisan key:generate`
6. Jalankan migrasi database: `php artisan migrate`
7. Jalankan server: `php artisan serve`

## Tech Stack
- **Framework:** Laravel
- **Database:** SQLite
- **Styling:** CSS/Blade