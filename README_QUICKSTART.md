# 🚀 FiberAssist - WhatsApp Bot Quick Start Guide

## 📋 Cara Menjalankan Bot

### Start Bot
```bash
cd ~/Documents/FiberAssist
./start.sh
```

### Stop Bot
```bash
cd ~/Documents/FiberAssist
./stop.sh
```

---

## 🔧 Yang Dilakukan Script `start.sh`:

1. ✅ Check dependencies (MySQL, Node.js, Apache)
2. ✅ Start Apache & MySQL (jika belum running)
3. ✅ Check/import database
4. ✅ Setup symbolic link ke `/var/www/html/FiberAssist`
5. ✅ Start Localtunnel dengan subdomain tetap: **polite-turkeys-take**
6. ✅ Set permissions

---

## 📡 Webhook URL (Permanent)

URL webhook akan selalu sama:
```
https://polite-turkeys-take.loca.lt/FiberAssist/api/webhook_wa.php
```

**PENTING**: 
- URL ini akan tetap sama setiap kali Anda jalankan `./start.sh`
- Anda tidak perlu update webhook di Meta Console lagi
- Hanya perlu bypass localtunnel protection page di browser sekali

---

## 🎯 Setup Meta Console (Sekali Saja)

1. Buka: https://developers.facebook.com/apps
2. Pilih: **wa project fiberassist**
3. WhatsApp → Configuration → Edit Webhook
4. Isi:
   - **Callback URL**: `https://polite-turkeys-take.loca.lt/FiberAssist/api/webhook_wa.php`
   - **Verify Token**: `netfix_webhook_token_2026`
5. Klik **"Verify and Save"**
6. Subscribe ke field **"messages"** ✅

---

## 📱 Test Bot

Setelah `./start.sh` berjalan:

1. **Buka browser**: https://polite-turkeys-take.loca.lt
2. **Bypass protection**: Klik "Click to Continue"
3. **Kirim WA** dari HP ke: **+6289681858965**
4. **Ketik**: `START` atau `Halo` atau `1`
5. **Bot akan balas** dalam 1-5 detik! 🤖

---

## 📊 Monitor Logs

### Webhook logs (pesan masuk/keluar):
```bash
tail -f ~/Documents/FiberAssist/logs/webhook_wa.log
```

### Tunnel logs:
```bash
tail -f ~/Documents/FiberAssist/logs/tunnel.log
```

### Database logs:
```bash
mysql -u root -p'Sembarang_37' db_sistem_pakar -e "SELECT * FROM message_logs ORDER BY created_at DESC LIMIT 10;"
```

---

## 🔍 Troubleshooting

### Bot tidak merespon?

1. **Cek tunnel masih running:**
   ```bash
   ps aux | grep localtunnel
   ```

2. **Cek Apache & MySQL:**
   ```bash
   sudo systemctl status apache2 mysql
   ```

3. **Cek webhook logs:**
   ```bash
   tail -20 ~/Documents/FiberAssist/logs/webhook_wa.log
   ```

4. **Test webhook accessible:**
   ```bash
   curl http://localhost/FiberAssist/test_connection.php
   ```

### Tunnel gagal start?

Jika subdomain `polite-turkeys-take` sudah diambil orang lain:

1. Edit `start.sh`
2. Ubah baris: `TUNNEL_SUBDOMAIN="polite-turkeys-take"`
3. Ganti dengan subdomain lain: `TUNNEL_SUBDOMAIN="fiberassist-bot"`
4. Update webhook URL di Meta Console

---

## 🤖 Perintah Bot WhatsApp

Kirim ke **+6289681858965**:

| Perintah | Fungsi |
|----------|--------|
| `START`, `HALO`, `HI`, `MENU` | Mulai diagnosa baru |
| `1` | Internet Mati Total |
| `2` | Internet Lambat / Buffering |
| `RESET`, `BATAL` | Reset sesi diagnosa |

---

## 📁 Struktur File Penting

```
~/Documents/FiberAssist/
├── start.sh              # Script untuk start bot
├── stop.sh               # Script untuk stop bot
├── src/
│   ├── api/
│   │   └── webhook_wa.php      # Webhook endpoint
│   ├── config/
│   │   ├── whatsapp.php        # Konfigurasi API
│   │   └── database.php        # Konfigurasi DB
│   └── includes/
│       ├── ForwardChaining.php # Logic diagnosa
│       └── whatsapp_helper.php # Helper kirim pesan
├── logs/
│   ├── webhook_wa.log    # Log pesan WA
│   ├── tunnel.log        # Log localtunnel
│   └── tunnel.pid        # PID localtunnel
└── database/
    └── dataset_sistem_pakar.sql
```

---

## 🎉 Selamat!

Bot WhatsApp Anda sudah siap digunakan!

**Nomor Bot**: +6289681858965  
**Webhook URL**: https://polite-turkeys-take.loca.lt/FiberAssist/api/webhook_wa.php

Untuk menjalankan ulang, cukup ketik:
```bash
cd ~/Documents/FiberAssist && ./start.sh
```
