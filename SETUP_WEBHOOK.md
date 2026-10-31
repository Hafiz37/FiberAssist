# 🚀 Panduan Setup Webhook WhatsApp Meta Cloud API

**Nomor Bot Baru**: `+6289681858965`  
**Tanggal Migrasi**: 14 Oktober 2026  
**Status**: ✅ Config Updated, ⏳ Webhook Pending Setup

---

## 📋 Langkah 1: Expose Server ke Internet

Pilih salah satu metode tunneling untuk expose localhost port 80:

### Opsi A: Localtunnel (Rekomendasi)
```bash
npx localtunnel --port 80
```
Akan muncul URL seperti: `https://abc-123-xyz.loca.lt`

### Opsi B: Tunnelmole
```bash
npx tunnelmole 80
```
Akan muncul URL seperti: `https://abc123.tunnelmole.net`

### Opsi C: Ngrok
```bash
ngrok http 80
```
Akan muncul URL seperti: `https://abc123.ngrok-free.app`

**⚠️ PENTING**: 
- Catat URL yang muncul (contoh: `https://abc-123-xyz.loca.lt`)
- Jangan tutup terminal/command prompt
- URL ini berlaku selama tunneling berjalan

---

## 📋 Langkah 2: Setup Webhook di Meta Console

1. **Buka Meta for Developers**  
   👉 https://developers.facebook.com/

2. **Pilih Aplikasi WhatsApp Business Anda**  
   Nama aplikasi: `wa project fiberassist`

3. **Masuk ke Konfigurasi WhatsApp**  
   Menu: **WhatsApp** → **Configuration**

4. **Edit Webhook**  
   Klik tombol **Edit** di bagian **Webhook**

5. **Isi Form Webhook**
   ```
   Callback URL: https://[URL_TUNNEL_ANDA]/FiberAssist/api/webhook_wa.php
   Verify Token: netfix_webhook_token_2026
   ```
   
   **Contoh Callback URL**:
   ```
   https://abc-123-xyz.loca.lt/FiberAssist/api/webhook_wa.php
   ```

6. **Klik Verify and Save**  
   Meta akan melakukan HTTP GET request ke server Anda untuk verifikasi

7. **Subscribe ke Webhook Field**  
   Di tabel **Webhook fields**, cari baris **messages**  
   Klik tombol **Subscribe**

8. **✅ Selesai!**  
   Status webhook harus menjadi **"Subscribed"**

---

## 📋 Langkah 3: Test Koneksi API (Opsional)

### Test 1: Cek Konfigurasi
Buka browser:
```
http://localhost/FiberAssist/tests/test_connection.php
```

### Test 2: Kirim Pesan Test Template
Buka browser:
```
http://localhost/FiberAssist/tests/test_whatsapp_meta.php?target=6289681858965
```

### Test 3: Simulasi Webhook GET Verification
Buka browser:
```
http://localhost/FiberAssist/api/webhook_wa.php?hub.mode=subscribe&hub.verify_token=netfix_webhook_token_2026&hub.challenge=TEST123
```
Harus menampilkan: `TEST123`

---

## 📋 Langkah 4: Test Bot dari HP (Live Test)

1. **Buka WhatsApp di HP**

2. **Kirim pesan ke nomor bot**: `+6289681858965`

3. **Ketik salah satu**:
   - `Halo`
   - `Hi`
   - `START`
   - `1`

4. **Bot harus membalas otomatis**:
   ```
   Halo! Selamat datang di Layanan Diagnosa Mandiri Internet ISP. 🤖

   Silakan pilih keluhan utama yang Anda alami:
   1. Internet Mati Total
   2. Internet Lambat / Buffering

   Balas dengan angka 1 atau 2.
   ```

5. **✅ Jika bot membalas** → Webhook berhasil!

6. **❌ Jika bot tidak membalas** → Cek logs:
   ```bash
   cat logs/webhook_wa.log
   ```

---

## 🔍 Troubleshooting

### Problem: Verify and Save gagal
**Solusi**:
- Pastikan tunneling masih berjalan (cek terminal)
- Pastikan Apache dan MySQL running: `sudo systemctl status apache2 mysql`
- Test manual webhook verification (Langkah 3 - Test 3)

### Problem: Bot tidak membalas pesan
**Solusi**:
- Cek webhook di Meta Console sudah **Subscribe** ke field `messages`
- Cek logs: `tail -f logs/webhook_wa.log`
- Pastikan nomor pengirim tidak di-blacklist

### Problem: HTTP 403 Forbidden
**Solusi**:
- Verify Token salah → Harus: `netfix_webhook_token_2026`
- File permission: `sudo chmod -R 755 /home/gopal-ichiro/Documents/FiberAssist`

### Problem: Token expired
**Solusi**:
- Token Anda adalah **Permanent Token**, tidak expire
- Jika tetap error, regenerate token dari System User di Meta Console

---

## 📊 Informasi Kredensial (Reference)

| Parameter | Nilai |
|-----------|-------|
| Phone Number | `+6289681858965` |
| Phone Number ID | `1332044093323260` |
| WABA ID | `28904596195896353` |
| Verify Token | `netfix_webhook_token_2026` |
| API Version | `v20.0` |
| Token Type | Permanent (System User) |

---

## 📞 Perintah Cepat Bot

Kirim via WhatsApp ke `+6289681858965`:

| Perintah | Fungsi |
|----------|--------|
| `START`, `HALO`, `HI`, `MENU` | Reset dan mulai diagnosa baru |
| `1` | Pilih: Internet Mati Total |
| `2` | Pilih: Internet Lambat / Buffering |
| `RESET`, `BATAL` | Batalkan sesi dan mulai ulang |

---

## 🎯 Checklist Setup

- [ ] Server Apache + MySQL running
- [ ] Tunneling aktif (localtunnel/ngrok/tunnelmole)
- [ ] Webhook configured di Meta Console
- [ ] Webhook field `messages` di-Subscribe
- [ ] Test connection API berhasil
- [ ] Test kirim pesan dari HP → Bot membalas ✅

---

**Selamat! Bot WhatsApp Anda sudah siap digunakan! 🎉**

Jika ada masalah, cek file `logs/webhook_wa.log` untuk debug.
