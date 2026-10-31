# Dokumentasi Proyek FiberAssist (NetFix Bot)

## 1. Judul

**Rancang Bangun Otomasi Diagnosis Gangguan Jaringan Fiber Optik Berbasis Sistem Pakar dan WhatsApp Bot**

## 2. Rumusan Masalah

1. Bagaimana membantu pelanggan menyampaikan gejala gangguan jaringan fiber optik dengan cara yang mudah dan terarah?
2. Bagaimana mengubah pengetahuan mengenai gejala, jenis kerusakan, dan solusi menjadi aturan diagnosis yang dapat digunakan secara konsisten?
3. Bagaimana memberikan hasil diagnosis awal dan saran penanganan berdasarkan jawaban pelanggan?
4. Bagaimana membantu customer service (CS) memantau riwayat diagnosis dan menindaklanjuti gangguan yang memerlukan teknisi?
5. Bagaimana menyediakan media pengujian alur diagnosis tanpa selalu bergantung pada percakapan WhatsApp yang sesungguhnya?

Rumusan masalah ini merupakan tujuan yang disusun berdasarkan fungsi proyek, bukan klaim hasil penelitian atau pengukuran kinerja.

## 3. Solusi yang Diusulkan

FiberAssist, juga disebut **NetFix Bot**, adalah aplikasi diagnosis awal gangguan jaringan fiber optik. Aplikasi menggabungkan sistem pakar dengan metode *forward chaining*, percakapan melalui WhatsApp, simulator chat berbasis web, serta dashboard bagi admin dan CS.

Pelanggan menjawab pertanyaan tentang gejala. Sistem mengumpulkan jawaban sebagai fakta, mencocokkannya dengan aturan yang tersimpan, lalu memberikan kesimpulan dan saran sesuai aturan yang cocok. Gangguan yang membutuhkan penanganan teknisi dapat menghasilkan tiket untuk ditindaklanjuti melalui dashboard. Hasil ini adalah bantuan diagnosis awal; kualitasnya bergantung pada kelengkapan aturan dan validasi pengetahuan oleh pihak yang memahami jaringan.

## 4. Penjelasan Proyek

### 4.1 Pengguna dan kebutuhan

- **Pelanggan:** memulai percakapan, menjawab pertanyaan, dan menerima hasil diagnosis serta saran.
- **CS:** memantau laporan dan tiket, melihat detail, serta memperbarui status tiket sesuai proses kerja.
- **Admin:** mengelola pengguna, basis pengetahuan, sesi, dan pengaturan yang tersedia pada dashboard.
- **Teknisi/pakar jaringan:** memberikan pengetahuan gejala, kerusakan, solusi, dan kriteria eskalasi serta menindaklanjuti tiket sesuai proses organisasi. Peran ini merupakan pihak operasional yang berkolaborasi dengan tim proyek, bukan salah satu dari sembilan jobdesk pengembangan.

### 4.2 Fitur dan komponen utama

1. **Sistem pakar:** kelas `src/includes/ForwardChaining.php` mengelola sesi, fakta, pencocokan aturan, kesimpulan, dan pembuatan tiket.
2. **Bot WhatsApp:** `src/api/webhook_wa.php` menerima peristiwa dari WhatsApp; konfigurasi dan fungsi pendukung berada di `src/config/whatsapp.php` dan `src/includes/whatsapp_helper.php`. Integrasi membutuhkan konfigurasi layanan Meta dan webhook yang dapat diakses.
3. **Simulator chat:** `public/chat/` menyediakan antarmuka untuk mencoba alur diagnosis melalui API.
4. **Dashboard:** `public/dashboard/` menyediakan login, pemantauan tiket dan laporan, grafik, serta fungsi administrasi yang tersedia.
5. **API:** `src/api/` menyediakan endpoint untuk diagnosis, autentikasi, tiket, laporan, sesi, pengguna, basis pengetahuan, pengaturan, dan webhook.
6. **Database:** `database/dataset_sistem_pakar.sql` menyediakan skema dan data awal, termasuk gejala, kerusakan, aturan, sesi diagnosis, tiket, dan pengguna. Beberapa tabel tambahan disiapkan oleh aplikasi saat dibutuhkan; periksa implementasi sebelum membuat migrasi.

### 4.3 Teknologi

- **Backend:** PHP native dan PDO.
- **Database:** MySQL/MariaDB dan SQL.
- **Frontend:** HTML, CSS, dan JavaScript; Chart.js digunakan untuk grafik dashboard.
- **Integrasi:** WhatsApp Cloud API dan webhook Meta.
- **Lingkungan pengembangan:** Apache, skrip shell, dan localtunnel untuk pengujian webhook lokal sesuai dokumentasi proyek.

### 4.4 Alur penggunaan

1. Pelanggan memulai percakapan melalui WhatsApp atau menggunakan simulator chat.
2. Sistem membuat atau melanjutkan sesi diagnosis berdasarkan identitas sesi yang digunakan.
3. Sistem menyampaikan pertanyaan dan menerima jawaban gejala secara bertahap.
4. Mesin *forward chaining* mengumpulkan fakta dan mencocokkannya dengan aturan.
5. Sistem menyampaikan hasil dan solusi jika aturan yang sesuai ditemukan; kondisi tanpa kecocokan juga perlu diuji.
6. Jika hasil memerlukan teknisi, sistem membuat tiket gangguan.
7. CS memantau laporan dan tiket pada dashboard, lalu memperbarui status sesuai tindak lanjut.

Alur WhatsApp bergantung pada konfigurasi Meta, koneksi, dan ketersediaan endpoint. Simulator membantu menguji logika, tetapi tidak menggantikan pengujian integrasi WhatsApp.


### 4.5 Detail proses diagnosis yang tersedia

Mesin diagnosis saat ini menggunakan percakapan bertahap yang ditulis dalam kode. Data gejala, aturan, kerusakan, dan solusi mendukung pencocokan kesimpulan. Karena pertanyaan dan perpindahan langkah berada di `ForwardChaining.php`, menambahkan gejala melalui dashboard saja belum berarti membuat cabang percakapan baru secara otomatis. Perubahan cabang memerlukan koordinasi Backend, System Analyst, Database Engineer, dan QA.

**Cabang internet mati total:** pelanggan memilih keluhan mati total, kemudian menjawab pertanyaan tentang lampu LOS. Jika LOS merah, sistem menambahkan fakta terkait dan mencoba aturan. Jika tidak, pertanyaan berlanjut ke PON, kemudian Power, kemudian WLAN. Pada cabang terakhir ketika WLAN menyala, implementasi menambahkan fakta G10; asumsi terkait tagihan/isolir ini harus ditinjau pakar karena aplikasi tidak membuktikan status pembayaran melalui integrasi sistem tagihan.

**Cabang internet lambat:** pelanggan memilih apakah masalah terjadi pada satu perangkat, semua perangkat, atau situs tertentu. Jawaban dipetakan ke fakta yang berbeda, lalu sistem mencari aturan dan memberikan hasil. Ini merupakan diagnosis berbasis jawaban pengguna, bukan pengukuran kecepatan atau pemeriksaan perangkat jaringan secara langsung.

**Finalisasi:** ketika aturan cocok, sistem mengambil kode kerusakan, nama, solusi, dan penanda kebutuhan teknisi. Tiket dibuat jika penanda tersebut aktif. Jika tidak ada aturan cocok, implementasi membuat tiket dengan kategori K06 dan hasil sementara untuk pengecekan manual. Sesi diselesaikan dan hasil disimpan. Pesan yang menyebut pengalihan ke CS tidak dengan sendirinya membuktikan adanya fitur percakapan langsung dengan agen manusia.

**Perintah percakapan:** `RESET`, `START`, `BATAL`, `MENU`, `HALO`, `HI`, `HELP`, dan `0` masuk ke alur reset/mulai pada mesin saat ini. Makna tombol atau pesan bantuan harus selaras dengan perilaku kode tersebut.

### 4.6 Data, fitur, dan batas cakupan

- **Gejala:** mendefinisikan kode dan nama gejala serta teks pertanyaan yang tersedia pada data. Pakar memvalidasi maknanya; perubahan data dan alur kode harus diperiksa bersama.
- **Kerusakan:** menyimpan jenis hasil, solusi, dan kebutuhan teknisi. Keputusan eskalasi diturunkan dari pengetahuan ini.
- **Aturan:** menghubungkan kombinasi gejala dengan kerusakan; perubahan aturan perlu kasus uji agar tidak menghasilkan kesimpulan keliru.
- **Sesi:** menyimpan tahap percakapan, fakta, status, dan hasil. Sesi merupakan rekam proses diagnosis; tiket merupakan pekerjaan tindak lanjut sehingga keduanya tidak selalu berjumlah sama.
- **Tiket:** menyimpan identitas tiket, pelanggan, hasil diagnosis, dan status penanganan. Tindak lanjut lapangan tetap membutuhkan proses kerja organisasi.
- **Pengguna dan pengaturan:** mendukung petugas yang mengakses dashboard serta konfigurasi aplikasi. Pengaturan integrasi harus dikelola oleh pihak berwenang.

Proyek bukan model pembelajaran mesin atau chatbot generatif. Tidak ada klaim bahwa sistem melakukan pengukuran redaman optik, pemeriksaan OLT secara langsung, penjadwalan teknisi otomatis, atau validasi tagihan pelanggan. Fitur tersebut memerlukan analisis serta implementasi tambahan apabila diputuskan menjadi cakupan berikutnya.

### 4.7 Contoh skenario operasional

Pelanggan melaporkan internet mati total dan menjawab bahwa LOS merah. Sistem menyimpan fakta, mencari aturan, lalu memberikan solusi sesuai dataset. Jika kerusakan memerlukan teknisi, nomor tiket dihasilkan. CS membaca tiket dan detail diagnosis pada dashboard, melakukan tindak lanjut melalui prosedur layanan, dan memperbarui status. Teknisi atau pakar dapat memberi masukan jika hasil awal tidak sesuai temuan lapangan; perubahan aturan harus ditinjau dan diuji sebelum dipakai kembali.

## 5. Flow Pembuatan Proyek

Berikut adalah **usulan alur dan timeline**, bukan catatan waktu aktual proyek. Durasi dapat disesuaikan dengan kemampuan tim, ketersediaan pakar, dan kebutuhan pengguna; beberapa pekerjaan berlangsung paralel.

| Fase | Perkiraan | Kegiatan utama | Hasil/gerbang kerja |
|---|---|---|---|
| 1. Inisiasi dan analisis | Minggu 1 | Menentukan tujuan, pemangku kepentingan, kebutuhan, ruang lingkup, dan risiko. Mengumpulkan pengetahuan dari pakar. | Kebutuhan dan ruang lingkup disepakati. |
| 2. Perancangan | Minggu 2 | Menyusun alur diagnosis, aturan, rancangan database, API, wireframe, dan rencana pengujian. | Rancangan ditinjau tim dan pakar. |
| 3. Pembangunan fondasi | Minggu 3–4 | Menyiapkan lingkungan, skema dan data awal, mesin inferensi, autentikasi, serta antarmuka dasar. | Komponen inti dapat dijalankan dan diuji secara terpisah. |
| 4. Integrasi | Minggu 5–6 | Menghubungkan simulator, dashboard, API, database, tiket, dan webhook WhatsApp. | Alur utama berjalan dari input sampai hasil dan tindak lanjut. |
| 5. Pengujian dan perbaikan | Minggu 7 | Menguji aturan bersama pakar, fungsi, integrasi, hak akses, dan penanganan kesalahan. | Temuan prioritas ditangani dan hasil pengujian dicatat. |
| 6. Persiapan rilis dan serah terima | Minggu 8 | Memeriksa konfigurasi, keamanan dasar, pencadangan, panduan, demonstrasi, dan penerimaan pengguna. | Versi yang disepakati siap diserahkan sesuai lingkungan target. |
| 7. Pemeliharaan | Setelah serah terima | Memantau masalah, memperbarui aturan bersama pakar, serta memperbaiki aplikasi. | Perubahan dicatat dan diuji sebelum diterapkan. |

