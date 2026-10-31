# Industrial Operations — Backend

Industrial Operations (IndOps) adalah platform internal yang dirancang untuk membantu perusahaan industri mengelola proses operasional secara terintegrasi.

Project ini dikembangkan sebagai **REST API Backend** menggunakan Laravel dan menjadi fondasi untuk aplikasi web serta pengembangan mobile application di tahap berikutnya.

## 🎯 Project Overview

IndOps dikembangkan berdasarkan kebutuhan operasional industri yang membutuhkan sistem terpusat untuk mengelola:

- Keselamatan dan Kesehatan Kerja (K3)
- Pelaporan dan penanganan incident
- Manajemen pengguna dan role
- Monitoring operasional
- Notifikasi
- Data dan aktivitas operasional

Fokus pengembangan saat ini adalah **K3 Incident Management**, dengan roadmap menuju platform internal yang mencakup kebutuhan operasional, workforce, HRIS, hingga payroll.

## 🚀 Current Features

### Authentication & Authorization

- Login & logout
- Laravel Sanctum authentication
- Role-based access control
- Permission-based authorization
- Protected API endpoints

### User Management

Admin dapat mengelola pengguna melalui:

- Create user
- View user
- Update user
- Delete user
- Role assignment
- Profile photo
- Search & filtering

Role yang digunakan dalam sistem saat ini:

- Admin
- K3 Officer
- Supervisor
- Employee

### Incident Management

Pengguna dapat:

- Melaporkan incident
- Menentukan kategori incident
- Menentukan severity
- Menambahkan lokasi
- Menambahkan dokumentasi foto
- Memberikan komentar
- Melakukan investigasi
- Menyelesaikan incident
- Melihat status history incident

Sistem juga menyediakan pembatasan akses berdasarkan role dan kepemilikan incident.

### Dashboard & Statistics

Dashboard menyediakan data operasional seperti:

- Total incident
- Incident berdasarkan severity
- Incident berdasarkan status
- Incident berdasarkan kategori
- Trend incident

### Notifications

Backend menyediakan sistem notifikasi untuk mendukung informasi dan aktivitas dalam platform.

## 🏗️ Architecture

Project menggunakan pendekatan **separated frontend & backend architecture**.

```text
                    Industrial Operations
                            │
              ┌─────────────┴─────────────┐
              │                           │
        React Frontend              Laravel Backend
        (Web Application)              (REST API)
              │                           │
              └─────────────┬─────────────┘
                            │
                          MySQL

```

## Pengembangan lokal dengan Docker Compose

### Tata letak repository

Compose membangun frontend dari folder `../industrial-operations-fe`. Clone kedua repository berdampingan:

```text
Ind-Projects/
├── industrial-operations/
└── industrial-operations-fe/
```

Gunakan Docker Desktop dengan integrasi WSL 2 aktif. Jalankan perintah berikut dari folder backend (`industrial-operations`).

### Siapkan environment lokal

```bash
cp .env.docker.example .env.docker
```

Isi `APP_KEY`, `DB_USERNAME`, `DB_PASSWORD`, dan `DB_ROOT_PASSWORD` dengan nilai unik untuk environment-mu. Untuk membuat key Laravel lokal, jalankan `openssl rand -base64 32`, lalu isi `APP_KEY` dengan awalan `base64:` diikuti hasilnya. Simpan hasil key hanya di `.env.docker`; jangan kirim atau commit nilainya. `APP_URL` dapat disesuaikan dengan alamat lokal. `.env.docker` sudah diabaikan oleh Git; commit template `.env.docker.example`, bukan file lokal tersebut.

### Bangun dan jalankan container

```bash
docker compose -f compose.yaml --env-file .env.docker up -d --build
docker compose -f compose.yaml --env-file .env.docker ps
```

Pada database baru, jalankan migration dan seeder role/permission, lalu buat admin pertama. Seeder ini hanya menyiapkan role dan permission. `app:create-admin` meminta password secara tersembunyi dan hanya membuat admin jika belum ada.

```bash
docker compose -f compose.yaml --env-file .env.docker exec backend php artisan migrate --force
docker compose -f compose.yaml --env-file .env.docker exec backend php artisan db:seed --class=RolePermissionSeeder --force
docker compose -f compose.yaml --env-file .env.docker exec backend php artisan app:create-admin
```

Jangan jalankan `migrate:fresh` pada database yang berisi data. Seeder penuh juga menambahkan akun dan insiden demo; gunakan seeder khusus role di atas untuk setup ini.

### Akses dan data

- Aplikasi frontend lokal: `http://localhost:8080` (hanya dapat diakses dari mesin host).
- phpMyAdmin: `http://localhost:8081` (host lokal saja).
- Nginx Proxy Manager: `http://localhost:8082` (host lokal saja).
- Untuk domain publik, arahkan DNS ke server lalu buat Proxy Host di Nginx Proxy Manager: forward scheme HTTP, hostname frontend, port 80. Aktifkan SSL setelah domain dan port 80/443 siap.
- Halaman admin Nginx Proxy Manager dan phpMyAdmin tetap terikat ke loopback; akses jarak jauh ke halaman admin sebaiknya melalui SSH tunnel.
- Database MySQL hanya tersedia pada jaringan internal Compose, tanpa port host langsung.
- Setiap developer memiliki volume `mysql_data` sendiri; database lokal tidak otomatis dibagikan ke komputer lain.
- `docker compose ... down` menghentikan dan menghapus container, tetapi named volume tetap ada. Jangan tambahkan `-v` kecuali memang ingin menghapus volume dan data.

Jangan publikasikan `.env.docker`, password, atau `APP_KEY` ke Git maupun chat.

## Deployment untuk demo portofolio gratis

Blueprint Render di `render.yaml` membangun Dockerfile yang sudah ada lalu menjalankan Laravel sebagai server HTTP. Compose lokal tidak berubah. Saat service mulai, script menjalankan migrasi database dan mengisi role serta permission saja; script tidak membuat akun admin atau data demo.

Buat MySQL Aiven Free terlebih dahulu. Saat menyinkronkan Blueprint di Render, isi koneksi database dan `APP_KEY` pada form Render. Kamu bisa membuat key di terminal lokal dengan `docker compose exec backend php artisan key:generate --show`; simpan nilainya hanya di Render. Tambahkan sertifikat CA Aiven sebagai secret file bernama `aiven-ca.pem`. Konfigurasi Render membacanya dari `/etc/secrets/aiven-ca.pem`. Jangan masukkan kredensial atau APP_KEY ke Git atau chat.

Penyimpanan file Render Free bersifat sementara. Aplikasi menyimpan foto profil dan foto insiden di disk privat lokal, serta foto temuan dan bukti task di disk publik lokal. File tersebut dapat hilang saat service restart, redeploy, atau spin down. Gunakan deployment ini sebagai demo sampai object storage permanen dikonfigurasi.

## Deploy to Vercel

See [VERCEL_DEPLOYMENT.md](VERCEL_DEPLOYMENT.md) for the backend container setup, Aiven environment variables, migration guidance, and temporary upload-storage limits.
