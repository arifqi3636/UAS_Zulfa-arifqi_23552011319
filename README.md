# 🐠 Sistem Informasi Peternakan Lele



https://github.com/user-attachments/assets/b0d958cc-ce27-4f6e-8b65-039c31cfed40



Sistem manajemen budidaya lele modern dengan dashboard interaktif dan laporan profesional.

## 🚀 Fitur Utama

- **Dashboard Interaktif** - Monitoring real-time kondisi peternakan
- **Manajemen Kolam** - Tambah, edit, dan hapus data kolam
- **Inventori Ikan** - Tracking jumlah, ukuran, dan berat ikan
- **Manajemen Pakan** - Pencatatan pemberian pakan dan biaya
- **Monitoring Kesehatan** - Tracking kesehatan ikan dan perawatan
- **Laporan Profesional** - Export PDF dan Excel dengan format tabel

## 📋 Persyaratan Sistem

- PHP 8.1+
- MySQL 5.7+
- Web Server (Apache/Nginx)

## 🛠️ Instalasi

1. **Clone atau Download** project ini
2. **Import Database**:
   ```sql
   mysql -u root -p < lele_farming.sql
   ```
3. **Konfigurasi Database** di `config/database.php`
4. **Jalankan Server**:
   ```bash
   php -S localhost:8000
   ```
5. **Akses Sistem** di browser: `http://localhost:8000`

## 👤 Akun Default

- **Username**: admin
- **Password**: admin123
- **Email**: admin@catfish.local

## 📁 Struktur Project

```
├── admin/              # Panel admin
├── assets/             # CSS, JS, Images
├── config/             # Konfigurasi database
├── includes/           # Class dan helper functions
├── libraries/          # Third-party libraries (TCPDF, PhpSpreadsheet)
├── pages/              # Halaman utama aplikasi
├── index.php           # Halaman login
├── register.php        # Halaman registrasi
├── logout.php          # Proses logout
└── lele_farming.sql    # Schema database
```

## 📊 Modul Utama

### 1. Dashboard
- Statistik overview
- Grafik performa
- Status kolam aktif

### 2. Manajemen Kolam
- Tambah/Edit/Hapus kolam
- Tracking lokasi dan ukuran
- Status operasional

### 3. Inventori Ikan
- Input data ikan baru
- Update jumlah dan ukuran
- History perubahan

### 4. Pakan & Nutrisi
- Pencatatan pemberian pakan
- Tracking biaya
- Analisis konsumsi

### 5. Kesehatan Ikan
- Monitoring kondisi kesehatan
- Pencatatan perawatan
- Riwayat penyakit

### 6. Laporan & Export
- Laporan PDF dengan tabel
- Export Excel dengan format
- Filter berdasarkan periode

## 🔧 Teknologi

- **Backend**: PHP 8.1+ dengan PDO
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Libraries**:
  - TCPDF (PDF generation)
  - PhpSpreadsheet (Excel export)
  - Chart.js (Grafik dashboard)

## 📞 Dukungan

Untuk pertanyaan atau masalah, silakan hubungi tim development.

---

**Dibuat dengan ❤️ untuk peternak lele Indonesia**</content>
<parameter name="filePath">c:\xampp\htdocs\catfish-farming-php\README.md
