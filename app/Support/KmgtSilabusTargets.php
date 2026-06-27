<?php

namespace App\Support;

use App\Models\MateriTarget;

class KmgtSilabusTargets
{
    public static function records(): array
    {
        $records = [];
        $categoryOrder = array_keys(self::categoryMap());

        foreach (self::sections() as $section) {
            foreach (self::categoryMap() as $field => $category) {
                if (empty($section[$field])) {
                    continue;
                }

                $records[] = [
                    'source_key' => sprintf('kmgt_%s_%s_%s', $section['series'], $section['class'], $category),
                    'target_grade' => $section['target_grade'],
                    'semester' => $section['semester'],
                    'category' => $category,
                    'title' => $section[$field]['title'],
                    'description' => trim(($section[$field]['description'] ?? '') . "\n\n" . $section['note']),
                    'sort_order' => (array_search($field, $categoryOrder, true) + 1) * 10,
                    'is_active' => true,
                ];
            }
        }

        foreach ($records as $record) {
            if ($record['target_grade'] !== TargetGrade::SMA_12) {
                continue;
            }

            $pranikahRecord = $record;
            $pranikahRecord['source_key'] = 'kmgt_pranikah_'.$record['source_key'];
            $pranikahRecord['target_grade'] = TargetGrade::PRANIKAH;
            $pranikahRecord['description'] = trim(
                $record['description']."\n\nTarget lanjutan untuk generus Pranikah setelah menyelesaikan SMA/K."
            );
            $records[] = $pranikahRecord;
        }

        return $records;
    }

    public static function categoryMap(): array
    {
        return [
            'quran' => MateriTarget::CATEGORY_BACAAN_MAKNA_AL_QURAN,
            'hadits' => MateriTarget::CATEGORY_MAKNA_AL_HADITS,
            'tulis_arab' => MateriTarget::CATEGORY_TULIS_ARAB,
            'tajwid' => MateriTarget::CATEGORY_TAJWID,
            'hafalan_dalil' => MateriTarget::CATEGORY_HAFALAN_DALIL,
            'hafalan_surat' => MateriTarget::CATEGORY_HAFALAN_SURAT,
            'doa' => MateriTarget::CATEGORY_DOA_HARIAN,
            'keilmuan' => MateriTarget::CATEGORY_KEILMUAN_KEFAHAMAN,
            'akhlaq' => MateriTarget::CATEGORY_MATERI_AKHLAQ,
            'praktek' => MateriTarget::CATEGORY_PRAKTEK_IBADAH,
            'kemandirian' => MateriTarget::CATEGORY_MATERI_KEMANDIRIAN,
        ];
    }

