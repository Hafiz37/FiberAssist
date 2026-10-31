# 🚀 Cara Menjalankan WhatsApp Bot dengan Webhook

## 📋 Langkah-langkah

### 1. Jalankan Localtunnel

Buka terminal baru dan jalankan:

```bash
cd ~/Documents/FiberAssist
bash START_WEBHOOK.sh
```

Atau langsung:

```bash
npx localtunnel --port 80
```

**Output akan seperti ini:**
```
your url is: https://xyz-abc-123.loca.lt
```

⚠️ **JANGAN TUTUP TERMINAL INI!** Biarkan tetap berjalan.

---

### 2. Bypass Localtunnel Protection Page

1. **Copy URL** yang muncul (contoh: `https://xyz-abc-123.loca.lt`)
2. **Buka URL tersebut di browser**
3. Akan muncul halaman **"Tunnel Password"** atau **"Click to Continue"**
4. **Masukkan IP address Anda** atau klik tombol **"Click to Continue"**
5. Setelah di-bypass, halaman akan redirect ke Apache default page (ini normal)

---

### 3. Update Webhook di Meta Console

Setelah bypass, segera update webhook:

1. Buka: https://developers.facebook.com/apps
2. Pilih app: **wa project fiberassist**
3. Menu: **WhatsApp** → **Configuration**
4. Klik **Edit** di bagian Webhook
5. Isi:
   ```
   Callback URL: https://xyz-abc-123.loca.lt/FiberAssist/api/webhook_wa.php
   Verify Token: netfix_webhook_token_2026
   ```
   **(Ganti `xyz-abc-123` dengan URL Anda)**
6. Klik **"Verifikasi dan simpan"** ✅
7. Pastikan field **messages** status **"Subscribed"** ✅

---

### 4. Test Bot dari WhatsApp

1. Buka WhatsApp di HP
2. Kirim pesan ke: **+6289681858965**
3. Ketik: `Halo` atau `START` atau `1`
4. Bot akan balas otomatis! 🤖

---

### 5. Monitor Logs (Opsional)

Buka terminal baru untuk monitoring:

```bash
tail -f ~/Documents/FiberAssist/logs/webhook_wa.log
```

atau

```bash
tail -f /var/www/html/FiberAssist/logs/webhook_wa.log
```

---

## 🔍 Troubleshooting

### Problem: "Tunnel Unavailable" atau 503 Error

**Solusi:**
- Localtunnel protection belum di-bypass
- Buka URL tunnel di browser dan bypass dulu
- Tunggu 1-2 menit setelah bypass
- Update webhook di Meta Console lagi

### Problem: Bot tidak membalas

**Checklist:**
- [ ] Localtunnel masih running (terminal tidak ditutup)
- [ ] URL sudah di-bypass di browser
- [ ] Webhook di Meta Console sudah **"Verifikasi dan simpan"** ✅
- [ ] Field **messages** sudah **Subscribed** ✅
- [ ] Apache dan MySQL running: `sudo systemctl status apache2 mysql`

### Problem: URL tunnel berubah setiap restart

**Solusi:**
- Localtunnel memberikan URL random setiap kali restart
- Jika restart, harus update webhook di Meta Console lagi dengan URL baru
- Untuk URL permanent, gunakan Ngrok (berbayar) atau deploy ke VPS

---

## 📊 Status Konfigurasi

| Item | Status |
|------|--------|
| Nomor Bot | +6289681858965 ✅ |
| Phone Number ID | 1332044093323260 ✅ |
| WABA ID | 28904596195896353 ✅ |
| Config File | Updated ✅ |
| Webhook Endpoint | /FiberAssist/api/webhook_wa.php ✅ |
| Verify Token | netfix_webhook_token_2026 ✅ |
| Database | db_sistem_pakar ✅ |
| Forward Chaining Engine | Ready ✅ |

---

## 🎯 Perintah Bot WhatsApp

Kirim via WhatsApp ke **+6289681858965**:

| Perintah | Fungsi |
|----------|--------|
| `START`, `HALO`, `HI`, `MENU` | Mulai diagnosa baru |
| `1` | Internet Mati Total |
| `2` | Internet Lambat / Buffering |
| `RESET`, `BATAL` | Reset sesi |

---

## 📞 Support

Jika masih ada masalah:
1. Cek log: `tail -f ~/Documents/FiberAssist/logs/webhook_wa.log`
2. Cek Apache error: `tail -f /var/log/apache2/error.log`
3. Test webhook manual: `curl http://localhost/FiberAssist/test_connection.php`

---

**Good luck! Bot WhatsApp Anda siap digunakan! 🎉**