**Ketergantungan utama:** pengetahuan dan aturan tervalidasi menjadi dasar implementasi inferensi dan kasus uji; skema database dan kontrak API menyelaraskan backend dan frontend; integrasi WhatsApp membutuhkan konfigurasi dan endpoint yang dapat diakses; serah terima membutuhkan hasil pengujian dan panduan.

## 6. Pembagian Jobdesk Sembilan Orang

### 6.1 Project Manager

**Fitur atau paket pekerjaan yang ditangani secara konkret:**

1. Paket diagnosis: menetapkan cakupan cabang mati total/lambat, waktu validasi pakar, serta penerimaan alur hasil dan tiket.
2. Paket dashboard: menyusun prioritas login, pemantauan, laporan, dan administrasi bersama pengguna.
3. Paket integrasi: menjadwalkan konfigurasi Meta dan pengujian webhook serta menyediakan waktu untuk kendala eksternal.
4. Paket serah terima: mengumpulkan persetujuan pengguna, hasil uji, panduan, dan daftar masalah tersisa.

**Cara menyerahkan dan memeriksa pekerjaan:** setiap paket diserahkan bersama penjelasan perubahan, lokasi hasil, ketergantungan, dan masalah yang belum selesai. Hasil ditinjau oleh pihak yang memakai hasil tersebut; pekerjaan yang memengaruhi perilaku aplikasi diperiksa melalui skenario uji yang disepakati. Checklist di bagian ini adalah rencana tugas, bukan bukti bahwa tugas telah selesai.


**Tanggung jawab utama:** mengarahkan ruang lingkup, jadwal, prioritas, risiko, komunikasi, dan penerimaan hasil proyek. Memastikan keputusan serta hambatan ditindaklanjuti.

**Deliverables:** rencana proyek dan jadwal, pembagian tugas, daftar prioritas, catatan rapat dan keputusan, daftar risiko, serta laporan kemajuan dan serah terima.

**Tools/teknologi:** papan tugas seperti Trello/GitHub Projects atau alat yang disepakati, spreadsheet, dokumen bersama, dan Git untuk memantau perubahan. Alat manajemen tersebut merupakan pilihan, bukan komponen yang dipastikan sudah digunakan proyek.

**Timeline/fase:** memimpin fase 1–7; menetapkan rencana pada minggu 1, memantau pelaksanaan setiap minggu, dan mengoordinasikan penerimaan pada minggu 8.

**Kolaborasi:** dengan System Analyst untuk ruang lingkup; seluruh pengembang dan QA untuk progres dan hambatan; Dokumentasi untuk laporan; serta pengguna dan pakar untuk keputusan dan penerimaan.

**Checklist tugas spesifik:**

- [ ] Menetapkan tujuan FiberAssist, batasan, dan kriteria penerimaan bersama pengguna.
- [ ] Membagi pekerjaan sembilan peran dan menyusun jadwal serta ketergantungan.
- [ ] Mengatur sesi validasi aturan dengan pakar jaringan.
- [ ] Memantau risiko integrasi WhatsApp, kesiapan data, dan penyelesaian bug.
- [ ] Memimpin demonstrasi, persetujuan hasil, dan serah terima.

**Rincian pekerjaan per fase:**

1. **Inisiasi:** menentukan tujuan, cakupan, indikator keberhasilan, pemangku kepentingan, serta asumsi dan batasan proyek. Menentukan siapa yang mengambil keputusan ketika kebutuhan berubah.
2. **Perencanaan:** memecah pekerjaan menjadi tugas yang dapat diselesaikan, menetapkan penanggung jawab dan tenggat, serta mengurutkan ketergantungan antara aturan pakar, database, API, antarmuka, dan pengujian.
3. **Pelaksanaan:** memimpin pertemuan rutin, membandingkan progres dengan rencana, menghilangkan hambatan, dan memperbarui prioritas. Mengawal keputusan terkait perubahan ruang lingkup.
4. **Pengujian dan serah terima:** memastikan temuan memiliki pemilik dan prioritas, mengatur demonstrasi serta penerimaan pengguna, lalu menyerahkan dokumentasi dan daftar masalah tersisa.
5. **Pemeliharaan:** menentukan jalur pelaporan masalah, penanggung jawab tindak lanjut, dan jadwal evaluasi.

**Contoh hasil yang harus diperiksa:** daftar tugas memiliki penanggung jawab dan status; risiko webhook dan ketersediaan pakar memiliki rencana tindak lanjut; keputusan perubahan fitur dicatat beserta dampaknya pada jadwal. PM bertanggung jawab mengoordinasikan penerimaan, sedangkan penilaian teknis dilakukan oleh pemilik bidang dan pihak yang berwenang.

**Checklist tambahan:**

- [ ] Mengidentifikasi perwakilan pelanggan, CS, teknisi/pakar, dan pihak penerima hasil.
- [ ] Menentukan prioritas minimum: diagnosis, hasil, tiket, dan dashboard.
- [ ] Mengadakan tinjauan kemajuan serta mencatat hambatan dan keputusan.
- [ ] Menetapkan batas penyelesaian tiap fase berdasarkan hasil yang dapat diperiksa.
- [ ] Menyusun daftar masalah tersisa dan penanggung jawab setelah serah terima.


#### Rencana pelaksanaan terperinci dan hasil kerja

**Bahan yang harus diterima sebelum bekerja:** Kebutuhan pengguna, kapasitas sembilan anggota, dependensi integrasi Meta, dan prioritas bisnis.

**Paket 1.1 — Menyusun backlog fitur**

- Langkah pekerjaan: Pisahkan pekerjaan menjadi diagnosis mati total, diagnosis lambat, sesi/reset, hasil dan tiket, simulator, dashboard, administrasi, dan integrasi WhatsApp. Setiap pekerjaan memiliki tujuan, pemilik, ketergantungan, dan bukti penyelesaian.
- Produk yang dibuat: Backlog yang berisi tugas terukur, bukan hanya label seperti “kerjakan backend”.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Paket 1.2 — Mengendalikan perubahan**

- Langkah pekerjaan: Ketika ada permintaan cabang diagnosis baru, minta Analyst menilai aturan, Backend menilai alur, Database Engineer menilai data, dan QA menilai kasus uji tambahan. Putuskan prioritas dan jadwal sebelum tugas dimasukkan.
- Produk yang dibuat: Catatan keputusan yang menjelaskan perubahan cakupan dan dampaknya.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Paket 1.3 — Mengelola kesiapan rilis**

- Langkah pekerjaan: Kumpulkan demonstrasi fitur, hasil QA, validasi pakar, kesiapan lingkungan, dan panduan. Catat pengecualian yang diterima pihak berwenang serta pemilik tindak lanjut.
- Produk yang dibuat: Daftar pemeriksaan rilis dan berita acara penerimaan.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Kriteria selesai untuk peran ini:** Jadwal disetujui pemilik pekerjaan; tugas kritis memiliki pemilik; risiko terbuka terlihat; penerimaan tercatat.

**Batas tanggung jawab:** Tidak menentukan sendiri kebenaran diagnosis dan tidak menggantikan pengujian teknis oleh QA.


### 6.2 Backend Developer

**Fitur atau paket pekerjaan yang ditangani secara konkret:**

1. Mesin percakapan: membuat atau memperbarui pengolahan perintah, langkah pertanyaan, fakta, reset, dan finalisasi dalam ForwardChaining.php.
2. Mesin aturan dan tiket: membaca basis pengetahuan, mencocokkan fakta, menangani hasil tanpa aturan, dan membuat tiket sesuai kondisi.
3. API dashboard: menyediakan pembacaan data, perubahan status, pengelolaan basis pengetahuan, sesi, pengguna, serta pengaturan sesuai hak akses.
4. Integrasi WhatsApp: menerima webhook, meneruskan input ke mesin, menyusun balasan, dan menangani kegagalan layanan.

**Cara menyerahkan dan memeriksa pekerjaan:** setiap paket diserahkan bersama penjelasan perubahan, lokasi hasil, ketergantungan, dan masalah yang belum selesai. Hasil ditinjau oleh pihak yang memakai hasil tersebut; pekerjaan yang memengaruhi perilaku aplikasi diperiksa melalui skenario uji yang disepakati. Checklist di bagian ini adalah rencana tugas, bukan bukti bahwa tugas telah selesai.


**Tanggung jawab utama:** membangun dan memelihara logika aplikasi, API, sesi, autentikasi, integrasi webhook, serta mesin diagnosis dan tiket.

**Deliverables:** implementasi PHP pada `src/api/` dan `src/includes/`, kontrak respons API, penanganan kesalahan, serta perbaikan hasil pengujian.

**Tools/teknologi:** PHP, PDO, SQL/MySQL atau MariaDB, Apache, WhatsApp Cloud API, Git, dan alat penguji HTTP seperti Postman atau curl.

**Timeline/fase:** merancang kontrak pada minggu 2; membangun fondasi pada minggu 3–4; mengintegrasikan fitur pada minggu 5–6; memperbaiki temuan pada minggu 7–8 dan pemeliharaan.

**Kolaborasi:** dengan Database Engineer untuk skema dan query; Frontend untuk format API; System Analyst dan pakar untuk aturan; DevOps untuk konfigurasi; QA untuk reproduksi masalah.

**Checklist tugas spesifik:**

- [ ] Meninjau alur fakta dan pencocokan aturan di `ForwardChaining.php` bersama pakar.
- [ ] Mengimplementasikan/memeriksa API diagnosis, sesi, tiket, laporan, dan basis pengetahuan.
- [ ] Memastikan tiket dibuat sesuai kondisi hasil yang membutuhkan teknisi.
- [ ] Mengintegrasikan penerimaan dan pengiriman pesan WhatsApp serta menangani kegagalan.
- [ ] Memeriksa validasi input, autentikasi, otorisasi, dan respons kesalahan bersama QA.

**Rincian pekerjaan per fase:**

1. **Analisis dan desain:** memetakan permintaan/respons endpoint, alur sesi, aturan validasi, hak akses, dan kondisi gagal bersama System Analyst dan Frontend.
2. **Mesin diagnosis:** mengelola fakta dari jawaban, membaca aturan dan mencocokkan gejala, menentukan hasil serta solusi, dan menangani sesi yang selesai, dibatalkan, atau tidak menemukan aturan.
3. **API dan integrasi:** mengembangkan atau memeriksa endpoint diagnosis, autentikasi, basis pengetahuan, sesi, tiket, laporan, pengguna, dan pengaturan. Menghubungkan webhook masuk serta pengiriman balasan WhatsApp.
4. **Tiket dan keamanan:** memastikan kriteria pembuatan tiket sesuai data kerusakan; memvalidasi input, menggunakan query terparameterisasi, memeriksa otorisasi server, dan menghindari pencatatan rahasia.
5. **Pengujian dan perbaikan:** membuat contoh permintaan dan respons, menelusuri kegagalan bersama QA, memperbaiki penyebab, dan memeriksa dampak terhadap alur lain.

**Hasil yang perlu diserahkan:** daftar endpoint beserta metode, parameter, contoh respons dan kesalahan; pemetaan aturan ke hasil; perubahan kode dan penjelasan konfigurasi; daftar batasan yang belum ditangani.

**Checklist tambahan:**

- [ ] Menyepakati format respons dan status kesalahan API dengan Frontend.
- [ ] Memeriksa sesi terpisah agar jawaban satu pengguna tidak tercampur dengan pengguna lain.
- [ ] Memeriksa kondisi jawaban tidak valid, sesi dibatalkan, aturan tidak cocok, dan database tidak tersedia.
- [ ] Memastikan perubahan tiket dan basis pengetahuan diperiksa hak aksesnya di server.
- [ ] Memeriksa respons webhook dan kegagalan pengiriman tanpa membocorkan token pada log.
- [ ] Mendokumentasikan perubahan API dan membantu QA mereproduksi temuan.


