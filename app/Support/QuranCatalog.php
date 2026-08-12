<?php

namespace App\Support;

class QuranCatalog
{
    private const NAMES = [
        1 => 'Al-Fatihah', 'Al-Baqarah', 'Ali Imran', 'An-Nisa', 'Al-Maidah', 'Al-Anam', 'Al-Araf', 'Al-Anfal', 'At-Taubah', 'Yunus',
        'Hud', 'Yusuf', 'Ar-Rad', 'Ibrahim', 'Al-Hijr', 'An-Nahl', 'Al-Isra', 'Al-Kahfi', 'Maryam', 'Taha', 'Al-Anbiya', 'Al-Hajj',
        'Al-Muminun', 'An-Nur', 'Al-Furqan', 'Asy-Syuara', 'An-Naml', 'Al-Qasas', 'Al-Ankabut', 'Ar-Rum', 'Luqman', 'As-Sajdah',
        'Al-Ahzab', 'Saba', 'Fatir', 'Yasin', 'As-Saffat', 'Sad', 'Az-Zumar', 'Gafir', 'Fussilat', 'Asy-Syura', 'Az-Zukhruf', 'Ad-Dukhan',
        'Al-Jasiyah', 'Al-Ahqaf', 'Muhammad', 'Al-Fath', 'Al-Hujurat', 'Qaf', 'Az-Zariyat', 'At-Tur', 'An-Najm', 'Al-Qamar', 'Ar-Rahman',
        'Al-Waqiah', 'Al-Hadid', 'Al-Mujadilah', 'Al-Hasyr', 'Al-Mumtahanah', 'As-Saff', 'Al-Jumuah', 'Al-Munafiqun', 'At-Tagabun',
        'At-Talaq', 'At-Tahrim', 'Al-Mulk', 'Al-Qalam', 'Al-Haqqah', 'Al-Maarij', 'Nuh', 'Al-Jinn', 'Al-Muzzammil', 'Al-Muddassir',
        'Al-Qiyamah', 'Al-Insan', 'Al-Mursalat', 'An-Naba', 'An-Naziat', 'Abasa', 'At-Takwir', 'Al-Infitar', 'Al-Mutaffifin', 'Al-Insyiqaq',
        'Al-Buruj', 'At-Tariq', 'Al-Ala', 'Al-Gasyiyah', 'Al-Fajr', 'Al-Balad', 'Asy-Syams', 'Al-Lail', 'Ad-Duha', 'Asy-Syarh', 'At-Tin',
        'Al-Alaq', 'Al-Qadr', 'Al-Bayyinah', 'Az-Zalzalah', 'Al-Adiyat', 'Al-Qariah', 'At-Takasur', 'Al-Asr', 'Al-Humazah', 'Al-Fil',
        'Quraisy', 'Al-Maun', 'Al-Kausar', 'Al-Kafirun', 'An-Nasr', 'Al-Lahab', 'Al-Ikhlas', 'Al-Falaq', 'An-Nas',
    ];

    private const AYAH_COUNTS = [
        1 => 7, 286, 200, 176, 120, 165, 206, 75, 129, 109, 123, 111, 43, 52, 99, 128, 111, 110, 98, 135,
        112, 78, 118, 64, 77, 227, 93, 88, 69, 60, 34, 30, 73, 54, 45, 83, 182, 88, 75, 85, 54, 53, 89, 59,
        37, 35, 38, 29, 18, 45, 60, 49, 62, 55, 78, 96, 29, 22, 24, 13, 14, 11, 11, 18, 12, 12, 30, 52, 52, 44,
        28, 28, 20, 56, 40, 31, 50, 40, 46, 42, 29, 19, 36, 25, 22, 17, 19, 26, 30, 20, 15, 21, 11, 8, 8, 19,
        5, 8, 8, 11, 11, 8, 3, 9, 5, 4, 7, 3, 6, 3, 5, 4, 5, 6,
    ];

    public static function options(): array
    {
        return collect(self::NAMES)->mapWithKeys(fn (string $name, int $number) => [
            $number => $number.'. '.$name,
        ])->all();
    }

    public static function name(int $number): string
    {
        return self::NAMES[$number] ?? 'Surat '.$number;
    }

    public static function ayahCount(int $number): int
    {
        return self::AYAH_COUNTS[$number] ?? 0;
    }
}
