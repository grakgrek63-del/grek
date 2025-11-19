# Sistem Manajemen Majelis Dzikir

Aplikasi web berbasis PHP 7 untuk mengelola majelis dzikir, penugasan petugas pentawajuh, dan pelaporan dengan integrasi peta interaktif.

## Fitur Utama

- 🗺️ **Peta Interaktif**: Integrasi OpenStreetMap dengan Leaflet.js untuk visualisasi wilayah dan lokasi
- 👥 **Manajemen Pengguna**: Sistem login dengan role admin dan regional
- 📍 **CRUD Wilayah**: Kelola wilayah dengan polygon yang dapat diedit langsung di peta
- 🕌 **CRUD Majelis Dzikir**: Kelola data majelis dzikir dengan lokasi GPS
- 👨‍💼 **CRUD Petugas**: Kelola data petugas pentawajuh
- 📅 **Sistem Penugasan**: Penugasan manual dan otomatis berdasarkan jarak terdekat
- 🤖 **Assignment Engine**: Algoritma optimasi penugasan dengan constraint bahwa satu petugas hanya bisa ditugaskan ke satu majelis per hari
- 📊 **Laporan**: Laporan per wilayah dengan export Excel menggunakan PhpSpreadsheet
- 📱 **Responsive Design**: UI modern dengan Bootstrap 5, mobile-friendly
- 🔄 **Real-time Updates**: Update data real-time dengan AJAX

## Teknologi yang Digunakan

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ / 8.0+
- **Frontend**: HTML5, JavaScript (ES6+), Bootstrap 5
- **Mapping**: OpenStreetMap dengan Leaflet.js
- **Excel Export**: phpoffice/phpspreadsheet
- **Authentication**: Session-based PHP auth dengan password hashing

## Persyaratan Server

- PHP 7.4 atau lebih tinggi
- MySQL 5.7+ atau MariaDB 10.2+
- Web server (Apache dengan mod_rewrite atau Nginx)
- Ekstensi PHP: JSON, MySQL, GD
- Composer untuk dependency management

## Instalasi

### 1. Clone Repository

```bash
git clone <repository-url>
cd majelis
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Database Setup

1. Buat database baru di MySQL:
```sql
CREATE DATABASE majelis_dzikir;
```

2. Import file database:
```bash
mysql -u username -p majelis_dzikir < majelis/database.sql
```

### 4. Konfigurasi Database

Edit file `majelis/config/database.php` dan sesuaikan dengan kredensial database Anda:

```php
private $host = 'localhost';
private $db_name = 'majelis_dzikir';
private $username = 'your_db_username';
private $password = 'your_db_password';
```

### 5. Set Permissions

```bash
chmod 755 -R majelis/
chmod 777 majelis/logs/
```

### 6. Konfigurasi Web Server

#### Apache:

Tambahkan konfigurasi berikut di `.htaccess`:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ majelis/index.php [QSA,L]
```

#### Nginx:

```nginx
location / {
    try_files $uri $uri/ /majelis/index.php?$query_string;
}
```

## Akun Default

**Administrator:**
- Username: `administrator`
- Password: `Rahasia513`

## Struktur Database

### Tabel: wilayah
- Menyimpan data wilayah dengan polygon GeoJSON
- ID, nama_wilayah, polygon, created_at, updated_at

### Tabel: majelis_dzikir
- Menyimpan data majelis dzikir
- ID, wilayah_id, nama_majelis, alamat, latitude, longitude

### Tabel: petugas_pentawajuh
- Menyimpan data petugas
- ID, nama, telepon, latitude, longitude, id_wilayah

### Tabel: penugasan
- Menyimpan data penugasan dengan distance calculation otomatis
- ID, id_petugas, id_majelis, tanggal, jarak_km, tugas

### Tabel: user
- Menyimpan data user dengan role-based access
- ID, username, password (hashed), role, wilayah_id

## Penggunaan

### Login

1. Buka aplikasi di browser
2. Login dengan akun administrator atau user regional

### Dashboard

- **Peta Interaktif**: Lihat semua lokasi majelis dan petugas
- **Filter**: Filter data berdasarkan wilayah dan tanggal
- **Statistik**: Lihat ringkasan data real-time
- **Penugasan**: Tabel penugasan dengan fitur CRUD

### Manajemen Data

#### Wilayah
- Tambah wilayah baru dengan menggambar polygon di peta
- Edit nama dan polygon wilayah
- Hapus wilayah (dengan validasi dependency)

#### Majelis Dzikir
- Tambah majelis dengan klik di peta untuk menentukan lokasi
- Edit data majelis termasuk lokasi
- Hapus majelis (dengan validasi penugasan)

