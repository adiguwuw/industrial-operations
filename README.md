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