#### Rencana pelaksanaan terperinci dan hasil kerja

**Bahan yang harus diterima sebelum bekerja:** Kontrak kebutuhan dari Analyst, aturan yang disetujui pakar, skema dari Database Engineer, dan rancangan respons untuk Frontend.

**Paket 2.1 — Membangun alur diagnosis**

- Langkah pekerjaan: Telusuri processInput, pemetaan jawaban ke G01–G10, pencocokan aturan, dan finalizeConclusion. Tentukan perilaku untuk input kosong, angka di luar pilihan, reset di tengah sesi, dan percakapan sesudah selesai. Pastikan perubahan cabang tidak merusak jalur lama.
- Produk yang dibuat: Kode mesin diagnosis beserta contoh input, fakta, hasil, dan kasus kegagalan.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Paket 2.2 — Mengembangkan layanan dashboard**

- Langkah pekerjaan: Susun kontrak baca tiket/laporan serta perubahan status dan administrasi. Validasi data di server, periksa izin tindakan, dan bedakan data tidak ditemukan dari data tidak berubah. Tinjau konsistensi hasil yang dibaca dari sesi dan tiket.
- Produk yang dibuat: Endpoint yang konsisten, dokumentasi respons, dan pemeriksaan akses yang dapat diuji.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Paket 2.3 — Menangani integrasi dan konsistensi**

- Langkah pekerjaan: Hubungkan webhook ke mesin dan balasan WhatsApp. Rancang penanganan pengiriman gagal dan peristiwa duplikat sesuai kebutuhan. Periksa risiko tiket tersimpan tetapi sesi gagal diperbarui; sepakati kebutuhan transaksi dengan Database Engineer.
- Produk yang dibuat: Implementasi integrasi dan catatan penanganan kegagalan; mekanisme baru hanya dinyatakan tersedia setelah diimplementasikan.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Kriteria selesai untuk peran ini:** Alur berhasil dan gagal dapat diperiksa; hasil sesuai aturan; perubahan data konsisten; hak akses diuji di endpoint.

**Batas tanggung jawab:** Tidak mengubah pengetahuan diagnosis tanpa validasi Analyst dan pakar; frontend bukan pengganti otorisasi server.


### 6.3 UI/UX Designer

**Fitur atau paket pekerjaan yang ditangani secara konkret:**

1. Percakapan: membuat naskah sapaan, pertanyaan indikator modem, pilihan jawaban, solusi, dan pesan kebutuhan bantuan manual.
2. Simulator: merancang gelembung percakapan, pilihan cepat, indikator menunggu, dan tampilan hasil.
3. Dashboard operasional: merancang ringkasan, daftar tiket, pencarian, filter, detail, dan interaksi perubahan status.
4. Administrasi: merancang formulir gejala, kerusakan, aturan, pengguna, dan pengaturan termasuk konfirmasi tindakan dan pesan kesalahan.

**Cara menyerahkan dan memeriksa pekerjaan:** setiap paket diserahkan bersama penjelasan perubahan, lokasi hasil, ketergantungan, dan masalah yang belum selesai. Hasil ditinjau oleh pihak yang memakai hasil tersebut; pekerjaan yang memengaruhi perilaku aplikasi diperiksa melalui skenario uji yang disepakati. Checklist di bagian ini adalah rencana tugas, bukan bukti bahwa tugas telah selesai.


**Tanggung jawab utama:** merancang pengalaman pelanggan dan petugas yang jelas, mudah dipahami, dan konsisten, termasuk alur percakapan dan tampilan dashboard.

**Deliverables:** alur pengguna, wireframe/prototipe, rancangan tampilan dan komponen, serta catatan hasil evaluasi kegunaan.

**Tools/teknologi:** Figma atau alat desain sejenis, diagram alur, dan browser. Alat desain merupakan usulan sesuai kebutuhan tim.

**Timeline/fase:** menggali kebutuhan pada minggu 1; merancang dan meninjau prototipe pada minggu 2; mendampingi implementasi pada minggu 3–6; mengevaluasi pada minggu 7–8.

**Kolaborasi:** dengan System Analyst dan pengguna untuk kebutuhan; Frontend untuk implementasi; Backend untuk batasan alur; QA untuk pengujian kegunaan.

**Checklist tugas spesifik:**

- [ ] Memetakan perjalanan pelanggan dari memulai chat hingga memperoleh hasil.
- [ ] Merancang pertanyaan, pilihan jawaban, pesan kesalahan, dan instruksi yang mudah dipahami.
- [ ] Merancang login, ringkasan tiket, laporan, filter, dan detail pada dashboard.
- [ ] Memeriksa keterbacaan, konsistensi, serta penggunaan di ukuran layar berbeda.
- [ ] Menguji prototipe dengan perwakilan pengguna dan mencatat perbaikan.

**Rincian pekerjaan per fase:**

1. **Riset:** mengidentifikasi karakteristik pelanggan, CS, dan admin; memahami bahasa yang biasa digunakan untuk menjelaskan gangguan serta kendala pengguna.
2. **Perancangan alur:** membuat alur percakapan dari awal sampai hasil, termasuk pilihan yang tidak valid, pembatalan, dan eskalasi. Membuat alur CS untuk menemukan dan memperbarui tiket.
3. **Rancangan visual:** menyusun wireframe dan prototipe halaman login, ringkasan, tiket, laporan, administrasi, dan simulator. Menetapkan hierarki informasi, warna, ukuran teks, dan komponen.
4. **Validasi:** meminta perwakilan pengguna mencoba tugas nyata, mencatat titik kebingungan, dan memperbaiki rancangan berdasarkan temuan.
5. **Pendampingan implementasi:** menyerahkan aset dan spesifikasi kepada Frontend, lalu memeriksa konsistensi hasil implementasi pada berbagai ukuran layar.

**Hasil yang perlu diserahkan:** peta alur, wireframe/prototipe, daftar komponen dan statusnya, naskah pesan yang disetujui, serta catatan temuan dan revisi pengujian pengguna.

**Checklist tambahan:**

- [ ] Menggunakan istilah gejala yang dapat dipahami pelanggan dan divalidasi pakar.
- [ ] Merancang kondisi menunggu, berhasil, kosong, gagal, dan tidak ditemukan hasil.
- [ ] Menjelaskan langkah berikutnya bagi pelanggan ketika teknisi diperlukan.
- [ ] Memastikan status tiket mudah dibedakan tanpa bergantung hanya pada warna.
- [ ] Memeriksa ukuran sentuh, kontras, label formulir, dan keterbacaan di layar kecil.
- [ ] Menyerahkan ukuran, aset, dan perilaku komponen kepada Frontend.


#### Rencana pelaksanaan terperinci dan hasil kerja

**Bahan yang harus diterima sebelum bekerja:** Profil pengguna, alur diagnosis dan operasional, keterbatasan kanal WhatsApp, dan daftar fitur.

**Paket 3.1 — Menyusun pengalaman percakapan**

- Langkah pekerjaan: Tulis instruksi melihat LOS/PON/Power/WLAN dengan istilah sederhana. Bedakan teks pilihan, hasil sementara, solusi, dan kebutuhan tindak lanjut. Sediakan rancangan ketika pelanggan tidak mengerti indikator atau tidak menemukan pilihan yang sesuai.
- Produk yang dibuat: Naskah percakapan dan alur interaksi; cabang baru ditandai sebagai usulan sampai disepakati.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Paket 3.2 — Merancang pekerjaan CS**

- Langkah pekerjaan: Susun informasi prioritas pada daftar dan detail: nomor tiket, waktu, pelanggan, diagnosis, dan status. Buat langkah dari menemukan tiket sampai memperbarui status tanpa kehilangan konteks.
- Produk yang dibuat: Prototipe daftar, detail, filter, status, dan konfirmasi tindakan.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Paket 3.3 — Menyerahkan spesifikasi desain**

- Langkah pekerjaan: Dokumentasikan ukuran, warna, jarak, komponen, kondisi kosong/memuat/gagal, dan perilaku layar kecil. Tinjau implementasi menggunakan tugas pengguna nyata.
- Produk yang dibuat: Spesifikasi komponen dan catatan evaluasi kegunaan.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Kriteria selesai untuk peran ini:** Alur utama dapat diikuti pengguna uji; semua kondisi penting dirancang; spesifikasi cukup jelas untuk Frontend.

**Batas tanggung jawab:** Tidak menjanjikan perpindahan otomatis ke agen manusia apabila integrasinya belum tersedia.


### 6.4 Frontend Developer

**Fitur atau paket pekerjaan yang ditangani secara konkret:**

1. Simulator chat: mengimplementasikan tampilan dan interaksi dalam public/chat/, mengirim input ke API, dan menampilkan respons.
2. Dashboard tiket dan laporan: membuat pemuatan daftar, detail, pencarian, filter, perubahan status, serta grafik dalam public/dashboard/.
3. Halaman autentikasi: menghubungkan login, pemeriksaan sesi, logout, dan kondisi sesi berakhir dengan API.
4. Formulir administrasi: menghubungkan pengelolaan gejala, kerusakan, aturan, pengguna, sesi, dan pengaturan dengan endpoint yang tersedia.

**Cara menyerahkan dan memeriksa pekerjaan:** setiap paket diserahkan bersama penjelasan perubahan, lokasi hasil, ketergantungan, dan masalah yang belum selesai. Hasil ditinjau oleh pihak yang memakai hasil tersebut; pekerjaan yang memengaruhi perilaku aplikasi diperiksa melalui skenario uji yang disepakati. Checklist di bagian ini adalah rencana tugas, bukan bukti bahwa tugas telah selesai.


**Tanggung jawab utama:** mengimplementasikan antarmuka simulator chat dan dashboard serta menghubungkannya ke API.

**Deliverables:** halaman HTML, gaya CSS, perilaku JavaScript, integrasi API, dan grafik dashboard yang berfungsi.

**Tools/teknologi:** HTML, CSS, JavaScript, Chart.js, browser developer tools, dan Git.

**Timeline/fase:** meninjau desain/API pada minggu 2; membangun tampilan pada minggu 3–4; integrasi pada minggu 5–6; memperbaiki temuan pada minggu 7–8.

**Kolaborasi:** dengan UI/UX untuk desain; Backend untuk kontrak API dan kesalahan; QA untuk pengujian tampilan dan alur; Dokumentasi untuk tangkapan layar/panduan.

**Checklist tugas spesifik:**

- [ ] Membangun dan memeriksa simulator di `public/chat/`, termasuk respons dan pilihan cepat.
- [ ] Menghubungkan dashboard ke API autentikasi, tiket, laporan, dan administrasi.
- [ ] Menampilkan status, detail, pencarian/filter, dan grafik berdasarkan data yang tersedia.
- [ ] Menangani kondisi memuat, data kosong, dan kegagalan permintaan secara jelas.
- [ ] Memeriksa tampilan responsif dan keamanan tampilan data bersama Backend dan QA.

**Rincian pekerjaan per fase:**

1. **Persiapan:** mempelajari rancangan dan menyepakati kontrak API, format data, hak akses, serta perilaku saat permintaan gagal.
2. **Simulator:** membangun tampilan pesan pelanggan dan sistem, pengiriman jawaban, pilihan cepat, indikator proses, dan penanganan respons diagnosis.
3. **Dashboard:** mengimplementasikan login, daftar dan detail tiket/laporan, filter, perubahan status, pengelolaan yang tersedia, serta grafik berdasarkan data API.
4. **Integrasi dan kualitas:** menghubungkan permintaan asinkron, menampilkan kondisi kosong/gagal, memeriksa data yang ditampilkan, dan menjaga antarmuka responsif serta dapat digunakan.
5. **Perbaikan:** bekerja dengan QA dan UI/UX untuk menindaklanjuti bug, kesesuaian desain, dan perubahan kontrak API.

