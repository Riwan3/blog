# Duta Damai Kalimantan Selatan - Blog System

Website resmi Duta Damai Kalimantan Selatan untuk publikasi artikel dan berita.

## Fitur

- ✅ Sistem login dengan role-based access (Admin, Editor, Kontributor)
- ✅ Manajemen artikel dengan status (Draft, Pending, Published)
- ✅ Upload gambar untuk artikel
- ✅ Sistem routing dengan URL clean
- ✅ Responsive design
- ✅ SSL/HTTPS ready

## Teknologi

- PHP 8.2
- MySQL/MariaDB
- Nginx
- Bootstrap CSS
- Let's Encrypt SSL

## Instalasi

1. Clone repository ini
2. Import database dari file `setup.sql`
3. Konfigurasi database di `src/config.php`
4. Jalankan dengan PHP built-in server atau Nginx

### Development (Local)

```bash
php -S localhost:8000 router.php
```

Akses: http://localhost:8000

### Production

Upload ke VPS dan konfigurasi Nginx sesuai dokumentasi.

## Default Login

- **Username:** admin
- **Password:** admin123

> **Penting:** Segera ubah password default setelah instalasi!

## Lisensi dan Kredit

© 2025 Duta Damai Kalimantan Selatan

Website ini menggunakan komponen dari [CMS Jawara](https://github.com/djadjoel/cmsjawara)  
© 2020 Djadjoel - MIT License

Lihat file [LICENSE](LICENSE) untuk detail lengkap.

## Kontak

Website: https://dutadamai-kalsel.my.id

---

**Catatan:** Project ini dibangun untuk keperluan organisasi Duta Damai Kalimantan Selatan.
