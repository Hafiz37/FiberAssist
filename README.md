# FiberAssist - NetFix Bot

**Rancang Bangun Otomasi Diagnosa Gangguan Jaringan Fiber Optik Berbasis Sistem Pakar dan WhatsApp Bot**

## 🚀 Teknologi

- **Forward Chaining Expert System**
- **PHP Native (PDO)** 
- **MariaDB/MySQL**
- **WhatsApp Cloud API (Meta)**
- **Chart.js Analytics**

## 📂 Struktur Proyek

```
FiberAssist/
├── src/                    # Source code utama
│   ├── api/               # REST API endpoints
│   ├── config/            # Database & WhatsApp config
│   └── includes/          # Core libraries (Forward Chaining)
├── public/                # Publicly accessible files
│   ├── dashboard/         # CS Dashboard monitoring
│   ├── chat/             # WhatsApp simulator
│   └── assets/           # Images, logos
├── database/             # SQL schema & datasets
├── docs/                 # Documentation
├── scripts/              # Setup scripts
└── logs/                 # Application logs

```

## 🔧 Quick Start

### 1. Aktifkan XAMPP
```bash
# Start Apache & MySQL
xampp start
```

### 2. Import Database
```bash
mysql -u root < database/dataset_sistem_pakar.sql
```

### 3. Akses Aplikasi
- **Dashboard CS:** http://localhost/FiberAssist/dashboard
- **Chat Simulator:** http://localhost/FiberAssist/chat
- **API Endpoint:** http://localhost/FiberAssist/api/diagnosa

## 🔑 Default Login

- **Username:** admin
- **Password:** admin123

## 📖 Dokumentasi Lengkap

Lihat folder `docs/` untuk dokumentasi lengkap:
- `walkthrough.md` - Panduan penggunaan

## 🎯 Fitur Utama

✅ Diagnosa otomatis via WhatsApp
✅ Forward Chaining expert system
✅ Auto-generate tiket gangguan
✅ Dashboard monitoring real-time
✅ Role-based access (Admin/CS)
✅ WhatsApp Cloud API integration

## 📝 License

Internal project for educational purposes.
