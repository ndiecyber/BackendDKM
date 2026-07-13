# Infrastruktur & Deployment (Dev Notes)

Dokumen ini ditujukan untuk *developer* selanjutnya agar memahami arsitektur *deployment* dari ekosistem aplikasi Masjid Kassiti (DKM).

## Arsitektur Utama
Sistem ini menggunakan arsitektur **Lightweight Docker dengan GitHub Container Registry (GHCR)**.

- **Tanpa Build di VPS:** VPS tidak melakukan proses `npm install` atau `composer install`. Semua proses komputasi berat (Build) dilakukan di GitHub Actions.
- **GHCR:** Hasil build dibungkus menjadi Docker Image dan disimpan di GHCR. VPS hanya bertugas mengunduh (*pull*) image tersebut dan menjalankannya.
- **Orkestrasi:** Semua container dijalankan menggunakan satu file `docker-compose.yml` induk yang berada di VPS (biasanya di `/opt/lexa`).

## Jaringan Internal & Reverse Proxy
Kita menggunakan **Nginx Proxy Manager (NPM)** yang berjalan di dalam Docker untuk mengatur semua domain dan SSL (HTTPS).

- NPM bertindak sebagai gerbang utama (Port 80 & 443).
- Semua aplikasi (WebDKM, TabunganQurban, BackendDKM) berada di dalam satu jaringan Docker yang sama (`lexa_default`).
- Anda bisa menambahkan domain baru lewat *Dashboard NPM* (Port 81) dan meneruskannya ke nama container aplikasi Anda tanpa perlu membuka port aplikasi tersebut ke publik.

## Database (PostgreSQL Global)
Sistem ini menggunakan **satu container PostgreSQL** yang berfungsi sebagai server database global untuk seluruh aplikasi di VPS.

### Aturan Menambah Database Baru (Standar Keamanan)
Jika Anda menambahkan aplikasi web baru yang membutuhkan database, **DILARANG** menggunakan akun `dkm_user` yang sama. Anda harus membuat lingkungan yang terisolasi (*Isolated*):

1. Masuk ke console PostgreSQL (bisa via DBeaver atau eksekusi ke dalam container).
2. Buat database baru: `CREATE DATABASE nama_web_baru;`
3. Buat user baru: `CREATE USER user_web_baru WITH ENCRYPTED PASSWORD 'password_rahasia';`
4. Berikan izin eksklusif: `GRANT ALL PRIVILEGES ON DATABASE nama_web_baru TO user_web_baru;`

Gunakan `user_web_baru` tersebut di file `.env` aplikasi Anda (dengan `DB_HOST=db`). Ini memastikan jika satu web diretas, database web lainnya tetap aman.

## Panduan Menambah Web/Microservice Baru
Jika Anda ingin men-deploy aplikasi ke-4 ke dalam server ini, ikuti langkah berikut:

1. **Di Repository Baru:**
   - Tambahkan `Dockerfile` dan sesuaikan dengan bahasa pemrograman yang dipakai (pastikan seringan mungkin menggunakan Alpine).
   - Buat `.github/workflows/deploy.yml` (bisa mencontek dari repo DKM yang ada) untuk build & push ke GHCR.
   - Tambahkan *GitHub Secrets* (`VPS_IP`, `VPS_USERNAME`, `VPS_SSH_KEY`).

2. **Di VPS:**
   - Edit file `docker-compose.yml` induk di `/opt/lexa`.
   - Tambahkan *service* baru yang menunjuk ke image GHCR aplikasi baru tersebut.
   - Jalankan `docker compose up -d` untuk menghidupkan container baru.

3. **Di Nginx Proxy Manager:**
   - Login ke dashboard NPM.
   - Tambahkan *Proxy Host* baru, masukkan domainnya, dan arahkan (*forward*) ke nama container yang baru saja Anda buat di langkah 2.
   - Aktifkan SSL Let's Encrypt.
