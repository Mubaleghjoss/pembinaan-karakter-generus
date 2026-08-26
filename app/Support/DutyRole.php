<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Peran tugas (duty role) untuk akun operasional.
 *
 * Berbeda dari peran akun (admin / pkg_manager / teacher) yang menentukan
 * HAK AKSES, peran tugas hanya menjelaskan TUGAS yang dipegang seseorang dan
 * ditampilkan sebagai badge. Satu akun boleh memegang beberapa peran tugas.
 *
 * Daftar bawaan selalu ada; admin dapat menambah peran baru yang disimpan di
 * tabel settings (grup permissions) sehingga tidak perlu deploy ulang.
 */
class DutyRole
{
    public const STORE_KEY = 'duty_roles_catalog';
    public const STORE_GROUP = 'permissions';

    /**
     * Peran tugas bawaan aplikasi.
     *
     * @return array<string, array{label: string, tone: string, description: string}>
     */
    public static function builtin(): array
    {
        return [
            'pengisi_presensi' => [
                'label' => 'Pengisi Presensi',
                'tone' => 'sky',
                'description' => 'Ditunjuk membantu mengisi presensi manual generus.',
            ],
            'verifikator_tugas' => [
                'label' => 'Verifikator Tugas PKG',
                'tone' => 'emerald',
                'description' => 'Memeriksa dan memverifikasi Tugas PKG generus.',
            ],
            'verifikator_quran' => [
                'label' => "Verifikator Bacaan Qur'an",
                'tone' => 'teal',
                'description' => "Memeriksa setoran bacaan Al-Qur'an generus.",
            ],
            'koordinator_kelompok' => [
                'label' => 'Koordinator Kelompok',
                'tone' => 'amber',
                'description' => 'Mengoordinasi kegiatan PKG di kelompoknya.',
            ],
            'penanggung_jawab_acara' => [
                'label' => 'PJ Acara',
                'tone' => 'violet',
                'description' => 'Penanggung jawab pelaksanaan acara/kegiatan PKG.',
            ],
            'operator_data' => [
                'label' => 'Operator Data',
                'tone' => 'slate',
                'description' => 'Mengelola data generus, materi, dan laporan.',
            ],
        ];
    }

    /**
     * Peran tugas tambahan buatan admin.
     */
    public static function custom(): array
    {
        $raw = Setting::get(self::STORE_KEY, null, self::STORE_GROUP);
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Seluruh peran tugas (bawaan + tambahan). Tambahan dengan slug sama
     * menimpa label/warna bawaan.
     */
    public static function all(): array
    {
        return Cache::remember('duty_roles:all', 300, function () {
            $merged = self::builtin();

            foreach (self::custom() as $slug => $data) {
                $merged[$slug] = [
                    'label' => $data['label'] ?? $slug,
                    'tone' => self::normalizeTone($data['tone'] ?? 'slate'),
                    'description' => $data['description'] ?? '',
                ];
            }

            return $merged;
        });
    }

    public static function labels(): array
    {
        return array_map(fn (array $role) => $role['label'], self::all());
    }

    public static function find(?string $slug): ?array
    {
        if (! $slug) {
            return null;
        }

        return self::all()[$slug] ?? null;
    }

    /**
     * Buang slug yang tidak dikenal supaya data tetap bersih.
     */
    public static function sanitize(array $slugs): array
    {
        $known = array_keys(self::all());

        return array_values(array_intersect($known, array_values(array_unique($slugs))));
    }

    /**
     * Tambah / ubah peran tugas buatan admin. Mengembalikan slug tersimpan.
     */
    public static function save(?string $slug, string $label, string $tone = 'slate', string $description = ''): string
    {
        $custom = self::custom();
        $label = trim($label);

        if (! $slug) {
            $slug = self::uniqueSlug($label, $custom);
        }

        $custom[$slug] = [
            'label' => $label !== '' ? $label : $slug,
            'tone' => self::normalizeTone($tone),
            'description' => trim($description),
        ];

        self::persist($custom);

        return $slug;
    }

    /**
     * Hapus peran tugas tambahan. Peran bawaan tidak bisa dihapus.
     */
    public static function delete(string $slug): bool
    {
        $custom = self::custom();

        if (! array_key_exists($slug, $custom)) {
            return false;
        }

        unset($custom[$slug]);
        self::persist($custom);

        return true;
    }

    /**
     * Kelas Tailwind untuk badge, dipetakan dari nama warna agar aman
     * terhadap purge CSS (kelas ditulis utuh di sini).
     */
    public static function badgeClasses(string $tone): string
    {
        return match (self::normalizeTone($tone)) {
            'sky' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/50 dark:text-sky-200',
            'emerald' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200',
            'teal' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/50 dark:text-teal-200',
            'amber' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-200',
            'violet' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/50 dark:text-violet-200',
            'rose' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-200',
            'indigo' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-200',
            default => 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
        };
    }

    public static function availableTones(): array
    {
        return ['sky', 'emerald', 'teal', 'amber', 'violet', 'rose', 'indigo', 'slate'];
    }

    protected static function normalizeTone(string $tone): string
    {
        return in_array($tone, self::availableTones(), true) ? $tone : 'slate';
    }

    protected static function uniqueSlug(string $label, array $existing): string
    {
        $base = Str::slug($label, '_') ?: 'peran';
        $taken = array_merge(array_keys($existing), array_keys(self::builtin()));

        $slug = $base;
        $i = 2;
        while (in_array($slug, $taken, true)) {
            $slug = $base . '_' . $i;
            $i++;
        }

        return $slug;
    }

    protected static function persist(array $custom): void
    {
        Setting::set(self::STORE_KEY, json_encode($custom), self::STORE_GROUP);
        Cache::forget('duty_roles:all');
    }
}