    private static function sections(): array
    {
        return [
            self::cSection(
                class: 1,
                grade: TargetGrade::SMP_7,
                semester: 1,
                quran: 'Al-Quran Juz 12-14 dan Makna Juz 21-22',
                quranDescription: 'Bacaan Al-Quran Juz 12, 13, 14 dari Surat Hud ayat 6 sampai An Nahl ayat 128. Makna Juz 21, 22 dari Al Ankabut ayat 46 sampai Saba ayat 31.',
                hadits: 'Makna Hadits Kitabul Sholah',
                tajwid: 'Istiadzah, basmalah, nun sukun, mim sukun, dan ghunnah',
                tajwidDescription: 'Hukum istiadzah dan basmalah; nun sukun dan tanwin; mim sukun; mim tasydid dan nun tasydid.',
                hafalanDalil: 'Dalil wajibnya berjamaah',
                hafalanSurat: 'An-Nas sampai At-Thoriq, Al-Buruj, Al-Insyiqoq, dan akhir Al-Hasyr',
                keilmuan: 'Akhirat, ihsan, struktur organisasi, 4 roda berputar, 5 usaha kefahaman',
                keilmuanDescription: 'Mengulang materi sebelumnya; memahami keutamaan urusan akhirat, ihsan, struktur LDII/Persinas ASAD/Senkom, 4 roda berputar, 5 usaha mencari kefahaman, dan ijtihad peraturan bernomor.'
            ),
            self::cSection(
                class: 2,
                grade: TargetGrade::SMP_7,
                semester: 2,
                quran: 'Al-Quran Juz 15-17 dan Makna Juz 22-23',
                quranDescription: 'Bacaan Al-Quran Juz 15, 16, 17 dari Surat Bani Israil ayat 1 sampai Al Hajji ayat 78. Makna Juz 22, 23 dari Saba ayat 32 sampai Az Zumar ayat 31.',
                hadits: 'Makna Hadits Kitab Adab',
                tajwid: 'Al marifat, lam fiil, dan idghom',
                tajwidDescription: 'Hukum Al Marifat, lam yang terdapat dalam kalimah fiil, dan idghom mutamatsilain, mutaqoribain, mutajanisain.',
                hafalanDalil: 'Dalil wajibnya berjamaah',
                hafalanSurat: 'An-Nas sampai At-Thoriq, Al-Buruj, Al-Insyiqoq, dan akhir Al-Hasyr',
                keilmuan: 'Akhirat, ihsan, struktur organisasi, 4 roda berputar, 5 usaha kefahaman',
                keilmuanDescription: 'Mengulang materi sebelumnya; memahami keutamaan urusan akhirat, ihsan, struktur LDII/Persinas ASAD/Senkom, 4 roda berputar, dan 5 usaha mencari kefahaman.'
            ),
            self::cSection(
                class: 3,
                grade: TargetGrade::SMP_8,
                semester: 1,
                quran: 'Al-Quran Juz 18-20 dan Makna Juz 24-25',
                quranDescription: 'Bacaan Al-Quran Juz 18, 19, 20 dari Surat Al Mukminun ayat 1 sampai Al Ankabut ayat 45. Makna Juz 24, 25 dari Az Zumar ayat 32 sampai Az Zuhruf ayat 33.',
                hadits: 'Makna Hadits Kitabus Sholatin Nawafil',
                tajwid: 'Hukum mad dan pembagiannya',
                tajwidDescription: 'Mad thobii, wajib muttashil, jaiz munfashil, lazim, layin, aridh lissukun, shilah, iwadl, badal, dan pembagian mad lain dalam Hidayatul Mustafid.',
                hafalanDalil: 'Lima Syarat Kerukunan dan Empat Maqodirulloh',
                hafalanSurat: 'An-Nas sampai Al-Insyiqoq, Al-Muthaffifin sampai At-Takwir, Ayat Kursi, dan akhir Al-Baqoroh',
                keilmuan: 'Mengulang materi jenjang sebelumnya',
                keilmuanDescription: 'Menguatkan kembali materi kefahaman agama pada jenjang sebelumnya.'
            ),
            self::cSection(
                class: 4,
                grade: TargetGrade::SMP_8,
                semester: 2,
                quran: 'Al-Quran Juz 21-23 dan Makna Juz 25-26',
                quranDescription: 'Bacaan Al-Quran Juz 21 sampai 23 dari Al Ankabut ayat 46 sampai Az Zumar ayat 31. Makna Juz 25, 26 dari Az Zuhruf ayat 34 sampai Adz Dzariyat ayat 30.',
                hadits: 'Makna Hadits Kitab Dawat',
                tajwid: 'Hukum Ro dan Qolqolah',
                tajwidDescription: 'Hukum Ro tebal, tipis, dan boleh tebal atau tipis; Qolqolah kubro dan shugro.',
                hafalanDalil: 'Lima Syarat Kerukunan dan Empat Maqodirulloh',
                hafalanSurat: 'An-Nas sampai Al-Insyiqoq, Al-Muthaffifin sampai At-Takwir, Ayat Kursi, dan akhir Al-Baqoroh',
                keilmuan: 'Mengulang materi jenjang sebelumnya',
                keilmuanDescription: 'Menguatkan kembali materi kefahaman agama pada jenjang sebelumnya.'
            ),
            self::cSection(
                class: 5,
                grade: TargetGrade::SMP_9,
                semester: 1,
                quran: 'Al-Quran Juz 24-26 dan Makna Juz 27-28',
                quranDescription: 'Bacaan Al-Quran Juz 24, 25, 26 dari Az Zumar ayat 1 sampai Adz Dzariyat ayat 30. Makna Juz 27, 28 dari At-Tuur ayat 1 sampai At-Tahrim ayat 12.',
                hadits: 'Makna Hadits Kitabul Adillah',
                tajwid: 'Makhorijul huruf',
                tajwidDescription: 'Mempraktikkan tajwid pada hukum makhorijul huruf.',
                hafalanDalil: 'Empat Roda Berputar dan Lima Usaha mencari kefahaman',
                hafalanSurat: 'An-Nas sampai Al-Infithor, Abasa sampai An-Naba, dan As-Shof 10-13',
                keilmuan: '5 pembinaan ke dalam dan 5 tahapan pembinaan QHJ',
                keilmuanDescription: 'Mengulang materi sebelumnya; memahami 5 pembinaan ke dalam dan 5 tahapan pembinaan Quran Hadits Jamaah.'
            ),
            self::cSection(
                class: 6,
                grade: TargetGrade::SMP_9,
                semester: 2,
                quran: 'Al-Quran Juz 27-29 dan Makna Juz 29-30',
                quranDescription: 'Bacaan Al-Quran Juz 27, 28, 29 dari Adz Dzariyat ayat 31 sampai Al Mursalat ayat 50. Makna Juz 29, 30 dari Al Mulk ayat 1 sampai An-Nas ayat 6.',
                hadits: 'Makna Hadits Kitabus Sifati Janah Wannar',
                tajwid: 'Sifat huruf, waqof, dan bidah dalam bacaan',
                tajwidDescription: 'Hukum sifat-sifat huruf, pembagian waqof, dan bidah dalam bacaan.',
                hafalanDalil: 'Empat Roda Berputar dan Lima Usaha mencari kefahaman',
                hafalanSurat: 'An-Nas sampai Al-Infithor, Abasa sampai An-Naba, dan As-Shof 10-13',
                keilmuan: '5 pembinaan ke dalam dan 5 tahapan pembinaan QHJ',
                keilmuanDescription: 'Mengulang materi sebelumnya; memahami 5 pembinaan ke dalam dan 5 tahapan pembinaan Quran Hadits Jamaah.'
            ),
            self::dSection(
                class: 1,
                grade: TargetGrade::SMA_10,
                semester: 1,
                quran: 'Bacaan sebelum makna Al-Quran dan Makna Juz 9-10',
                quranDescription: 'Bacaan sebelum makna Al-Quran. Makna Juz 9, 10 dari Al Arof ayat 88 sampai At Taubah ayat 93.',
                hadits: 'Makna Hadits Kitabul Janaiz dan Kitabus Soum',
                hafalanDalil: 'Lima Bab, Empat Tali Keimanan, dan Tri Sukses Generasi Penerus',
                hafalanSurat: 'An-Nas sampai Abasa, Al-Baqoroh pilihan, akhir Al-Hasyr, dan As-Shof 10-13',
                keilmuan: 'Puasa sunah, 3 syarat mendapat pertolongan, 5 tahapan perjuangan',
                keilmuanDescription: 'Mengulang materi sebelumnya; memahami puasa sunah, 3 syarat mendapat pertolongan, dan 5 tahapan perjuangan.'
            ),
            self::dSection(
                class: 2,
                grade: TargetGrade::SMA_10,
                semester: 2,
                quran: 'Bacaan sebelum makna Al-Quran dan Makna Juz 11-12',
                quranDescription: 'Bacaan sebelum makna Al-Quran. Makna Juz 11, 12 dari At Taubah ayat 94 sampai Yusuf ayat 52.',
                hadits: 'Makna Hadits Kitabus Soum dan Kitabul Imaroh',
                hafalanDalil: 'Lima Bab, Empat Tali Keimanan, dan Tri Sukses Generasi Penerus',
                hafalanSurat: 'An-Nas sampai Abasa, Al-Baqoroh pilihan, akhir Al-Hasyr, dan As-Shof 10-13',
                keilmuan: 'Puasa sunah, 4 syarat keberhasilan perjuangan, 4 sifat wajib Ulil Amri',
                keilmuanDescription: 'Mengulang materi sebelumnya; memahami puasa sunah, 4 syarat keberhasilan perjuangan, dan 4 sifat wajib Ulil Amri.'
            ),
            self::dSection(
                class: 3,
                grade: TargetGrade::SMA_11,
                semester: 1,
                quran: 'Bacaan sebelum makna Al-Quran dan Makna Juz 13-14',
                quranDescription: 'Bacaan sebelum makna Al-Quran. Makna Juz 13, 14 dari Yusuf ayat 53 sampai An Nahl ayat 128.',
                hadits: 'Makna Hadits Kitabul Ahkam dan Kitabus Jihad',
                hafalanDalil: 'Enam Thobiat Luhur Jamaah, wajibnya berjamaah, dan Lima Syarat Kerukunan',
                hafalanSurat: 'An-Nas sampai An-Naziat, Al-Mukminun pilihan, dan Al-Kahfi 1-10',
                keilmuan: 'Prinsip kerja jamaah, program kerja jamaah, dan 10 peramutan',
                keilmuanDescription: 'Memahami dan mengulang materi sebelumnya; memahami prinsip kerja jamaah, program kerja jamaah, dan 10 peramutan dalam jamaah.'
            ),
            self::dSection(
                class: 4,
                grade: TargetGrade::SMA_11,
                semester: 2,
                quran: 'Bacaan sebelum makna Al-Quran dan Makna Juz 15-16',
                quranDescription: 'Bacaan sebelum makna Al-Quran. Makna Juz 15, 16 dari Bani Isroil ayat 1 sampai Toha ayat 135.',
                hadits: 'Makna Hadits Kitabus Jihad dan Kitabul Manasik wal Jihad',
                hafalanDalil: 'Enam Thobiat Luhur Jamaah, wajibnya berjamaah, dan Lima Syarat Kerukunan',
                hafalanSurat: 'An-Nas sampai An-Naziat, Al-Mukminun pilihan, dan Al-Kahfi 1-10',
                keilmuan: 'Prinsip kerja jamaah, program kerja jamaah, dan 10 peramutan',
                keilmuanDescription: 'Memahami dan mengulang materi sebelumnya; memahami prinsip kerja jamaah, program kerja jamaah, dan 10 peramutan dalam jamaah.'
            ),
            self::dSection(
                class: 5,
                grade: TargetGrade::SMA_12,
                semester: 1,
                quran: 'Bacaan sebelum makna Al-Quran dan Makna Juz 17-18',
                quranDescription: 'Bacaan sebelum makna Al-Quran. Makna Juz 17, 18 dari Al Anbiya ayat 1 sampai Al Furqon ayat 21.',
                hadits: 'Makna Hadits Kitabul Khutbah',
                hafalanDalil: 'Enam Thobiat Luhur Jamaah, wajibnya berjamaah, dan Lima Syarat Kerukunan',
                hafalanSurat: 'Juz 30 dan Surat Al-Mulk',
                keilmuan: 'Prinsip kerja jamaah, program kerja jamaah, 10 peramutan, 7 fakta sahnya jamaah, dan sejarah perintisan jamaah',
                keilmuanDescription: 'Memahami dan mengulang materi sebelumnya; memahami prinsip kerja jamaah, program kerja jamaah, 10 peramutan dalam jamaah, puasa sunah, 7 fakta sahnya jamaah, dan sejarah perintisan jamaah di Indonesia.'
            ),
            self::dSection(
                class: 6,
                grade: TargetGrade::SMA_12,
                semester: 2,
                quran: 'Bacaan sebelum makna Al-Quran dan Makna Juz 19-20',
                quranDescription: 'Bacaan sebelum makna Al-Quran. Makna Juz 19, 20 dari Al Furqon ayat 22 sampai Al Ankabut ayat 44.',
                hadits: 'Makna Hadits Kitabul Haji',
                hafalanDalil: 'Enam Thobiat Luhur Jamaah, wajibnya berjamaah, dan Lima Syarat Kerukunan',
                hafalanSurat: 'Juz 30 dan Surat Al-Mulk',
                keilmuan: 'Prinsip kerja jamaah, program kerja jamaah, 10 peramutan, 7 fakta sahnya jamaah, sejarah perintisan jamaah, dan materi 30 point',
                keilmuanDescription: 'Memahami dan mengulang materi sebelumnya; memahami prinsip kerja jamaah, program kerja jamaah, 10 peramutan dalam jamaah, puasa sunah, 7 fakta sahnya jamaah, sejarah perintisan jamaah di Indonesia, dan materi 30 point untuk membedakan antara jamaah dan bukan jamaah.'
            ),
        ];
    }