**Hasil yang perlu diserahkan:** halaman dan komponen yang berfungsi, integrasi terhadap endpoint yang disepakati, daftar kondisi tampilan, serta catatan dependensi dan batasan browser jika ada.

**Checklist tambahan:**

- [ ] Memeriksa pesan chat, pilihan jawaban, dan tampilan hasil pada simulator.
- [ ] Menyediakan umpan balik saat permintaan sedang berjalan atau gagal.
- [ ] Memastikan filter, pencarian, detail, dan perubahan status menggunakan data yang sesuai.
- [ ] Memastikan grafik memperbarui data tanpa menampilkan nilai yang menyesatkan.
- [ ] Memeriksa tampilan untuk admin dan CS sesuai respons hak akses server.
- [ ] Menguji tampilan pada layar ponsel dan desktop serta memperbaiki masalah aksesibilitas dasar.


#### Rencana pelaksanaan terperinci dan hasil kerja

**Bahan yang harus diterima sebelum bekerja:** Desain UI/UX, kontrak API, contoh data sukses/gagal, serta kebutuhan tampilan berdasarkan peran.

**Paket 4.1 — Mengerjakan simulator**

- Langkah pekerjaan: Bangun pengiriman pesan, tampilan balasan, pilihan cepat, indikator menunggu, dan hasil. Cegah kebingungan saat pengiriman gagal atau pengguna mengirim ulang. Pastikan riwayat tampilan dan sesi server dipahami sebagai dua hal berbeda.
- Produk yang dibuat: Simulator yang dapat menjalankan jalur diagnosis dan menunjukkan kegagalan dengan jelas.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Paket 4.2 — Mengerjakan dashboard operasional**

- Langkah pekerjaan: Hubungkan daftar, filter, pencarian, detail, dan perubahan status. Sesuaikan statistik dengan sumber data tiket atau laporan agar jumlah diagnosis tidak disalahartikan sebagai jumlah tiket.
- Produk yang dibuat: Halaman pemantauan, detail, interaksi status, serta grafik yang cocok dengan data.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Paket 4.3 — Mengerjakan administrasi**

- Langkah pekerjaan: Implementasikan formulir gejala, kerusakan, aturan, pengguna, dan pengaturan sesuai API. Tampilkan validasi dan kesalahan relasi data. Minta konfirmasi untuk tindakan penghapusan sesuai desain.
- Produk yang dibuat: Formulir dan umpan balik yang dapat digunakan tanpa menebak hasil operasi.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Kriteria selesai untuk peran ini:** Tampilan cocok dengan respons server; kondisi kosong/gagal tertangani; interaksi diperiksa pada perangkat sasaran.

**Batas tanggung jawab:** Penyembunyian menu hanya mengatur pengalaman pengguna; izin tetap harus diperiksa Backend.


### 6.5 Dokumentasi

**Fitur atau paket pekerjaan yang ditangani secara konkret:**

1. Panduan pelanggan: menjelaskan cara memulai diagnosis, menjawab pertanyaan, membaca solusi, dan meminta tindak lanjut.
2. Panduan CS/admin: membuat langkah login, membaca tiket/laporan, memperbarui status, dan menggunakan administrasi sesuai kewenangan.
3. Referensi teknis: mendokumentasikan struktur folder, alur data, endpoint, konfigurasi, dan batasan mesin diagnosis.
4. Paket serah terima: menyusun riwayat perubahan, bukti uji dari QA, panduan operasional dari DevOps, dan daftar kendala yang diketahui.

**Cara menyerahkan dan memeriksa pekerjaan:** setiap paket diserahkan bersama penjelasan perubahan, lokasi hasil, ketergantungan, dan masalah yang belum selesai. Hasil ditinjau oleh pihak yang memakai hasil tersebut; pekerjaan yang memengaruhi perilaku aplikasi diperiksa melalui skenario uji yang disepakati. Checklist di bagian ini adalah rencana tugas, bukan bukti bahwa tugas telah selesai.


**Tanggung jawab utama:** menyusun dan memperbarui dokumen teknis maupun pengguna agar aplikasi mudah dipahami, dijalankan, diuji, dan diserahkan.

**Deliverables:** dokumentasi proyek dan jobdesk, panduan instalasi dan konfigurasi, panduan pengguna, dokumentasi API, catatan pengujian, serta dokumen serah terima.

**Tools/teknologi:** Markdown, editor teks, Git, alat tangkapan layar, dan dokumen bersama bila diperlukan.

**Timeline/fase:** mencatat kebutuhan sejak minggu 1; memperbarui rancangan dan implementasi pada minggu 2–6; memfinalkan panduan dan hasil pengujian pada minggu 7–8; menjaga dokumen selama pemeliharaan.

**Kolaborasi:** dengan Project Manager untuk laporan; System Analyst untuk kebutuhan; pengembang dan DevOps untuk instruksi teknis; QA untuk hasil uji dan pengguna untuk panduan.

**Checklist tugas spesifik:**

- [ ] Menjelaskan tujuan, fitur, alur diagnosis, dan pembagian peran FiberAssist.
- [ ] Memverifikasi langkah instalasi dan penggunaan berdasarkan lingkungan yang benar-benar diuji.
- [ ] Mendokumentasikan endpoint, input/output, dan kondisi kesalahan bersama Backend.
- [ ] Menyusun panduan dashboard, simulator, dan konfigurasi WhatsApp tanpa mencantumkan rahasia.
- [ ] Memperbarui dokumen setelah perubahan serta menyiapkan materi serah terima.

**Rincian pekerjaan per fase:**

1. **Pengumpulan informasi:** mencatat tujuan, pengguna, kebutuhan, keputusan, istilah, serta sumber informasi dari setiap penanggung jawab.
2. **Dokumentasi rancangan:** menjelaskan arsitektur, alur diagnosis, aturan yang telah disetujui, alur tiket, struktur data, dan tanggung jawab tim.
3. **Dokumentasi teknis:** menyusun langkah instalasi, konfigurasi, endpoint, dependensi, cara menjalankan dan menghentikan aplikasi, serta pemecahan masalah berdasarkan langkah yang diverifikasi.
4. **Panduan pengguna:** menulis langkah memakai simulator, mengakses dashboard, membaca laporan, dan menindaklanjuti tiket dengan bahasa yang sesuai pembaca.
5. **Serah terima:** mengumpulkan hasil pengujian, batasan, kontak penanggung jawab yang disepakati, dan prosedur operasional; memperbarui dokumen ketika aplikasi berubah.

**Hasil yang perlu diserahkan:** dokumen proyek dan jobdesk, panduan instalasi serta pengguna, referensi API, riwayat perubahan, dan daftar dokumen serah terima. Penanggung jawab teknis meninjau kebenaran materi masing-masing.

**Checklist tambahan:**

- [ ] Mencatat versi atau tanggal pembaruan dan penanggung jawab peninjauan dokumen.
- [ ] Menjelaskan prasyarat dan contoh penggunaan tanpa menyertakan token atau kata sandi.
- [ ] Mencoba langkah panduan bersama anggota yang belum melakukan instalasi.
- [ ] Memastikan gambar dan istilah sesuai tampilan aplikasi terkini.
- [ ] Membedakan fitur yang tersedia, rencana pengembangan, dan hasil pengujian aktual.
- [ ] Menyediakan indeks dokumen agar pembaca mudah menemukan panduan terkait.


#### Rencana pelaksanaan terperinci dan hasil kerja

**Bahan yang harus diterima sebelum bekerja:** Kebutuhan yang disepakati, kode dan konfigurasi yang telah ditinjau, hasil uji, serta rancangan dan keputusan tim.

**Paket 5.1 — Membuat referensi sistem**

- Langkah pekerjaan: Jelaskan hubungan WhatsApp/simulator, API, mesin aturan, database, dan dashboard. Cantumkan lokasi komponen dan bedakan perilaku aktual dari rencana.
- Produk yang dibuat: Dokumen arsitektur deskriptif, istilah, alur data, dan batasan.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Paket 5.2 — Membuat panduan berbasis tugas**

- Langkah pekerjaan: Susun panduan “memulai diagnosis”, “mencari tiket”, “mengubah status”, dan “memperbarui aturan”. Tuliskan prasyarat, langkah, hasil yang diharapkan, dan tindakan ketika gagal.
- Produk yang dibuat: Panduan pelanggan, CS, admin, pengembang, dan operator dengan pembaca yang jelas.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Paket 5.3 — Mengendalikan versi dokumen**

- Langkah pekerjaan: Periksa perubahan fitur dan sinkronkan panduan. Minta pemilik teknis memverifikasi langkah. Hapus contoh kredensial nyata dan gunakan penanda pengganti.
- Produk yang dibuat: Catatan revisi, sumber verifikasi, dan paket serah terima yang konsisten.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Kriteria selesai untuk peran ini:** Panduan telah dicoba; istilah konsisten; sumber teknis ditinjau pemilik; tidak ada klaim uji tanpa bukti.

**Batas tanggung jawab:** Tidak menulis hasil pengujian atau validasi pakar yang belum dilakukan sebagai keberhasilan aktual.


### 6.6 DevOps / Infrastructure

**Fitur atau paket pekerjaan yang ditangani secara konkret:**

1. Lingkungan aplikasi: menyiapkan Apache/PHP, akses database, routing aplikasi, dan direktori yang diperlukan.
2. Konektivitas WhatsApp: menyiapkan endpoint publik untuk uji, membantu verifikasi webhook, dan memeriksa ketersediaan layanan.
3. Operasional: menyusun pemeriksaan kesehatan layanan, pembacaan log, pengelolaan kredensial, backup, dan pemulihan.
4. Rilis: membuat langkah deployment serta rollback yang dapat diikuti dan dicoba pada lingkungan uji.

**Cara menyerahkan dan memeriksa pekerjaan:** setiap paket diserahkan bersama penjelasan perubahan, lokasi hasil, ketergantungan, dan masalah yang belum selesai. Hasil ditinjau oleh pihak yang memakai hasil tersebut; pekerjaan yang memengaruhi perilaku aplikasi diperiksa melalui skenario uji yang disepakati. Checklist di bagian ini adalah rencana tugas, bukan bukti bahwa tugas telah selesai.


**Tanggung jawab utama:** menyiapkan lingkungan aplikasi dan konektivitas, mengelola konfigurasi serta mendukung pemantauan, pencadangan, dan rilis.

**Deliverables:** panduan lingkungan dan deployment, konfigurasi server/webhook, prosedur pencadangan dan pemulihan, serta daftar pemeriksaan operasional.

**Tools/teknologi:** Apache, PHP, MySQL/MariaDB, shell, Git, log aplikasi, serta localtunnel untuk pengujian lokal. Mekanisme deployment produksi dipilih sesuai lingkungan target.

**Timeline/fase:** menyiapkan lingkungan pada minggu 1–3; mendukung integrasi pada minggu 5–6; memeriksa kesiapan rilis pada minggu 7–8; memantau setelah rilis.

**Kolaborasi:** dengan Backend untuk konfigurasi dan webhook; Database Engineer untuk backup; QA untuk lingkungan uji; Dokumentasi dan Project Manager untuk serah terima.

**Checklist tugas spesifik:**

- [ ] Menyiapkan Apache, PHP, database, dan akses aplikasi di lingkungan pengembangan.
- [ ] Meninjau `start.sh`, `stop.sh`, dan konfigurasi sesuai sistem yang digunakan.
- [ ] Menghubungkan dan menguji webhook WhatsApp yang dapat diakses Meta; gunakan tunnel sesuai kebutuhan pengujian.
- [ ] Menyimpan kredensial dengan aman, membatasi akses, dan memeriksa log tanpa membocorkan rahasia.
- [ ] Menguji backup dan pemulihan serta mencatat prosedur rilis dan pemantauan.

**Rincian pekerjaan per fase:**