#### Petugas Pentawajuh
- Tambah petugas dengan lokasi GPS
- Edit data petugas
- Hapus petugas (dengan validasi penugasan masa depan)

### Penugasan

#### Manual
- Pilih petugas dan majelis yang tersedia
- Sistem otomatis menampilkan jarak
- Validasi: satu petugas per majelis per hari

#### Otomatis
- Pilih wilayah dan rentang tanggal
- Pilih jenis penugasan (harian/mingguan)
- Sistem otomatis menugaskan petugas ke majelis terdekat
- Algoritma optimasi dengan constraint handling

### Laporan

- Pilih wilayah dan rentang tanggal
- Lihat data penugasan dalam format matrix
- Export ke Excel dengan format yang sudah ditentukan
- Print laporan langsung dari browser

## API Endpoints

### Authentication
- `POST /majelis/api/auth.php?action=login` - Login user
- `POST /majelis/api/auth.php?action=logout` - Logout user
- `GET /majelis/api/auth.php?action=check` - Check authentication status

### Wilayah
- `GET /majelis/api/wilayah.php` - Get all regions
- `GET /majelis/api/wilayah.php?id={id}` - Get single region
- `POST /majelis/api/wilayah.php` - Create region
- `PUT /majelis/api/wilayah.php?id={id}` - Update region
- `DELETE /majelis/api/wilayah.php?id={id}` - Delete region

### Majelis
- `GET /majelis/api/majelis.php?wilayah_id={id}` - Get majelis by region
- `GET /majelis/api/majelis.php?id={id}` - Get single majelis
- `POST /majelis/api/majelis.php` - Create majelis
- `PUT /majelis/api/majelis.php?id={id}` - Update majelis
- `DELETE /majelis/api/majelis.php?id={id}` - Delete majelis

### Petugas
- `GET /majelis/api/petugas.php?wilayah_id={id}` - Get petugas by region
- `GET /majelis/api/petugas.php?id={id}` - Get single petugas
- `POST /majelis/api/petugas.php` - Create petugas
- `PUT /majelis/api/petugas.php?id={id}` - Update petugas
- `DELETE /majelis/api/petugas.php?id={id}` - Delete petugas

### Penugasan
- `GET /majelis/api/penugasan.php?wilayah_id={id}&date={YYYY-MM-DD}` - Get assignments
- `GET /majelis/api/penugasan.php?action=available&date={YYYY-MM-DD}` - Get available petugas
- `POST /majelis/api/penugasan.php` - Create assignment
- `POST /majelis/api/penugasan.php?action=auto_assign` - Generate auto assignments
- `PUT /majelis/api/penugasan.php?id={id}` - Update assignment
- `DELETE /majelis/api/penugasan.php?id={id}` - Delete assignment

## Algoritma Penugasan Otomatis

Sistem menggunakan algoritma greedy optimization:

1. **Input**: Daftar petugas dan majelis dalam wilayah
2. **Distance Calculation**: Hitung jarak Haversine antar lokasi
3. **Scoring**: Beri skor setiap kemungkinan penugasan berdasarkan:
   - Jarak (semakin dekat semakin baik)
   - Load balancing (distribusi penugasan merata)
   - Prioritas majelis (jika ada)
4. **Assignment**: Pilih penugasan dengan skor tertinggi
5. **Constraint Handling**: Pastikan satu petugas hanya satu majelis per hari

## Security Considerations

- Password hashing menggunakan PHP `password_hash()`
- SQL injection prevention dengan prepared statements
- Session timeout management
- Role-based access control
- CSRF protection pada forms
- Input sanitization dan validation

## Data Awal

Sistem sudah dilengkapi dengan data awal:

- **5 Wilayah**: Indonesia BKMZ Jakarta, BKMZ Kalteng 1 & 2, Malaysia KL, Singapore Central
- **23 Majelis Dzikir**: Tersebar di semua wilayah
- **26 Petugas Pentawajuh**: Dengan koordinat dan informasi kontak
- **1 User Admin**: Username: administrator, Password: Rahasia513

Data awal akan otomatis diimport saat menjalankan `database.sql`.

## Troubleshooting

### Error Database Connection
- Pastikan kredensial database benar di `majelis/config/database.php`
- Cek apakah MySQL server running
- Verifikasi database exists

### Map Not Loading
- Pastikan koneksi internet stabil (OpenStreetMap memerlukan koneksi)
- Cek console browser untuk JavaScript errors
- Verifikasi Leaflet.js loaded correctly

### Export Excel Not Working
- Pastikan PhpSpreadsheet terinstall dengan `composer install`
- Cek file permissions di direktori vendor
- Verifikasi ekstensi PHP Zip enabled