    private static function cSection(
        int $class,
        string $grade,
        int $semester,
        string $quran,
        string $quranDescription,
        string $hadits,
        string $tajwid,
        string $tajwidDescription,
        string $hafalanDalil,
        string $hafalanSurat,
        string $keilmuan,
        string $keilmuanDescription
    ): array {
        return [
            'series' => 'c',
            'class' => $class,
            'target_grade' => $grade,
            'semester' => $semester,
            'note' => "Sumber: Silabus KMGT C Kelas {$class}. Alokasi utama ditempuh 6 bulan termasuk munaqosah.",
            'quran' => self::target($quran, $quranDescription),
            'hadits' => self::target($hadits, $hadits),
            'tulis_arab' => self::target('Terampil menulis Arab dan Pegon', 'Melatih keterampilan menulis Arab dan Pegon.'),
            'tajwid' => self::target($tajwid, $tajwidDescription),
            'hafalan_dalil' => self::target($hafalanDalil, 'Menjaga hafalan dalil jenjang sebelumnya dan menambah target dalil sesuai paket semester.'),
            'hafalan_surat' => self::target($hafalanSurat, 'Menjaga hafalan surat sebelumnya dan menambah hafalan surat sesuai target semester.'),
            'doa' => self::target('Menjaga doa harian jenjang sebelumnya', 'Menjaga dan mempraktikkan hafalan doa harian yang sudah dihafal pada jenjang sebelumnya.'),
            'keilmuan' => self::target($keilmuan, $keilmuanDescription),
            'akhlaq' => self::target('Akhlaq pribadi, keluarga, dan masyarakat', self::akhlaqDescription()),
            'praktek' => self::target('Praktek ibadah dan adab', 'Mempraktikkan ibadah jenjang sebelumnya, sholat beserta bacaan dan doanya, rutin membaca Al-Quran, rutin membaca PR 13, doa-doa yang telah dihafal, dan adab jenjang sebelumnya.'),
            'kemandirian' => self::target('Kemandirian pra remaja', 'Kemandirian pribadi, keluarga, lingkungan jamaah dan sekolah, serta keterampilan generus putra dan putri sesuai buku KMGT Tatakrama dan Kemandirian.'),
        ];
    }