1. **Perencanaan lingkungan:** mengidentifikasi versi dan dependensi, kebutuhan server, akses jaringan, konfigurasi database, serta perbedaan lingkungan pengembangan, uji, dan target rilis.
2. **Penyiapan:** memasang dan mengonfigurasi layanan yang diperlukan; memeriksa akses aplikasi, hak file, koneksi database, serta variabel dan kredensial.
3. **Integrasi webhook:** menyiapkan endpoint yang dapat dijangkau Meta, memeriksa verifikasi webhook dan aliran pesan bersama Backend, serta mencatat keterbatasan tunnel untuk pengujian.
4. **Operasional:** menyiapkan pemeriksaan layanan, pengelolaan log, backup dan latihan pemulihan bersama Database Engineer. Menentukan langkah deployment dan rollback sesuai lingkungan target.
5. **Serah terima:** menjalankan pemeriksaan kesiapan, mendokumentasikan prosedur mulai/berhenti dan penanganan gangguan, serta menyampaikan akses kepada pihak berwenang.

**Hasil yang perlu diserahkan:** daftar prasyarat dan konfigurasi, prosedur deployment/rollback, panduan webhook, rencana pemantauan, serta bukti hasil uji backup dan pemulihan. Infrastruktur produksi harus disesuaikan dengan kebijakan dan lingkungan penerapan yang dipilih.

**Checklist tambahan:**

- [ ] Mencatat versi PHP, Apache, database, dan ekstensi yang diperlukan setelah diverifikasi.
- [ ] Memeriksa akses jaringan dan konfigurasi webhook di lingkungan yang digunakan.
- [ ] Mengelola token dan kredensial di lokasi yang aman serta membatasi aksesnya.
- [ ] Memastikan log berguna untuk diagnosis dan tidak membuka informasi sensitif.
- [ ] Mendokumentasikan siapa yang menerima notifikasi gangguan dan langkah eskalasinya.
- [ ] Melakukan latihan pemulihan database dan mendokumentasikan hasilnya bersama Database Engineer.


#### Rencana pelaksanaan terperinci dan hasil kerja

**Bahan yang harus diterima sebelum bekerja:** Prasyarat aplikasi, lingkungan sasaran, konfigurasi integrasi, kebutuhan akses dan pemulihan.

**Paket 6.1 — Menyiapkan lingkungan dapat diulang**

- Langkah pekerjaan: Catat versi layanan, ekstensi PHP, lokasi aplikasi, routing, akun layanan, dan akses database. Verifikasi skrip mulai/berhenti sesuai host; jangan mengasumsikan semua mesin memiliki konfigurasi sama.
- Produk yang dibuat: Panduan lingkungan dan langkah setup yang sudah dicoba.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Paket 6.2 — Menyiapkan webhook dan operasional**

- Langkah pekerjaan: Periksa endpoint dapat dijangkau, konfigurasi verifikasi sesuai, dan pesan tiba di aplikasi. Bedakan kegagalan jaringan, konfigurasi Meta, aplikasi, dan pengiriman balasan.
- Produk yang dibuat: Panduan diagnosis gangguan integrasi dan lokasi log relevan.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Paket 6.3 — Menyiapkan pemulihan**

- Langkah pekerjaan: Sepakati backup database dan konfigurasi yang diperlukan. Lakukan pemulihan di lingkungan terpisah dan periksa aplikasi dapat membaca hasil. Susun langkah rollback perubahan versi.
- Produk yang dibuat: Backup yang dapat dipulihkan, catatan uji pemulihan, dan runbook rilis.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Kriteria selesai untuk peran ini:** Lingkungan dapat disiapkan ulang; akses berfungsi; jalur pemulihan diuji; pemilik operasional diketahui.

**Batas tanggung jawab:** Tunnel pengembangan tidak dijanjikan selalu tersedia atau menjadi infrastruktur produksi permanen.


### 6.7 Database Engineer

**Fitur atau paket pekerjaan yang ditangani secara konkret:**

1. Basis pengetahuan: merancang dan memeriksa tabel gejala, kerusakan, aturan, serta hubungan antarentitas.
2. Data transaksi: menyiapkan struktur sesi dan tiket, memastikan hubungan hasil diagnosis dengan tindak lanjut dapat ditelusuri.
3. Data administrasi: meninjau pengguna dan pengaturan bersama Backend, termasuk tabel yang dibuat saat aplikasi berjalan.
4. Pemeliharaan data: membuat skrip perubahan terkontrol, data uji, pemeriksaan konsistensi, dan prosedur pemulihan.

**Cara menyerahkan dan memeriksa pekerjaan:** setiap paket diserahkan bersama penjelasan perubahan, lokasi hasil, ketergantungan, dan masalah yang belum selesai. Hasil ditinjau oleh pihak yang memakai hasil tersebut; pekerjaan yang memengaruhi perilaku aplikasi diperiksa melalui skenario uji yang disepakati. Checklist di bagian ini adalah rencana tugas, bukan bukti bahwa tugas telah selesai.


**Tanggung jawab utama:** merancang, menyiapkan, dan menjaga integritas data aplikasi serta mendukung kebutuhan query dan pemulihan.

**Deliverables:** rancangan relasi data, skema dan data awal SQL, strategi migrasi, dokumentasi struktur data, serta prosedur backup/pemulihan yang diuji.

**Tools/teknologi:** MySQL/MariaDB, SQL, klien database, Git, dan alat pemodelan ERD sesuai pilihan tim.

**Timeline/fase:** memodelkan data pada minggu 1–2; menyiapkan skema dan dataset pada minggu 3–4; mendukung integrasi dan pengujian pada minggu 5–7; memeriksa backup pada minggu 8.

**Kolaborasi:** dengan System Analyst dan pakar untuk struktur pengetahuan; Backend untuk query dan perubahan skema; DevOps untuk backup; QA untuk data uji.

**Checklist tugas spesifik:**

- [ ] Meninjau tabel gejala, kerusakan, aturan, sesi, tiket, dan pengguna pada dataset SQL.
- [ ] Mendokumentasikan relasi, kunci, batasan, dan data awal bersama Backend.
- [ ] Memastikan aturan merujuk gejala dan kerusakan yang benar serta divalidasi pakar.
- [ ] Menyiapkan data uji yang tidak mengungkap data pelanggan sebenarnya.
- [ ] Menguji impor, konsistensi data, backup, dan pemulihan pada lingkungan uji.

**Rincian pekerjaan per fase:**

1. **Analisis data:** mengidentifikasi entitas, atribut, hubungan, dan siklus hidup data bersama System Analyst dan Backend, termasuk hubungan aturan dengan gejala dan kerusakan.
2. **Perancangan:** menyusun ERD dan kamus data, menetapkan kunci, batasan, jenis data, dan strategi perubahan skema. Memeriksa tabel yang dibuat oleh skrip SQL maupun aplikasi.
3. **Implementasi:** menyiapkan skema, data awal, dan akun database sesuai kebutuhan; bekerja dengan Backend untuk query dan transaksi yang relevan.
4. **Validasi:** memeriksa kelengkapan dan konsistensi dataset bersama pakar, menyiapkan data uji, dan menguji impor pada lingkungan bersih.
5. **Operasional:** menyepakati frekuensi dan retensi backup dengan tim operasional, menguji pemulihan, dan meninjau dampak perubahan data/skema sebelum diterapkan.

**Hasil yang perlu diserahkan:** ERD, kamus data, skrip skema dan data awal, catatan perubahan, data uji, serta prosedur dan hasil uji pemulihan.

**Checklist tambahan:**

- [ ] Mendokumentasikan arti kolom, tipe, kunci, dan hubungan pada setiap tabel utama.
- [ ] Mengidentifikasi perbedaan antara skema SQL awal dan tabel yang dibuat aplikasi.
- [ ] Memeriksa data aturan yang hilang, duplikat, atau merujuk entitas yang tidak sesuai.
- [ ] Memastikan data uji mencakup diagnosis yang menghasilkan tiket dan yang tidak.
- [ ] Meninjau hak akses akun database sesuai tugas aplikasi dan operasional.
- [ ] Memastikan hasil pemulihan dapat dibaca aplikasi pada lingkungan uji.


#### Rencana pelaksanaan terperinci dan hasil kerja

**Bahan yang harus diterima sebelum bekerja:** Entitas dan kebutuhan data, kode query Backend, dataset pakar, dan kebutuhan retensi/pemulihan.

**Paket 7.1 — Menyelaraskan skema aktual**

- Langkah pekerjaan: Bandingkan dataset SQL dengan query aplikasi dan perubahan saat runtime. Misalnya laporan membaca hasil_diagnosa, sehingga kolom tersebut harus masuk inventaris skema aktual walaupun tidak terlihat pada CREATE TABLE awal sesi.
- Produk yang dibuat: Kamus data dan daftar migrasi yang mencerminkan kebutuhan aplikasi.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Paket 7.2 — Menjaga referensi aturan**

- Langkah pekerjaan: Periksa kode gejala/kerusakan, pasangan aturan, serta dampak penghapusan. Pada dataset, penghapusan referensi gejala/kerusakan dapat menghapus aturan melalui cascade; jelaskan dampaknya sebelum administrasi digunakan.
- Produk yang dibuat: Dataset konsisten dan prosedur perubahan basis pengetahuan.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Paket 7.3 — Menyiapkan data uji dan pemulihan**

- Langkah pekerjaan: Buat data kasus tiket, diagnosis tanpa tiket, sesi aktif/selesai, dan perubahan status. Hindari data pelanggan nyata. Verifikasi jumlah serta relasi data setelah restore.
- Produk yang dibuat: Dataset uji dan laporan pemeriksaan integritas setelah pemulihan.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Kriteria selesai untuk peran ini:** Skema mendukung query; referensi valid; impor dan restore diuji; dampak perubahan dapat ditelusuri.

**Batas tanggung jawab:** Tidak menentukan makna gejala atau solusi sendiri; validasi domain menjadi tanggung jawab pakar bersama Analyst.


### 6.8 System Analyst

**Fitur atau paket pekerjaan yang ditangani secara konkret:**

1. Spesifikasi diagnosis: membuat pohon keputusan atau deskripsi langkah, daftar fakta, aturan, hasil, dan kondisi tanpa kecocokan.
2. Spesifikasi tiket: menetapkan kapan tiket dibuat, informasi yang dibutuhkan CS, makna status, dan alur tindak lanjut.
3. Spesifikasi pengguna: menentukan kewenangan pelanggan, CS, admin, serta batas pekerjaan teknisi di luar aplikasi.
4. Spesifikasi penerimaan: membuat contoh masukan dan hasil yang diharapkan untuk simulator, dashboard, integrasi, dan administrasi.

**Cara menyerahkan dan memeriksa pekerjaan:** setiap paket diserahkan bersama penjelasan perubahan, lokasi hasil, ketergantungan, dan masalah yang belum selesai. Hasil ditinjau oleh pihak yang memakai hasil tersebut; pekerjaan yang memengaruhi perilaku aplikasi diperiksa melalui skenario uji yang disepakati. Checklist di bagian ini adalah rencana tugas, bukan bukti bahwa tugas telah selesai.


**Tanggung jawab utama:** menerjemahkan kebutuhan pengguna dan pengetahuan pakar menjadi spesifikasi, proses bisnis, aturan, dan kriteria penerimaan yang dapat dibangun dan diuji.

**Deliverables:** spesifikasi kebutuhan, aktor dan alur proses, aturan diagnosis dan tabel keputusan, kebutuhan data/API, serta kriteria penerimaan.

**Tools/teknologi:** wawancara dan observasi, diagram alur/use case, spreadsheet atau dokumen untuk aturan, dan alat pemodelan sesuai kesepakatan tim.

**Timeline/fase:** memimpin analisis pada minggu 1; merinci dan memvalidasi rancangan pada minggu 2; mendampingi pembangunan pada minggu 3–6; memvalidasi hasil bersama pengguna dan QA pada minggu 7–8.

