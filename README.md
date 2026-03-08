# Mini Wallet – Backend API Documentation
Laravel REST API + MySQL + Laravel Sanctum • Last Updated: March 2026

📌 **Table of Contents**
1. [Project Overview](#-project-overview)
2. [Core Features](#-core-features)
3. [Authentication & Access Control](#-authentication--access-control)
4. [API Routing Structure](#-api-routing-structure)
5. [Tech Stack](#-tech-stack)
6. [Database Design](#-database-design)
7. [API Endpoints](#-api-endpoints)
8. [Middleware & Security](#-middleware--security)
9. [Installation & Setup](#-installation--setup)
10. [Author](#-author)

---

## 🎯 Project Overview
**Mini Wallet Backend** adalah RESTful API yang dibangun menggunakan Laravel 12 untuk mengelola sistem dompet digital sederhana namun aman. Dirancang khusus untuk melayani kebutuhan frontend (React/Vite) dengan fitur utama pengelolaan saldo, pengisian dana (Top Up), hingga transfer antar pengguna.

**Fungsi Utama:**
*   Manajemen otentikasi & profil pengguna.
*   Sistem saldo wallet yang terintegrasi untuk setiap user.
*   Proses pengisian saldo (Top Up) dengan validasi nominal.
*   Transfer dana antar pengguna dengan sistem pencarian (Email/Phone).
*   Riwayat transaksi lengkap dengan status dan tipe transaksi.
*   Keamanan transaksi menggunakan sistem Database Transaction (Atomic Operations).

---

## 🌟 Core Features
### 1️⃣ Wallet & Balance System
*   Setiap pengguna baru otomatis mendapatkan Wallet dengan saldo awal Rp 0.
*   Pengecekan saldo secara real-time.
*   Validasi saldo cukup sebelum melakukan transfer.

### 2️⃣ Transaction Flow
*   **Top Up**: Pengisian saldo ke wallet pribadi.
*   **Transfer**: Pengiriman dana ke pengguna lain menggunakan identitas Email atau Nomor Telepon.
*   **Transaction Logging**: Setiap aktivitas ekonomi (debit/kredit) dicatat dalam tabel transaksi untuk kebutuhan audit.

### 3️⃣ Security & Data Integrity
*   Penggunaan **Database Transactions** untuk menjamin konsistensi data saat transfer (jika satu gagal, semua batal).
*   Validasi input yang ketat (integer, positif, minimal nominal).

---

## 🔐 Authentication & Access Control
### Auth Flow
1. Client mengirim kredensial login ke `/api/login`.
2. Backend memvalidasi user dan menghasilkan **Sanctum Token**.
3. Client wajib menyertakan token tersebut di header `Authorization: Bearer <token>` untuk rute terproteksi.

### Access Control Rules
| Route Group | Middleware | Izin Akses |
| :--- | :--- | :--- |
| **Public** | None | Tamu (Register/Login) |
| **Authenticated** | `auth:sanctum` | Pengguna Terdaftar |

---

## 🗺️ API Routing Structure
**routes/api.php**
```text
│
├── Public Routes (No Auth)
│   ├── POST   /register
│   └── POST   /login
│
└── Authenticated Routes (Requires Token)
    ├── POST   /logout
    ├── PUT    /profile (Update Profile)
    ├── GET    /wallet (Balance Info)
    ├── POST   /topup
    ├── POST   /transfer
    ├── GET    /transactions (History)
    └── GET    /recent-transfers (Recent Recipients)
```

---

## ⚙️ Tech Stack

| Category | Technology |
| :--- | :--- |
| **Framework** | Laravel 12 |
| **Language** | PHP 8.2+ |
| **Database** | MySQL |
| **Auth** | Laravel Sanctum |
| **API Format** | JSON REST |

---

## 🗄️ Database Design
### Key Tables
*   **users**: Menyimpan data profil (name, username, email, phone_number, password).
*   **wallets**: Menyimpan saldo (balance) yang terikat ke `user_id`.
*   **transactions**: Mencatat setiap mutasi saldo (wallet_id, related_wallet_id, type, amount, reference).

---

## 🌐 API Endpoints

### Authentication
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `POST` | `/register` | Pendaftaran user baru & pembuatan wallet |
| `POST` | `/login` | Autentikasi & get Bearer token |
| `POST` | `/logout` | Revoke/hapus token aktif |
| `PUT` | `/profile` | Update data profil pengguna |

### Wallet Operations
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/wallet` | Mengambil informasi saldo terbaru |
| `POST` | `/topup` | Menambah saldo wallet |
| `POST` | `/transfer` | Kirim uang ke user lain (via email/phone) |
| `GET` | `/transactions` | Melihat riwayat seluruh transaksi |
| `GET` | `/recent-transfers` | List orang yang terakhir dikirimi uang |

---

## 🛡️ Middleware & Security
*   **CORS Configuration**: Dikonfigurasi untuk mengizinkan akses dari domain frontend.
*   **auth:sanctum**: Proteksi rute menggunakan Sanctum Bearer Token.
*   **Atomic Transactions**: Menjamin uang tidak hilang atau berlipat jika terjadi error sistem saat transfer.
*   **Validation**: Menggunakan Laravel Form Request untuk validasi tipe data (422 Unprocessable Entity).

---

## 🛠️ Prerequisites

Sebelum memulai, pastikan Anda telah menginstal berikut ini:
*   **PHP 8.2+**
*   **Composer**
*   **MySQL Server**
*   **Git**

---

## 🚀 Installation & Setup

Ikuti langkah-langkah berikut untuk menjalankan project ini di komputer lokal Anda:

### 1. Clone Repository (Optional)
```bash
git clone <repository-url>
cd backwallet
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Configuration
Salin file `.env.example` menjadi `.env` dan sesuaikan pengaturan database Anda:
```bash
cp .env.example .env
```
*Pastikan konfigurasi `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` sesuai dengan MySQL Anda.*

### 4. Setup Project
Jalankan perintah berikut untuk menginisialisasi aplikasi:
```bash
php artisan key:generate
php artisan migrate --seed  # Membuat tabel & data user awal
php artisan storage:link
```

### 5. Run Application
```bash
php artisan serve
```
Backend sekarang dapat diakses di: `http://localhost:8000`

---

## 📬 Author
**Admin Mini Wallet**
Fullstack Developer
*"Designing secure and scalable digital wallet solutions."*

> [!NOTE]
> Proyek ini adalah bagian dari Assignment Mini Wallet System.