    private static function dSection(
        int $class,
        string $grade,
        int $semester,
        string $quran,
        string $quranDescription,
        string $hadits,
        string $hafalanDalil,
        string $hafalanSurat,
        string $keilmuan,
        string $keilmuanDescription
    ): array {
        return [
            'series' => 'd',
            'class' => $class,
            'target_grade' => $grade,
            'semester' => $semester,
            'note' => "Sumber: Silabus KMGT D Kelas {$class}. Alokasi utama ditempuh 6 bulan termasuk munaqosah.",
            'quran' => self::target($quran, $quranDescription),
            'hadits' => self::target($hadits, $hadits),
            'tulis_arab' => self::target('Terampil menulis Arab dan Pegon', 'Melatih keterampilan menulis Arab dan Pegon.'),
            'hafalan_dalil' => self::target($hafalanDalil, 'Menjaga hafalan dalil jenjang sebelumnya dan menambah target dalil sesuai paket semester.'),
            'hafalan_surat' => self::target($hafalanSurat, 'Menjaga hafalan surat sebelumnya dan menambah hafalan surat pilihan sesuai target semester.'),
            'doa' => self::target('Menjaga doa harian jenjang sebelumnya', 'Menjaga dan mempraktikkan hafalan doa harian yang sudah dihafal pada jenjang sebelumnya.'),
            'keilmuan' => self::target($keilmuan, $keilmuanDescription),
            'akhlaq' => self::target('Akhlaq pribadi, keluarga, dan masyarakat', self::akhlaqDescription()),
            'praktek' => self::target('Praktek ibadah dan adab', 'Mempraktikkan ibadah jenjang sebelumnya, puasa sunah, doa-doa yang telah dihafal, dan adab jenjang sebelumnya.'),
            'kemandirian' => self::target('Kemandirian remaja', 'Kemandirian pribadi, keluarga, lingkungan jamaah dan sekolah, lingkungan umum atau masyarakat, serta keterampilan generus putra dan putri sesuai buku KMGT Tatakrama dan Kemandirian.'),
        ];
    }

    private static function target(string $title, string $description): array
    {
        return compact('title', 'description');
    }

    private static function akhlaqDescription(): string
    {
        return 'Membiasakan akhlaq baik seperti tawakkal, qonaah, menjaga perasaan, keporo ngalah, toleran, tepo seliro, muhasabah, menerima diri dan keadaan orang tua; menjauhi akhlaq tercela; berbuat baik kepada orang tua dan masyarakat dengan jujur, amanah, rukun, kompak, kerja sama, hormat, dermawan, tawadhu, dan taat peraturan.';
    }
}