**Kolaborasi:** dengan pengguna, CS, dan pakar jaringan untuk kebutuhan; Project Manager untuk ruang lingkup; Backend dan Database Engineer untuk implementasi aturan; UI/UX dan QA untuk alur dan penerimaan.

**Checklist tugas spesifik:**

- [ ] Mengidentifikasi aktor, kebutuhan, batasan, dan alur gangguan fiber optik.
- [ ] Mengumpulkan gejala, kerusakan, solusi, serta kondisi kebutuhan teknisi dari pakar.
- [ ] Menyusun dan memvalidasi tabel aturan serta kasus tanpa kecocokan.
- [ ] Merinci alur sesi, hasil diagnosis, pembuatan tiket, dan tindak lanjut CS.
- [ ] Menetapkan kriteria penerimaan yang dapat diuji dan mengelola perubahan kebutuhan.

**Rincian pekerjaan per fase:**

1. **Penggalian:** mewawancarai pelanggan, CS, admin, dan pakar untuk mengetahui gangguan umum, proses penanganan saat ini, kebutuhan data, dan kendala.
2. **Analisis:** menyusun aktor, proses bisnis, kebutuhan fungsional dan nonfungsional, batasan sistem, prioritas, serta kondisi yang berada di luar cakupan.
3. **Basis pengetahuan:** mengubah pengetahuan pakar menjadi daftar gejala, pertanyaan, jenis kerusakan, solusi, dan aturan; meminta pakar memeriksa contoh dan pengecualiannya.
4. **Spesifikasi:** merinci alur sesi, kondisi tiket, peran dan hak akses, kebutuhan laporan, serta kriteria penerimaan untuk setiap alur utama.
5. **Validasi perubahan:** menjawab pertanyaan pengembang, menilai dampak perubahan kebutuhan, membantu QA menyusun hasil yang diharapkan, dan mendampingi penerimaan pengguna.

**Hasil yang perlu diserahkan:** daftar kebutuhan dan prioritas, diagram proses/use case, tabel keputusan yang divalidasi pakar, kamus istilah, dan kriteria penerimaan yang dapat diuji.

**Checklist tambahan:**

- [ ] Mencatat sumber setiap aturan dan tanggal/pihak yang memvalidasinya.
- [ ] Menentukan pertanyaan dan kemungkinan jawaban untuk setiap tahapan diagnosis.
- [ ] Merinci kondisi diagnosis tidak pasti atau aturan tidak cocok serta tindakan lanjutannya.
- [ ] Menentukan informasi yang diperlukan CS untuk menindaklanjuti tiket.
- [ ] Menyepakati kewenangan admin dan CS untuk tiap fitur bersama pemilik proses.
- [ ] Menghubungkan kebutuhan dengan kasus uji agar perubahan dapat ditelusuri.


#### Rencana pelaksanaan terperinci dan hasil kerja

**Bahan yang harus diterima sebelum bekerja:** Masalah pelanggan, proses layanan CS, pengetahuan pakar, dan kemampuan sistem saat ini.

**Paket 8.1 — Mendefinisikan kebutuhan terukur**

- Langkah pekerjaan: Ubah kebutuhan umum menjadi skenario: aktor, pemicu, masukan, langkah, hasil, pengecualian, dan kriteria penerimaan. Pisahkan kebutuhan fungsional dari sasaran kualitas yang harus disepakati nilainya.
- Produk yang dibuat: Spesifikasi kebutuhan yang bisa dipakai pengembang dan QA.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Paket 8.2 — Memvalidasi basis pengetahuan**

- Langkah pekerjaan: Tinjau delapan aturan awal bersama pakar. Periksa asumsi cabang tagihan, kondisi tanpa kecocokan, dan istilah kebutuhan teknisi untuk kasus billing. Catat tingkat kepastian sebagai sifat diagnosis awal, tanpa membuat persentase akurasi palsu.
- Produk yang dibuat: Aturan dan penjelasan domain dengan sumber serta keputusan validasi.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Paket 8.3 — Menetapkan proses operasional**

- Langkah pekerjaan: Definisikan siapa membaca tiket, apa arti OPEN/IN_PROGRESS/RESOLVED, kapan status diperbarui, dan bagaimana hasil lapangan kembali menjadi masukan aturan.
- Produk yang dibuat: Prosedur layanan dan kriteria penerimaan per fitur.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Kriteria selesai untuk peran ini:** Setiap fitur memiliki skenario dan pengecualian; aturan disetujui pakar; kebutuhan dapat ditelusuri ke pengujian.

**Batas tanggung jawab:** Tidak menganggap hasil inferensi sebagai bukti pengukuran jaringan atau status tagihan yang terverifikasi.


### 6.9 Quality Assurance (QA)

**Fitur atau paket pekerjaan yang ditangani secara konkret:**

1. Paket uji diagnosis: menyusun jalur input untuk tiap cabang, aturan, jawaban salah, reset, dan hasil tanpa kecocokan.
2. Paket uji dashboard: menguji login, daftar, detail, pencarian/filter, grafik, perubahan status, dan formulir administrasi.
3. Paket uji integrasi: memeriksa input WhatsApp sampai hasil tersimpan, balasan diterima, dan tiket tampil sesuai aturan.
4. Paket uji regresi: mengulang skenario terdampak setelah perubahan aturan, skema, API, atau antarmuka, dan menyimpan bukti hasil.

**Cara menyerahkan dan memeriksa pekerjaan:** setiap paket diserahkan bersama penjelasan perubahan, lokasi hasil, ketergantungan, dan masalah yang belum selesai. Hasil ditinjau oleh pihak yang memakai hasil tersebut; pekerjaan yang memengaruhi perilaku aplikasi diperiksa melalui skenario uji yang disepakati. Checklist di bagian ini adalah rencana tugas, bukan bukti bahwa tugas telah selesai.


**Tanggung jawab utama:** merencanakan dan melaksanakan pengujian, mencatat temuan, memverifikasi perbaikan, dan menyampaikan risiko kualitas sebelum serah terima.

**Deliverables:** rencana dan kasus uji, data uji, laporan bug, hasil pengujian ulang, dan ringkasan kesiapan penerimaan.

**Tools/teknologi:** browser developer tools, Postman/curl atau alat penguji HTTP, MySQL/MariaDB untuk pemeriksaan data uji, dan papan pencatatan masalah. Otomasi dapat ditambahkan jika sesuai kebutuhan.

**Timeline/fase:** menyusun strategi dan kasus uji pada minggu 2; menguji komponen sejak minggu 3; menguji integrasi pada minggu 5–6; menjalankan regresi dan penerimaan pada minggu 7–8.

**Kolaborasi:** dengan System Analyst dan pakar untuk hasil diagnosis yang diharapkan; pengembang untuk perbaikan; DevOps dan Database Engineer untuk lingkungan/data uji; Project Manager dan pengguna untuk prioritas dan penerimaan.

**Checklist tugas spesifik:**

- [ ] Menguji setiap aturan diagnosis terhadap hasil yang telah disetujui pakar, termasuk input tidak valid dan tanpa kecocokan.
- [ ] Menguji sesi baru, lanjutan, reset, dan hasil diagnosis pada simulator serta WhatsApp yang terkonfigurasi.
- [ ] Menguji pembuatan dan perubahan status tiket, laporan, filter, dan grafik.
- [ ] Menguji login, hak akses admin/CS, validasi input, dan kondisi kegagalan API/webhook.
- [ ] Mencatat langkah reproduksi, hasil yang diharapkan dan aktual; menguji ulang perbaikan serta regresi.

**Rincian pekerjaan per fase:**

1. **Perencanaan:** meninjau kebutuhan dan risiko, menetapkan cakupan dan pendekatan pengujian, serta menyiapkan lingkungan, akun, dan data uji.
2. **Perancangan kasus uji:** menuliskan kondisi awal, langkah, data masukan, hasil yang diharapkan, dan sumber persetujuan aturan pakar untuk setiap skenario.
3. **Pelaksanaan:** menguji komponen dan alur menyeluruh, termasuk simulator, dashboard, API, database, dan integrasi WhatsApp secara terpisah maupun bersama.
4. **Manajemen temuan:** mencatat bukti dan langkah reproduksi, tingkat dampak dan prioritas bersama tim, lalu memverifikasi perbaikan dan menjalankan regresi yang relevan.
5. **Penerimaan:** mendampingi pengguna melakukan skenario utama, merangkum hasil dan risiko tersisa, serta memberikan masukan kepada pihak yang berwenang mengambil keputusan rilis.

**Hasil yang perlu diserahkan:** rencana uji, daftar kasus dan data uji, laporan temuan, bukti pengujian ulang, serta ringkasan hasil dan risiko yang belum terselesaikan.

**Checklist tambahan:**

- [ ] Menelusuri setiap kebutuhan utama ke satu atau lebih kasus uji.
- [ ] Memeriksa seluruh kombinasi aturan yang disepakati bersama pakar dan mendokumentasikan hasil aktual.
- [ ] Menguji sesi bersamaan, jawaban tidak valid, reset, dan kegagalan koneksi yang relevan.
- [ ] Memeriksa bahwa kasus yang memerlukan teknisi menghasilkan tiket sesuai aturan, termasuk risiko tiket ganda.
- [ ] Menguji akses langsung ke API menggunakan peran berbeda, bukan hanya tampilan menu.
- [ ] Memeriksa konsistensi data antara hasil diagnosis, tiket, laporan, dan dashboard.
- [ ] Menguji integrasi WhatsApp dengan akun dan konfigurasi uji yang tersedia, lalu mencatat keterbatasannya.
- [ ] Mengelompokkan temuan berdasarkan dampak, menguji ulang perbaikan, dan melaporkan risiko tersisa.


#### Rencana pelaksanaan terperinci dan hasil kerja

**Bahan yang harus diterima sebelum bekerja:** Spesifikasi kebutuhan, aturan tervalidasi, versi aplikasi, lingkungan uji, serta akun dan data uji.

**Paket 9.1 — Menyiapkan pengujian aturan**

- Langkah pekerjaan: Buat input berurutan untuk R01–R08 dan hasil yang diharapkan. Pisahkan kesesuaian dengan dataset dari kebenaran diagnosis dunia nyata yang membutuhkan pakar.
- Produk yang dibuat: Kasus uji tiap aturan beserta fakta, hasil, kebutuhan tiket, dan bukti.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Paket 9.2 — Menguji alur menyeluruh**

- Langkah pekerjaan: Jalankan percakapan, periksa hasil tersimpan, tiket jika diperlukan, tampilan dashboard, dan perubahan status. Ulangi lewat WhatsApp untuk membuktikan integrasi, bukan hanya simulator.
- Produk yang dibuat: Bukti pengujian antarkomponen dan daftar kegagalan yang terlokalisasi.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Paket 9.3 — Menguji perubahan dan risiko**

- Langkah pekerjaan: Uji input tidak valid, reset, sesi berbeda, akses langsung API, aturan yang dihapus, serta sumber data grafik. Setelah perbaikan, uji ulang kasus gagal dan regresi area terkait.
- Produk yang dibuat: Laporan temuan, hasil retest, hasil regresi, dan rekomendasi kesiapan.
- Bukti penyelesaian: hasil diperlihatkan kepada anggota terkait, kekurangan dicatat, dan revisi diperiksa sebelum paket ditutup.

**Kriteria selesai untuk peran ini:** Kasus utama dan pengecualian memiliki hasil aktual; bug dapat direproduksi; risiko terbuka disampaikan.

**Batas tanggung jawab:** QA memberi bukti dan rekomendasi, sedangkan keputusan penerimaan dibuat pihak berwenang bersama pengguna.


## 7. Kriteria Penerimaan yang Disarankan

