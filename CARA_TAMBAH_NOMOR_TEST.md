# 📱 Cara Menambahkan Nomor Test di Meta WhatsApp Business API

## 🎯 Tujuan
Menambahkan nomor HP Anda sebagai "Test Number" agar bisa menerima pesan dari bot di Development Mode.

---

## 📋 Langkah-langkah Detail

### 1. Buka Meta for Developers
👉 https://developers.facebook.com/apps

### 2. Pilih Aplikasi WhatsApp Anda
- Klik aplikasi: **"wa project fiberassist"**
- Atau aplikasi WhatsApp Business yang Anda gunakan

### 3. Masuk ke Menu WhatsApp
- Di sidebar kiri, klik **"WhatsApp"**
- Pilih submenu **"API Setup"**

### 4. Cari Section "To" atau "Send and receive messages"
Scroll ke bawah hingga menemukan bagian:
- **"To"** atau
- **"Send and receive messages"** atau
- **"Step 2: Send messages with the API"**

Di bagian ini akan ada:
- Display phone number: +62 896-8185-8965
- Tombol **"Manage phone number list"** atau **"Add recipient phone number"**

### 5. Klik "Manage phone number list"
Akan muncul popup/modal untuk menambah nomor.

### 6. Tambahkan Nomor HP Anda
**Format nomor:**
```
6289xxxxxxxxx
```
**Contoh:**
- Jika nomor Anda: 0812-3456-7890
- Masukkan: 6281234567890 (tanpa 0 di depan, ganti dengan 62)

**PENTING:**
- Tanpa tanda + di depan
- Tanpa strip atau spasi
- Format: 62 + nomor (tanpa 0)

### 7. Verifikasi OTP
Setelah submit:
1. Meta akan kirim kode OTP ke WhatsApp Anda
2. Buka WhatsApp di HP
3. Akan ada pesan dari WhatsApp resmi dengan kode 6 digit
4. Masukkan kode tersebut ke popup Meta Console
5. Klik "Verify" atau "Konfirmasi"

### 8. Nomor Berhasil Ditambahkan
- Nomor Anda akan muncul di list "Test phone numbers"
- Status: **Verified** ✅

---

## 📸 Visual Guide (Lokasi Menu)

```
Meta for Developers
├── Apps (pilih: wa project fiberassist)
│   └── WhatsApp
│       ├── Getting Started
│       ├── API Setup ← PILIH INI
│       │   ├── Phone Numbers
│       │   ├── Temporary access token
│       │   └── Send and receive messages
│       │       ├── Step 1: Select phone number
│       │       └── Step 2: Send messages with the API
│       │           ├── To: [Nomor bot]
│       │           └── [Manage phone number list] ← KLIK INI
│       └── Configuration
```

---

## 🔍 Troubleshooting

### Problem: Tidak Menemukan "Manage phone number list"

**Lokasi Alternatif:**
1. **WhatsApp** → **Getting Started**
   - Scroll ke "Step 2: Send a test message"
   - Klik **"Add recipient phone number"**

2. **WhatsApp** → **API Setup**
   - Bagian "To" 
   - Klik ikon pensil atau "Edit"
   - Pilih "Add phone number"

3. **Business Settings** (jika tidak ada di Apps)
   - Buka: https://business.facebook.com/settings/whatsapp-business-accounts
   - Pilih WABA Anda
   - Tab "Phone numbers"
   - Klik "Add phone number"

### Problem: OTP Tidak Masuk

**Solusi:**
1. Pastikan WhatsApp di HP Anda aktif dan online
2. Cek WhatsApp dari nomor resmi WhatsApp/Meta
3. Tunggu 1-2 menit
4. Jika tidak masuk, klik "Resend code"
5. Pastikan nomor yang diinput benar (format 62xxx)

### Problem: "Phone number already in use"

**Artinya:**
- Nomor sudah terdaftar di WABA lain
- Atau sudah terdaftar di app lain

**Solusi:**
- Gunakan nomor HP lain yang belum terdaftar
- Atau hapus nomor dari WABA/app lain terlebih dahulu

---

## ✅ Verifikasi Berhasil

Setelah nomor ditambahkan, cek:

1. **Di Meta Console:**
   - Nomor muncul di list "Recipient phone numbers"
   - Status: **Verified** ✅

2. **Test Bot:**
   ```bash
   # Monitor log di terminal
   tail -f /var/www/html/FiberAssist/logs/webhook_wa.log
   ```

3. **Kirim WA:**
   - Dari HP Anda
   - Ke: **+6289681858965**
   - Ketik: **START** atau **Halo**

4. **Bot Akan Balas Otomatis:**
   ```
   Halo! Selamat datang di Layanan Diagnosa Mandiri Internet ISP. 🤖

   Silakan pilih keluhan utama yang Anda alami:
   1. Internet Mati Total
   2. Internet Lambat / Buffering

   Balas dengan angka 1 atau 2.
   ```

---

## 📞 Batasan Development Mode

**Yang Bisa:**
- ✅ Bot membalas pesan dari nomor test yang terdaftar
- ✅ Dalam 24 jam setelah user kirim pesan (customer care window)
- ✅ Unlimited pesan dalam 24 jam window tersebut

**Yang Tidak Bisa:**
- ❌ Bot kirim pesan duluan ke user (harus user yang mulai)
- ❌ Bot membalas nomor yang tidak terdaftar sebagai test number
- ❌ Kirim template "hello_world" dari non-test number

**Untuk Production (unlimited):**
- Selesaikan Business Verification di Meta
- Approve template message kustom
- Upgrade ke Live Mode

---

## 📊 Checklist Final

Sebelum test bot, pastikan:

- [ ] Nomor HP Anda sudah ditambahkan sebagai test number ✅
- [ ] Status nomor: **Verified** ✅
- [ ] Webhook: `https://polite-turkeys-take.loca.lt/FiberAssist/api/webhook_wa.php` ✅
- [ ] Field "messages": **Subscribed** ✅
- [ ] Tunnel masih running ✅
- [ ] Token sudah update di web server ✅

Jika semua checklist ✅, bot akan berfungsi!

---

**Good luck!** 🚀
