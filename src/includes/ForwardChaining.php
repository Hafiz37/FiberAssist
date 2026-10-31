<?php
/**
 * Core Forward Chaining Inference Engine
 * Sistem Pakar Diagnosa Gangguan Internet (ONU ZTE F609)
 */

class ForwardChainingEngine
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Ambil atau buat sesi baru untuk nomor WA tertentu
     */
    public function getOrCreateSession($no_wa)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM sesi_diagnosa WHERE no_wa = ? AND status = 'ACTIVE' ORDER BY updated_at DESC LIMIT 1");
        $stmt->execute([$no_wa]);
        $session = $stmt->fetch();

        if (!$session) {
            $stmtInsert = $this->pdo->prepare("INSERT INTO sesi_diagnosa (no_wa, step, fakta_terkumpul, status) VALUES (?, 1, ?, 'ACTIVE')");
            $stmtInsert->execute([$no_wa, json_encode([])]);
            $id = $this->pdo->lastInsertId();

            return [
                'id_sesi' => $id,
                'no_wa' => $no_wa,
                'step' => 1,
                'fakta_terkumpul' => [],
                'status' => 'ACTIVE'
            ];
        }

        $session['fakta_terkumpul'] = json_decode($session['fakta_terkumpul'], true) ?? [];
        return $session;
    }

    /**
     * Reset sesi pengguna menjadi COMPLETED dan buat sesi baru
     */
    public function resetSession($no_wa)
    {
        $stmt = $this->pdo->prepare("UPDATE sesi_diagnosa SET status = 'COMPLETED' WHERE no_wa = ? AND status = 'ACTIVE'");
        $stmt->execute([$no_wa]);

        return $this->getOrCreateSession($no_wa);
    }

    /**
     * Simpan pembaruan state sesi (step & fakta_terkumpul)
     */
    private function updateSession($id_sesi, $step, array $facts, $status = 'ACTIVE', $hasil_diagnosa = null)
    {
        if ($hasil_diagnosa !== null) {
            $stmt = $this->pdo->prepare("UPDATE sesi_diagnosa SET step = ?, fakta_terkumpul = ?, status = ?, hasil_diagnosa = ? WHERE id_sesi = ?");
            $stmt->execute([$step, json_encode($facts), $status, json_encode($hasil_diagnosa), $id_sesi]);
        } else {
            $stmt = $this->pdo->prepare("UPDATE sesi_diagnosa SET step = ?, fakta_terkumpul = ?, status = ? WHERE id_sesi = ?");
            $stmt->execute([$step, json_encode($facts), $status, $id_sesi]);
        }
    }

    /**
     * Evaluasi Aturan Forward Chaining berdasarkan fakta yang terkumpul
     */
    private function matchRules(array $facts)
    {
        if (count($facts) < 2) {
            return null;
        }

        // Ambil semua aturan dari database
        $stmt = $this->pdo->prepare("
            SELECT a.kode_rule, a.id_gejala_1, a.id_gejala_2, k.kode_kerusakan, k.nama_kerusakan, k.solusi, k.butuh_teknisi
            FROM aturan a
            JOIN kerusakan k ON a.id_kerusakan = k.kode_kerusakan
        ");
        $stmt->execute();
        $rules = $stmt->fetchAll();

        foreach ($rules as $rule) {
            if (in_array($rule['id_gejala_1'], $facts) && in_array($rule['id_gejala_2'], $facts)) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * Buat tiket gangguan resmi jika butuh teknisi / eskalasi
     */
    private function createTicket($no_wa, $kode_kerusakan, $detail_diagnosa)
    {
        $no_tiket = 'TKT-' . date('Ymd') . '-' . rand(1000, 9999);
        $stmt = $this->pdo->prepare("INSERT INTO tiket_gangguan (no_tiket, no_wa, kode_kerusakan, detail_diagnosa, status_tiket) VALUES (?, ?, ?, ?, 'OPEN')");
        $stmt->execute([$no_tiket, $no_wa, $kode_kerusakan, $detail_diagnosa]);

        return $no_tiket;
    }

    /**
     * Engine Pemroses Obrolan Utama (State Machine)
     */
    public function processInput($no_wa, $rawInput)
    {
        $input = trim($rawInput);
        $inputUpper = strtoupper($input);

        // Opsi reset/restart percakapan
        if (in_array($inputUpper, ['RESET', 'START', 'BATAL', 'MENU', 'HALO', 'HI', 'HELP', '0'])) {
            $session = $this->resetSession($no_wa);
            return [
                'status' => 'QUESTION',
                'step' => 1,
                'message' => "Halo! Selamat datang di Layanan Diagnosa Mandiri Internet ISP. 🤖\n\nSilakan pilih keluhan utama yang Anda alami:\n1. Internet Mati Total\n2. Internet Lambat / Buffering\n\n_Balas dengan angka 1 atau 2._"
            ];
        }

        $session = $this->getOrCreateSession($no_wa);
        $step = (int)$session['step'];
        $facts = $session['fakta_terkumpul'];

        // STEP 1: Pilihan Keluhan Utama (G01 atau G02)
        if ($step === 1) {
            if ($input === '1') {
                $facts = ['G01'];
                $this->updateSession($session['id_sesi'], 2, $facts);
                return [
                    'status' => 'QUESTION',
                    'step' => 2,
                    'message' => "Baik, kendala *Internet Mati Total*.\n\nSilakan periksa indikator modem ZTE F609 Anda:\nApakah lampu indikator *LOS* berkedip warna MERAH?\n\n1. Ya (Lampu LOS Berkedip Merah)\n2. Tidak (Lampu LOS Off/Normal)\n\n_Balas dengan angka 1 atau 2._"
                ];
            } elseif ($input === '2') {
                $facts = ['G02'];
                $this->updateSession($session['id_sesi'], 2, $facts);
                return [
                    'status' => 'QUESTION',
                    'step' => 2,
                    'message' => "Baik, kendala *Internet Lambat / Buffering*.\n\nApakah kendala ini terjadi pada:\n1. Hanya 1 Perangkat saja (HP/Laptop)\n2. Semua Perangkat (HP, Laptop, Smart TV)\n3. Terjadi saat buka website tertentu saja (DNS Error)\n\n_Balas dengan angka 1, 2, atau 3._"
                ];
            } else {
                return [
                    'status' => 'QUESTION',
                    'step' => 1,
                    'message' => "⚠️ Pilihan tidak valid. Silakan balas dengan angka:\n1. Internet Mati Total\n2. Internet Lambat / Buffering"
                ];
            }
        }

        // STEP 2 - INTERNET MATI TOTAL (G01)
        if ($step === 2 && in_array('G01', $facts)) {
            if ($input === '1') {
                // Jawaban: Ya (LOS Merah -> G03)
                $facts[] = 'G03';
                $ruleMatch = $this->matchRules($facts);
                return $this->finalizeConclusion($session['id_sesi'], $no_wa, $facts, $ruleMatch);
            } elseif ($input === '2') {
                // Jawaban: Tidak -> Lanjut Tanya Lampu PON (G04)
                $this->updateSession($session['id_sesi'], 3, $facts);
                return [
                    'status' => 'QUESTION',
                    'step' => 3,
                    'message' => "Baik. Apakah lampu indikator *PON* pada modem dalam kondisi MATI (Off)?\n\n1. Ya (Lampu PON Mati)\n2. Tidak (Lampu PON Menyala/Normal)\n\n_Balas dengan angka 1 atau 2._"
                ];
            } else {
                return [
                    'status' => 'QUESTION',
                    'step' => 2,
                    'message' => "⚠️ Silakan balas 1 (Ya) atau 2 (Tidak)."
                ];
            }
        }

        // STEP 3 - MATI TOTAL -> PON OFF (G04)
        if ($step === 3 && in_array('G01', $facts)) {
            if ($input === '1') {
                // Jawaban: Ya (PON Off -> G04)
                $facts[] = 'G04';
                $ruleMatch = $this->matchRules($facts);
                return $this->finalizeConclusion($session['id_sesi'], $no_wa, $facts, $ruleMatch);
            } elseif ($input === '2') {
                // Jawaban: Tidak -> Lanjut Tanya Lampu Power (G05)
                $this->updateSession($session['id_sesi'], 4, $facts);
                return [
                    'status' => 'QUESTION',
                    'step' => 4,
                    'message' => "Baik. Apakah lampu indikator *Power* pada modem MATI TOTAL (Off)?\n\n1. Ya (Lampu Power Mati Total)\n2. Tidak (Lampu Power Menyala)\n\n_Balas dengan angka 1 atau 2._"
                ];
            }
        }

        // STEP 4 - MATI TOTAL -> POWER OFF (G05)
        if ($step === 4 && in_array('G01', $facts)) {
            if ($input === '1') {
                // Jawaban: Ya (Power Off -> G05)
                $facts[] = 'G05';
                $ruleMatch = $this->matchRules($facts);
                return $this->finalizeConclusion($session['id_sesi'], $no_wa, $facts, $ruleMatch);
            } elseif ($input === '2') {
                // Jawaban: Tidak -> Lanjut Tanya Lampu WLAN (G08)
                $this->updateSession($session['id_sesi'], 5, $facts);
                return [
                    'status' => 'QUESTION',
                    'step' => 5,
                    'message' => "Baik. Apakah lampu indikator *WLAN / WiFi* pada modem MATI (Off)?\n\n1. Ya (Lampu WLAN Mati)\n2. Tidak (Lampu WLAN Menyala)\n\n_Balas dengan angka 1 atau 2._"
                ];
            }
        }

        // STEP 5 - MATI TOTAL -> WLAN OFF (G08) ATAU TAGIHAN (G10)
        if ($step === 5 && in_array('G01', $facts)) {
            if ($input === '1') {
                // Jawaban: Ya (WLAN Off -> G08)
                $facts[] = 'G08';
                $ruleMatch = $this->matchRules($facts);
                return $this->finalizeConclusion($session['id_sesi'], $no_wa, $facts, $ruleMatch);
            } elseif ($input === '2') {
                // Jawaban: Tidak WLAN Off -> Cek Tagihan / Isolir (G10)
                $facts[] = 'G10';
                $ruleMatch = $this->matchRules($facts);
                return $this->finalizeConclusion($session['id_sesi'], $no_wa, $facts, $ruleMatch);
            }
        }

        // STEP 2 - INTERNET LAMBAT (G02)
        if ($step === 2 && in_array('G02', $facts)) {
            if ($input === '1') {
                // 1 Perangkat saja -> G07 (Rule R04)
                $facts[] = 'G07';
            } elseif ($input === '2') {
                // Semua Perangkat -> G06 (Rule R05)
                $facts[] = 'G06';
            } elseif ($input === '3') {
                // Web Tertentu / DNS Error -> G09 (Rule R07)
                $facts[] = 'G09';
            } else {
                return [
                    'status' => 'QUESTION',
                    'step' => 2,
                    'message' => "⚠️ Pilihan tidak valid. Silakan balas dengan angka 1, 2, atau 3."
                ];
            }

            $ruleMatch = $this->matchRules($facts);
            return $this->finalizeConclusion($session['id_sesi'], $no_wa, $facts, $ruleMatch);
        }

        // Default fallback jika step tidak dikenali
        return [
            'status' => 'QUESTION',
            'step' => 1,
            'message' => "Ketik *START* atau *MENU* untuk memulai kembali diagnosa mandiri."
        ];
    }

    /**
     * Finalisasi hasil evaluasi dan penyusunan pesan kesimpulan / tiket
     */
    private function finalizeConclusion($id_sesi, $no_wa, array $facts, $ruleMatch)
    {
        if (!$ruleMatch) {
            $no_tiket = $this->createTicket($no_wa, 'K06', 'Diagnosa tidak teridentifikasi otomatis dari fakta: ' . implode(', ', $facts));
            
            $hasilDiagnosa = [
                'kode_rule' => null,
                'kode_kerusakan' => 'K06',
                'nama_kerusakan' => 'Masalah Konfigurasi / Unresolved',
                'solusi' => 'Hubungi Customer Service untuk pengecekan manual.',
                'butuh_teknisi' => 1,
                'no_tiket' => $no_tiket
            ];
            
            $this->updateSession($id_sesi, 99, $facts, 'COMPLETED', $hasilDiagnosa);
            
            return [
                'status' => 'CONCLUSION',
                'kode_kerusakan' => 'K06',
                'nama_kerusakan' => 'Masalah Konfigurasi / Unresolved',
                'solusi' => 'Hubungi Customer Service untuk pengecekan manual.',
                'butuh_teknisi' => 1,
                'no_tiket' => $no_tiket,
                'message' => "🔍 *HASIL DIAGNOSA SEMENTARA*\n\nKendala belum dapat teridentifikasi secara otomatis dari indikator yang Anda berikan.\n\n📞 Obrolan Anda dialihkan ke Customer Service untuk penanganan lebih lanjut."
            ];
        }

        $needTech = (int)$ruleMatch['butuh_teknisi'];
        $no_tiket = null;
        $detail = "Gejala Terdeteksi: " . implode(' + ', $facts) . " | Rule: " . $ruleMatch['kode_rule'];

        if ($needTech === 1) {
            $no_tiket = $this->createTicket($no_wa, $ruleMatch['kode_kerusakan'], $detail);
        }

        $hasilDiagnosa = [
            'kode_rule' => $ruleMatch['kode_rule'],
            'kode_kerusakan' => $ruleMatch['kode_kerusakan'],
            'nama_kerusakan' => $ruleMatch['nama_kerusakan'],
            'solusi' => $ruleMatch['solusi'],
            'butuh_teknisi' => $needTech,
            'no_tiket' => $no_tiket
        ];

        $this->updateSession($id_sesi, 99, $facts, 'COMPLETED', $hasilDiagnosa);

        $msg = "📋 *HASIL DIAGNOSA SISTEM PAKAR*\n\n";
        $msg .= "• *Kode Aturan:* " . $ruleMatch['kode_rule'] . "\n";
        $msg .= "• *Diagnosa:* " . $ruleMatch['nama_kerusakan'] . "\n\n";
        $msg .= "💡 *Rekomendasi / Solusi:* \n" . $ruleMatch['solusi'] . "\n\n";

        if ($no_tiket) {
            $msg .= "🎫 *TIKET GANGGUAN DIKIRIMKAN KE TEKNISI*\n";
            $msg .= "• *No. Tiket:* #" . $no_tiket . "\n";
            $msg .= "• *Status:* TIKET DIPROSES TEKNISI LAPANGAN\n\n";
            $msg .= "Tim teknisi kami telah menerima hasil diagnosa ini dan akan segera memverifikasi jaringan menuju rumah Anda.";
        } else {
            $msg .= "✅ *PENANGANAN MANDIRI*\nAnda dapat mengikuti petunjuk solusi di atas tanpa perlu menunggu kedatangan teknisi.";
        }

        return [
            'status' => 'CONCLUSION',
            'kode_rule' => $ruleMatch['kode_rule'],
            'kode_kerusakan' => $ruleMatch['kode_kerusakan'],
            'nama_kerusakan' => $ruleMatch['nama_kerusakan'],
            'solusi' => $ruleMatch['solusi'],
            'butuh_teknisi' => $needTech,
            'no_tiket' => $no_tiket,
            'facts' => $facts,
            'message' => $msg
        ];
    }
}