- Alur diagnosis utama menghasilkan kesimpulan dan solusi sesuai kasus uji yang disetujui pakar; kasus tanpa aturan ditangani dengan jelas.
- Tiket dibuat untuk kasus yang memenuhi kriteria kebutuhan teknisi dan dapat dipantau serta diperbarui oleh petugas yang berwenang.
- Simulator, dashboard, database, dan API terintegrasi pada lingkungan uji; alur WhatsApp diuji terpisah dengan konfigurasi yang aktif.
- Hak akses, validasi input, dan kondisi kegagalan utama telah diuji; temuan dan risiko yang tersisa dicatat dan disepakati.
- Panduan penggunaan, konfigurasi, backup/pemulihan, dan hasil pengujian tersedia untuk serah terima.

Kriteria ini merupakan usulan untuk disepakati bersama pengguna dan pakar, bukan pernyataan bahwa semua pengujian telah lulus.

## 8. Latar Belakang, Tujuan, dan Manfaat Secara Terperinci

### 8.1 Latar belakang

Gangguan internet dapat memiliki keluhan awal yang sama tetapi penyebab berbeda. Pelanggan dapat menyebut internet mati, padahal informasi tambahan yang diperlukan adalah kondisi lampu modem dan perangkat yang terdampak. Tanpa alur pertanyaan yang konsisten, petugas perlu menggali informasi berulang kali dan catatan penanganan dapat berbeda antarpetugas.

FiberAssist menyusun penggalian informasi awal tersebut menjadi percakapan terarah. Sistem menyimpan fakta yang diberikan pelanggan dan menghubungkannya dengan pengetahuan diagnosis. Dashboard kemudian membantu petugas melihat hasil dan tiket yang membutuhkan tindak lanjut. Latar belakang ini adalah dasar perancangan; belum merupakan hasil observasi lapangan atau penelitian kuantitatif terhadap suatu ISP.

### 8.2 Tujuan umum dan khusus

Tujuan umum proyek adalah menyediakan bantuan diagnosis awal dan pencatatan tindak lanjut gangguan fiber optik melalui kanal yang mudah digunakan.

Tujuan khususnya adalah:

1. Menyeragamkan pengumpulan gejala untuk dua kelompok keluhan awal: internet mati total dan internet lambat.
2. Menggunakan aturan eksplisit agar hubungan masukan dengan hasil dapat dijelaskan dan ditinjau pakar.
3. Memberikan solusi berdasarkan data kerusakan yang cocok, sekaligus menyediakan jalur pengecekan manual ketika aturan tidak cocok.
4. Menyimpan hasil diagnosis sehingga petugas dapat menelusuri informasi awal pelanggan.
5. Membuat tiket berdasarkan kondisi eskalasi, lalu menyediakan pemantauan status tindak lanjut.
6. Memudahkan percobaan alur menggunakan simulator sebelum pengujian WhatsApp.
7. Menyediakan pengelolaan data pengetahuan dan pengguna melalui dashboard sesuai batas akses yang perlu diverifikasi.

### 8.3 Manfaat yang diharapkan dan cara menilainya

- **Pelanggan:** memperoleh pertanyaan yang lebih terarah. Penilaian dapat dilakukan dengan mengamati apakah pengguna memahami pertanyaan dan mampu menyelesaikan skenario.
- **CS:** memperoleh ringkasan gejala dan hasil awal sebelum tindak lanjut. Penilaian dapat dilakukan melalui uji tugas mencari dan memahami tiket.
- **Pakar/teknisi:** dapat memeriksa aturan yang digunakan dan memberi koreksi berdasarkan temuan lapangan. Penilaian memerlukan peninjauan kasus, bukan hanya keberhasilan kode berjalan.
- **Tim pengembang:** memiliki pemisahan komponen yang membantu penelusuran masalah. Penilaian dapat dilakukan dengan melihat keterlacakan kebutuhan, implementasi, dan pengujian.

Tidak ada angka penghematan waktu, tingkat akurasi, atau kapasitas pengguna yang dinyatakan telah tercapai dalam dokumen ini. Angka tersebut harus berasal dari pengukuran dengan metode dan data yang jelas.

## 9. Katalog Fitur dan Pembagian Pekerjaan Antartim

### 9.1 Fitur mulai, melanjutkan, dan mengulang diagnosis

**Tujuan:** memastikan pelanggan mengetahui posisi percakapan dan dapat kembali memulai diagnosis.

**Masukan:** identitas sesi dan pesan pelanggan. **Proses:** mengenali perintah, mencari atau membuat sesi, membaca langkah dan fakta, lalu menentukan balasan. **Keluaran:** pertanyaan berikutnya atau hasil.

Analyst mendefinisikan makna setiap perintah; UI/UX menyusun pesan; Backend mengelola sesi; Database Engineer memastikan data sesi dapat disimpan; Frontend mengimplementasikan simulator; QA menguji reset pada beberapa langkah; Dokumentasi menulis panduan; DevOps menyediakan lingkungan; PM mengatur penerimaan.

**Kriteria penerimaan usulan:** sesi pengguna berbeda tidak tercampur; reset mengembalikan alur sesuai spesifikasi; input salah tidak dianggap gejala sah; hasil perilaku sesudah sesi selesai terdokumentasi.

### 9.2 Fitur inferensi dan solusi

**Tujuan:** menghubungkan fakta dengan kesimpulan berdasarkan aturan.

Analyst dan pakar membuat keputusan domain; Database Engineer menyimpan gejala/aturan/kerusakan; Backend mengimplementasikan pencocokan dan penyajian hasil; UI/UX memastikan bahasa tidak menyatakan kepastian berlebihan; Frontend menampilkan hasil; QA memeriksa seluruh jalur; Dokumentasi menjelaskan batas metode.

**Kriteria penerimaan usulan:** fakta dan hasil sesuai dataset yang disetujui; kebutuhan tiket cocok dengan penanda eskalasi; kasus tanpa aturan masuk jalur manual; tidak ada klaim pemeriksaan jaringan langsung.

### 9.3 Fitur tiket gangguan

**Tujuan:** mengubah hasil diagnosis yang membutuhkan tindak lanjut menjadi pekerjaan yang dapat dipantau.

Data tiket mencakup nomor tiket, nomor pelanggan, kode kerusakan, detail diagnosis, status, dan waktu pembuatan. Dataset menyediakan status `OPEN`, `IN_PROGRESS`, dan `RESOLVED`. Arti operasional yang disarankan: baru diterima, sedang ditangani, dan dinyatakan selesai melalui prosedur layanan. Aturan siapa boleh mengubah status dan apakah transisi mundur diperbolehkan harus disepakati, karena validasi nilai status tidak otomatis menegakkan urutan transisi bisnis.

Backend membuat dan memperbarui tiket; Database Engineer menjaga struktur; Frontend menyediakan interaksi; Analyst menentukan proses; QA memeriksa hasil dan hak akses; UI/UX menyusun detail; Dokumentasi membuat panduan; PM mengatur proses penerimaan; DevOps memastikan layanan tersedia.

**Kriteria penerimaan usulan:** tiket dapat ditelusuri ke informasi diagnosis, perubahan status terlihat pada pembacaan berikutnya, dan kondisi tidak ditemukan dibedakan secara jelas dari status tidak berubah.

### 9.4 Fitur laporan diagnosis dan grafik

**Tujuan:** memperlihatkan hasil sesi yang selesai dan ringkasan data operasional.

Endpoint laporan membaca sesi berstatus `COMPLETED`, termasuk fakta dan hasil diagnosis. Laporan mencakup diagnosis yang mungkin tidak menghasilkan tiket. Karena itu, statistik laporan tidak boleh otomatis disebut statistik tiket.

Analyst menetapkan definisi angka dan periode; Backend menyediakan data; Database Engineer memeriksa sumber dan konsistensi; Frontend mengolah tampilan Chart.js; UI/UX merancang label; QA membandingkan angka dengan dataset kecil yang diketahui; Dokumentasi menjelaskan arti indikator.

**Kriteria penerimaan usulan:** setiap angka memiliki definisi, sumber, dan cakupan yang jelas; kondisi tanpa data tidak menampilkan kesimpulan menyesatkan; jumlah pada grafik dapat dibandingkan dengan data uji.

### 9.5 Fitur pengelolaan basis pengetahuan

**Tujuan:** menyediakan pengelolaan gejala, kerusakan, dan aturan.

Proses kerja yang disarankan adalah mengajukan perubahan, memvalidasi makna bersama pakar, memeriksa dampak terhadap percakapan, menerapkan perubahan pada lingkungan uji, menjalankan regresi, lalu menyetujui penerapan. Ini prosedur tim yang diusulkan, bukan klaim adanya fitur persetujuan otomatis dalam aplikasi.

**Kriteria penerimaan usulan:** aturan hanya merujuk data valid, dampak penghapusan dijelaskan, dan perubahan cabang percakapan diperlakukan sebagai perubahan kode jika diperlukan. Menambah baris gejala tidak otomatis menambah pertanyaan yang dapat dicapai pelanggan.

### 9.6 Fitur autentikasi, pengguna, dan pengaturan

**Tujuan:** menyediakan akses petugas dan pengelolaan konfigurasi aplikasi.

Analyst menyusun kebutuhan izin; Backend menegakkannya pada setiap endpoint; Frontend menyesuaikan menu serta formulir; Database Engineer menyimpan data; QA menguji permintaan langsung dengan sesi/peran berbeda; DevOps mengelola akses dan rahasia; Dokumentasi menjelaskan prosedur.

Keberadaan login dan peran `ADMIN`/`CS` tidak membuktikan seluruh endpoint telah terlindungi. Pemeriksaan endpoint tetap menjadi tugas implementasi dan QA. Pengaturan WhatsApp harus ditampilkan serta dicatat sesuai kebutuhan tanpa menyebarkan token pada panduan atau bukti uji.

## 10. Rincian Rencana Kerja Delapan Minggu

Jadwal berikut adalah usulan pembagian aktivitas. Pekerjaan yang telah tersedia dapat diperlakukan sebagai peninjauan atau penyempurnaan, sehingga tim tidak perlu membangun ulang komponen tanpa alasan.

### Minggu 1 — Analisis dan inventaris

PM menyepakati ruang lingkup dan penanggung jawab. Analyst mengumpulkan kebutuhan dan menelaah aturan bersama pakar. UI/UX menggali masalah pengguna. Backend, Frontend, Database Engineer, dan DevOps menginventaris implementasi serta lingkungan. QA mengidentifikasi risiko. Dokumentasi menyusun kerangka dokumen dan daftar sumber.

**Hasil:** kebutuhan awal, inventaris fitur, daftar pertanyaan untuk pakar, dan risiko. **Syarat lanjut:** semua anggota memahami tujuan serta hal yang belum diputuskan.

### Minggu 2 — Spesifikasi dan rancangan

Analyst merinci skenario dan pengecualian. UI/UX menyerahkan prototipe. Backend dan Frontend menyepakati kontrak. Database Engineer menyusun skema aktual. DevOps menyiapkan rancangan lingkungan. QA membuat kasus uji berdasarkan kebutuhan. PM memeriksa dependensi dan Dokumentasi mencatat keputusan.

**Hasil:** rancangan yang dapat digunakan untuk implementasi. **Syarat lanjut:** aturan, data, respons API, dan alur tampilan tidak saling bertentangan.

### Minggu 3 — Fondasi diagnosis

Backend dan Database Engineer menangani mesin aturan, sesi, serta data awal. Frontend mengerjakan simulator dengan rancangan UI/UX. DevOps memastikan lingkungan berfungsi. QA memeriksa jalur dasar sejak dini. Analyst menjawab masalah domain; PM menyelesaikan hambatan; Dokumentasi memperbarui panduan setup.

**Hasil:** diagnosis dasar dapat dicoba melalui simulator pada lingkungan uji.

### Minggu 4 — Tiket dan dashboard

Backend menyediakan layanan tiket/laporan. Frontend menghubungkan daftar, detail, status, dan grafik. Database Engineer memeriksa konsistensi data. QA membandingkan hasil diagnosis dengan tiket/laporan. UI/UX meninjau keterbacaan dan Analyst menyelaraskan proses CS.

