<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersiapanAcara extends Model
{
    protected $table = 'persiapan_acara';

    // PJ categories definition
    public const PJ_CATEGORIES = [
        'pj_acara' => ['label' => 'PJ Acara', 'icon' => '🎪', 'color' => 'blue'],
        'pj_konsumsi' => ['label' => 'PJ Konsumsi', 'icon' => '🍽️', 'color' => 'amber'],
        'pj_perlengkapan' => ['label' => 'PJ Perlengkapan', 'icon' => '🔧', 'color' => 'gray'],
        'pj_pembuat_materi' => ['label' => 'PJ Pembuat Materi', 'icon' => '📚', 'color' => 'indigo'],
        'pj_solat_malam' => ['label' => 'PJ Solat Malam', 'icon' => '🌙', 'color' => 'purple'],
        'pj_kebersihan' => ['label' => 'PJ Kebersihan', 'icon' => '🧹', 'color' => 'green'],
        'pj_olahraga' => ['label' => 'PJ Olahraga', 'icon' => '⚽', 'color' => 'red'],
    ];

    protected $fillable = [
        'judul_acara',
        'nomor_ke',
        'deskripsi_acara',
        'waktu_acara',
        'waktu_selesai',
        'tempat',
        'peserta',
        'pakaian',
        'materi_pemateri',
        'perlengkapan',
        'catatan_tambahan',
        'rundown',
        'pj_acara_id',
        'tim_dokumentasi',
        'panitia',
        'created_by',
    ];

    protected $casts = [
        'waktu_acara' => 'datetime',
        'materi_pemateri' => 'array',
        'perlengkapan' => 'array',
        'catatan_tambahan' => 'array',
        'rundown' => 'array',
        'tim_dokumentasi' => 'array',
        'panitia' => 'array',
    ];

    public function pjAcara(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pj_acara_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTimDokumentasiUsers()
    {
        if (empty($this->tim_dokumentasi)) {
            return collect();
        }
        return User::whereIn('id', $this->tim_dokumentasi)->get();
    }

    /**
     * Get users for a specific PJ category from the panitia JSON.
     */
    public function getPanitiaUsers(string $category)
    {
        $panitia = $this->panitia ?? [];
        $ids = $panitia[$category] ?? [];
        if (empty($ids)) {
            return collect();
        }
        return User::whereIn('id', $ids)->get();
    }

    /**
     * Get all non-empty PJ categories with their users.
     */
    public function getAllPanitia(): array
    {
        $result = [];
        foreach (self::PJ_CATEGORIES as $key => $meta) {
            $users = $this->getPanitiaUsers($key);
            if ($users->isNotEmpty()) {
                $result[$key] = [
                    'label' => $meta['label'],
                    'icon' => $meta['icon'],
                    'color' => $meta['color'],
                    'users' => $users,
                ];
            }
        }
        return $result;
    }

    /**
     * Generate formatted invitation text for WhatsApp sharing.
     */
    public function generateWhatsAppText(): string
    {
        $nomorLabel = $this->nomor_ke ? "  Ke {$this->nomor_ke}" : '';
        $text = "📩 *UNDANGAN {$this->judul_acara}*{$nomorLabel}\n";

        if ($this->peserta) {
            $text .= "Kepada Yth:\n";
            $text .= "{$this->peserta}\n";
            $text .= "📍 di tempat\n";
        }

        $text .= "السلام عليكم ورحمة الله وبركاته\n";

        if ($this->deskripsi_acara) {
            $text .= "{$this->deskripsi_acara}\n";
        }

        $text .= "\nyang insyaAllah akan dilaksanakan:\n\n";

        if ($this->waktu_acara) {
            $text .= "📅 Hari/Tanggal: " . $this->waktu_acara->isoFormat('dddd, D MMMM YYYY') . "\n";
            $waktuStr = $this->waktu_acara->format('H.i');
            if ($this->waktu_selesai) {
                $waktuStr .= " – {$this->waktu_selesai}";
            }
            $text .= "🕰 Waktu: {$waktuStr} WIB\n";
        }

        if ($this->tempat) {
            $text .= "🕌 Tempat: {$this->tempat}\n";
        }

        if ($this->peserta) {
            $text .= "👥 Peserta: {$this->peserta}\n";
        }

        if ($this->pakaian) {
            $text .= "👕 Pakaian: {$this->pakaian}\n";
        }

        // Perlengkapan
        if (!empty($this->perlengkapan)) {
            $text .= "\n🎒 Perlengkapan:\n";
            foreach ($this->perlengkapan as $i => $item) {
                $text .= ($i + 1) . ". {$item}\n";
            }
        }

        // Catatan Tambahan
        if (!empty($this->catatan_tambahan)) {
            $text .= "\n📝 Ket. tambahan :\n";
            foreach ($this->catatan_tambahan as $i => $item) {
                $text .= ($i + 1) . ". {$item}\n";
            }
        }

        // Rundown
        if (!empty($this->rundown)) {
            $text .= "\nRUNDOWN KEGIATAN :\n";
            foreach ($this->rundown as $item) {
                $text .= "🔹 " . ($item['waktu'] ?? '') . "\n";
                $text .= ($item['kegiatan'] ?? '') . "\n";
                if (!empty($item['detail'])) {
                    foreach (explode("\n", $item['detail']) as $line) {
                        if (trim($line)) {
                            $text .= "- {$line}\n";
                        }
                    }
                }
                $text .= "\n";
            }
        }

        // Panitia / PJ
        $allPanitia = $this->getAllPanitia();
        if (!empty($allPanitia)) {
            $text .= "\n📌 *SUSUNAN PANITIA:*\n";
            foreach ($allPanitia as $key => $data) {
                $names = $data['users']->pluck('username')->implode(', ');
                $text .= "{$data['icon']} *{$data['label']}:* {$names}\n";
            }
        }

        // Tim Dokumentasi
        $timUsers = $this->getTimDokumentasiUsers();
        if ($timUsers->isNotEmpty()) {
            $text .= "📸 *Tim Dokumentasi:* " . $timUsers->pluck('username')->implode(', ') . "\n";
        }

        $text .= "\n🙏 Kami mohon dukungan dari orang tua agar generus dapat hadir tepat waktu dan mengikuti kegiatan dengan penuh semangat.\n";
        $text .= "\nDemikian undangan ini kami sampaikan, atas perhatian kami ucapkan syukuri\n";
        $text .= "\nالحمد لله جزا كم الله خيرا , والسلام عليكم ورحمة الله و بركا ته\n";

        return $text;
    }
}