**Hasil:** alur dari diagnosis sampai pemantauan dapat didemonstrasikan.

### Minggu 5 — Administrasi dan integrasi

Tim menyelesaikan pengelolaan pengetahuan, pengguna, pengaturan, serta webhook. DevOps dan Backend memeriksa konektivitas WhatsApp. QA membedakan pengujian simulator dari integrasi sesungguhnya. Dokumentasi mencatat konfigurasi dengan penanda rahasia yang aman.

**Hasil:** integrasi utama dapat dicoba; kegagalan yang diketahui memiliki pemilik tindak lanjut.

### Minggu 6 — Penyatuan dan evaluasi

QA menjalankan skenario menyeluruh. Analyst dan pakar memvalidasi hasil. Pengembang memperbaiki masalah. UI/UX mengamati penggunaan dashboard dan chat. Database Engineer dan DevOps mencoba backup/pemulihan. PM mengendalikan perubahan agar fokus rilis jelas.

**Hasil:** daftar temuan, revisi, dan bukti perbaikan.

### Minggu 7 — Regresi dan penerimaan pengguna

QA menguji ulang dan menjalankan regresi area terdampak. Pengguna mencoba tugas operasional. Analyst memeriksa kriteria penerimaan. Dokumentasi menguji panduan bersama pengguna baru. DevOps meninjau kesiapan lingkungan target. PM menyusun keputusan atas masalah terbuka.

**Hasil:** ringkasan hasil pengujian, penerimaan atau tindakan perbaikan, dan daftar risiko tersisa.

### Minggu 8 — Serah terima

Tim mendemonstrasikan versi yang diserahkan, menyediakan panduan, menyerahkan akses melalui saluran yang tepat, dan menetapkan penanggung jawab pemeliharaan. QA menyimpan bukti versi yang diuji. PM mencatat penerimaan dan pekerjaan lanjutan.

**Hasil:** paket serah terima yang menjelaskan versi, lingkungan, fitur, batasan, hasil uji, dan dukungan setelah penyerahan.

## 11. Skenario Uji Konkret Berdasarkan Dataset Awal

Urutan masukan berikut dimulai dari sesi baru dengan `START`. Angka setelahnya adalah jawaban berturut-turut. Hasil di bawah merupakan ekspektasi berdasarkan kode/dataset yang dibaca, bukan catatan pengujian yang telah dijalankan atau bukti akurasi diagnosis lapangan.

1. **R01 — LOS merah:** `START → 1 → 1`; fakta G01 dan G03; hasil K01; penanda teknisi aktif dan tiket diharapkan dibuat.
2. **R02 — PON mati:** `START → 1 → 2 → 1`; fakta G01 dan G04; hasil K02; tiket diharapkan dibuat.
3. **R03 — Power mati:** `START → 1 → 2 → 2 → 1`; fakta G01 dan G05; hasil K03; tidak membuat tiket berdasarkan penanda dataset.
4. **R04 — Lambat satu perangkat:** `START → 2 → 1`; fakta G02 dan G07; hasil K04; tidak membuat tiket berdasarkan penanda dataset.
5. **R05 — Lambat semua perangkat:** `START → 2 → 2`; fakta G02 dan G06; hasil K05; tidak membuat tiket berdasarkan penanda dataset.
6. **R06 — WLAN mati:** `START → 1 → 2 → 2 → 2 → 1`; fakta G01 dan G08; hasil K06; tidak membuat tiket berdasarkan penanda dataset.
7. **R07 — Situs tertentu:** `START → 2 → 3`; fakta G02 dan G09; hasil K07; tidak membuat tiket berdasarkan penanda dataset.
8. **R08 — Cabang tagihan:** `START → 1 → 2 → 2 → 2 → 2`; fakta G01 dan G10; hasil K08; tiket diharapkan dibuat. Validitas asumsi tagihan pada cabang ini harus diperiksa pakar.

### 11.1 Pengujian pengecualian

- Kirim teks yang tidak termasuk pilihan pada setiap langkah, lalu periksa apakah fakta tetap benar dan pengguna mendapat arahan yang sesuai.
- Jalankan reset di tengah diagnosis, lalu pastikan alur kembali ke awal sesuai perilaku yang disepakati.
- Jalankan dua identitas pengguna dengan jawaban berbeda untuk memeriksa isolasi sesi.
- Pada lingkungan uji terisolasi, siapkan kondisi tanpa aturan cocok untuk memeriksa fallback; jangan menghapus aturan pada lingkungan operasional hanya untuk pengujian.
- Periksa perubahan status tiket ke nilai sah, nilai tidak sah, tiket yang tidak ada, serta nilai status yang sama.
- Uji daftar dan grafik dengan data kosong, satu entri, serta beberapa kategori yang jumlahnya diketahui.
- Periksa akses langsung endpoint dengan pengguna belum login dan peran berbeda.
- Catat kegagalan WhatsApp secara terpisah: pesan belum sampai webhook, proses aplikasi gagal, atau balasan gagal dikirim.

### 11.2 Format catatan kasus uji

Setiap kasus memiliki ID, kebutuhan terkait, versi aplikasi, lingkungan, prasyarat, data, langkah, hasil yang diharapkan, hasil aktual, bukti, status lulus/gagal, dan ID bug bila ada. Bukti dapat berupa tangkapan layar atau respons yang telah disamarkan. QA tidak menandai lulus hanya karena tidak muncul pesan kesalahan; data dan hasil bisnis juga diperiksa.

## 12. Catatan Implementasi yang Memerlukan Tinjauan Tim

### 12.1 Kategori fallback menggunakan K06

Dataset memberi K06 arti “Fitur WiFi Modem Non-Aktif” dengan penanda teknisi tidak aktif. Namun jalur tanpa aturan cocok pada mesin menggunakan kode K06 untuk hasil “Masalah Konfigurasi / Unresolved” dan membuat tiket. Endpoint tiket membaca nama kerusakan melalui relasi ke tabel kerusakan, sedangkan laporan membaca hasil yang disimpan pada sesi. Perbedaan ini berpotensi membuat nama atau makna hasil berbeda antara tiket dan laporan.

**Tindak lanjut yang disarankan:** Analyst dan pakar menentukan kategori fallback yang benar; Database Engineer menyiapkan data atau migrasi; Backend menyelaraskan hasil; Frontend menampilkan makna konsisten; QA menguji kedua sumber. Dokumen ini mencatat temuan, tidak mengubah kode atau data.

### 12.2 Cabang tagihan dan penanda teknisi

G10 ditambahkan pada cabang tertentu tanpa verifikasi langsung ke sistem pembayaran. Selain itu, dataset K08 menggunakan penanda teknisi aktif meskipun tindak lanjut yang disebut berkaitan dengan pembayaran/CS billing. Tim perlu menyepakati apakah penanda sebenarnya berarti teknisi lapangan atau eskalasi layanan secara umum.

### 12.3 Data pertanyaan dan percakapan

Adanya kolom pertanyaan pada gejala tidak berarti semua teks dan langkah percakapan dibangun dinamis dari tabel. Saat ini cabang utama terlihat pada kode mesin. Analyst, Backend, dan Database Engineer harus menyelaraskan perubahan data dengan cabang yang dapat dicapai pengguna.

### 12.4 Keamanan dan kesiapan penerapan

Login serta pembagian peran tersedia sebagai bagian aplikasi, tetapi kelengkapan perlindungan setiap endpoint harus ditinjau dan diuji tersendiri. Dokumen ini bukan hasil audit keamanan atau sertifikasi kesiapan produksi. Pengujian fungsional, otorisasi, konfigurasi, dan pemulihan tetap menjadi pekerjaan sebelum penerapan sesuai lingkungan target.

## 13. Mekanisme Kolaborasi dan Serah Terima

### 13.1 Dari kebutuhan menuju implementasi

Analyst menyerahkan skenario kepada UI/UX, Backend, Database Engineer, dan QA. Setiap pihak meninjau kebutuhan dari sudutnya: apakah interaksi jelas, data cukup, kontrak dapat dibangun, dan hasil bisa diuji. PM mencatat keputusan ketika ada perbedaan, sedangkan Dokumentasi memperbarui spesifikasi.

### 13.2 Dari implementasi menuju pengujian

Pengembang menyerahkan perubahan beserta lokasi komponen, cara mencoba, konfigurasi yang diperlukan, dan batasan. QA mencatat versi serta menjalankan kasus terkait. Jika gagal, bug memuat langkah reproduksi dan hasil aktual. Setelah diperbaiki, QA menjalankan retest serta regresi yang relevan.

### 13.3 Dari pengujian menuju operasional

DevOps menerima versi dan prasyarat; Database Engineer menyerahkan perubahan skema dan prosedur pemulihan; Dokumentasi menyerahkan panduan; QA menyerahkan hasil dan risiko. PM mengoordinasikan penerimaan pengguna. Masalah yang belum selesai diberi pemilik dan keputusan penanganan, bukan disembunyikan dalam catatan informal.

### 13.4 Contoh perubahan: menambahkan jenis gangguan baru

1. Analyst dan pakar mendefinisikan gejala, hasil, pertanyaan, serta solusi.
2. PM menilai prioritas dan dampak jadwal bersama tim.
3. UI/UX menyusun pertanyaan dan pilihan yang mudah dipahami.
4. Database Engineer menyiapkan perubahan data dan relasi.
5. Backend menambah cabang dan aturan pencocokan yang diperlukan.
6. Frontend memperbarui pilihan atau tampilan jika terdampak.
7. QA membuat kasus baru dan menjalankan regresi cabang lama.
8. Dokumentasi memperbarui panduan dan riwayat perubahan.
9. DevOps menerapkan perubahan dengan prosedur rilis dan pemulihan yang disepakati.

Contoh ini menunjukkan bahwa satu fitur melibatkan beberapa peran dengan hasil kerja berbeda; kepemilikan fitur tidak berarti seluruh pekerjaan ditangani oleh satu orang.

## 14. Paket Akhir yang Diserahkan Tim

- **Dokumen kebutuhan:** tujuan, ruang lingkup, aktor, skenario, pengecualian, dan kriteria penerimaan.
- **Dokumen pengetahuan:** gejala, aturan, kerusakan, solusi, sumber pakar, serta catatan validasi.
- **Desain:** alur, prototipe, komponen, naskah percakapan, dan kondisi tampilan.
- **Aplikasi:** kode, konfigurasi contoh tanpa rahasia, catatan dependensi, serta penjelasan versi.
- **Database:** skema aktual, dataset, kamus data, perubahan skema, dan prosedur pemulihan.
- **Pengujian:** kasus uji, hasil aktual, bukti, bug, hasil retest, dan risiko tersisa.
- **Operasional:** setup, mulai/berhenti, webhook, pemantauan, backup, restore, dan rollback.
- **Panduan pengguna:** instruksi pelanggan, CS, admin, serta langkah penanganan masalah umum.
- **Serah terima:** versi dan lingkungan yang diserahkan, keputusan penerimaan, penanggung jawab, serta daftar pekerjaan lanjutan.

## 15. Acuan Dokumen dan Cara Membacanya

Penjelasan implementasi mengacu pada README, struktur folder, dataset SQL, mesin `src/includes/ForwardChaining.php`, endpoint API, serta JavaScript simulator dan dashboard yang tersedia saat dokumen disusun. Pembacaan kode tidak menggantikan pengujian aplikasi yang sedang berjalan.

Bagian fitur tersedia menjelaskan komponen yang ditemukan. Bagian timeline, kriteria penerimaan, paket kerja, serta checklist adalah panduan pelaksanaan yang diusulkan. Semua checklist dibiarkan belum dicentang agar tidak menyatakan pekerjaan telah selesai tanpa bukti. Nama anggota dapat ditambahkan pada setiap peran ketika pembagian orang sudah ditetapkan.
