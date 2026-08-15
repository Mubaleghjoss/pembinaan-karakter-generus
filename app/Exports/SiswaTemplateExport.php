<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SiswaTemplateExport
{
    /**
     * Create Excel template for siswa import
     */
    public function create(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Siswa');

        $headers = [
            'A1' => 'NIS',
            'B1' => 'Password',
            'C1' => 'Nama',
            'D1' => 'Jenis Kelamin',
            'E1' => 'Kelas Sekolah',
            'F1' => 'Tanggal Lahir',
            'G1' => 'Kelompok',
            'H1' => 'Phone',
            'I1' => 'Nama Wali',
            'J1' => 'Phone Wali',
            'K1' => 'Pamong Username',
            'L1' => 'Foto',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style header
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];
        $sheet->getStyle('A1:L1')->applyFromArray($headerStyle);

        // Sample data - Uses Kelas names (NIS, Password, Nama, JK, Kelas, TglLahir, Alamat, Phone, NamaWali, PhoneWali, Foto)
        $sampleData = [
            ['12345', '12345', 'Ahmad Fauzi', 'L', 'SMP Kelas 1', '2013-05-15', 'panunggangan utara', '081234567890', 'Budi Santoso', '081234567891', 'pamong.ahmad', 'ahmad_fauzi.jpg'],
            ['12346', 'password123', 'Siti Nurhaliza', 'P', 'SMA/SMK Kelas 2', '2010-08-20', 'sawah dalam', '081234567892', 'Hasan Abdullah', '081234567893', 'pamong.ahmad; pamong.siti', 'siti_nurhaliza.jpg'],
            ['12347', '', 'Muhammad Rizki', 'L', 'Pranikah', '2007-03-10', 'pakulonan', '', 'Ahmad Yani', '081234567894', '', ''],
        ];

        $row = 2;
        foreach ($sampleData as $data) {
            $sheet->fromArray($data, null, 'A' . $row);
            $row++;
        }

        // Style data rows
        $dataStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];
        $sheet->getStyle('A2:L4')->applyFromArray($dataStyle);

        // Column widths
        $columnWidths = [
            'A' => 12,  // NIS
            'B' => 15,  // Password
            'C' => 25,  // Nama
            'D' => 15,  // Jenis Kelamin
            'E' => 24,  // Kelas Sekolah
            'F' => 15,  // Tanggal Lahir
            'G' => 35,  // Alamat
            'H' => 15,  // Phone
            'I' => 20,  // Nama Wali
            'J' => 15,  // Phone Wali
            'K' => 30,  // Pamong Username
            'L' => 20,  // Foto
        ];

        foreach ($columnWidths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Add instructions sheet
        $instructionSheet = $spreadsheet->createSheet();
        $instructionSheet->setTitle('Petunjuk');
        
        $instructions = [
            ['PETUNJUK PENGISIAN DATA SISWA'],
            [''],
            ['Kolom', 'Keterangan', 'Contoh'],
            ['NIS', 'Nomor Induk Siswa (WAJIB, unik) - juga sebagai username login', '12345'],
            ['Password', 'Password untuk login (opsional, jika kosong = NIS)', 'password123'],
            ['Nama', 'Nama lengkap siswa (WAJIB)', 'Ahmad Fauzi'],
            ['Jenis Kelamin', 'L untuk Laki-laki, P untuk Perempuan (opsional)', 'L'],
            ['Kelas Sekolah', 'Wajib: SMP Kelas 1-3, SMA/SMK Kelas 1-3, atau Pranikah', 'SMP Kelas 1'],
            ['Tanggal Lahir', 'Format: YYYY-MM-DD (opsional)', '2008-05-15'],
            ['Kelompok', 'Pilih salah satu: panunggangan utara, sawah dalam, pakulonan', 'panunggangan utara'],
            ['Phone', 'Nomor HP pribadi siswa (opsional)', '081234567890'],
            ['Nama Wali', 'Nama wali/orang tua (opsional)', 'Budi Santoso'],
            ['Phone Wali', 'Nomor telepon wali (opsional)', '081234567891'],
            ['Pamong Username', 'Opsional. Pisahkan beberapa username dengan titik koma', 'pamong.ahmad; pamong.siti'],
            ['Foto', 'Nama file foto dalam ZIP (opsional)', 'ahmad_fauzi.jpg'],
            [''],
            ['DATA MINIMAL YANG DIPERLUKAN:'],
            ['- NIS (wajib)'],
            ['- Nama (wajib)'],
            ['- Password (opsional, default = NIS)'],
            [''],
            ['INFORMASI AKUN LOGIN SISWA:'],
            ['Username = NIS siswa'],
            ['Password = Kolom Password (jika kosong, password = NIS)'],
            ['URL Login = /siswa/login'],
            [''],
            ['PILIHAN KELAS SEKOLAH:'],
            ['SMP Kelas 1, SMP Kelas 2, SMP Kelas 3, SMA/SMK Kelas 1, SMA/SMK Kelas 2, SMA/SMK Kelas 3, Pranikah'],
            [''],
            ['CATATAN PENTING:'],
            ['1. Hapus baris contoh sebelum mengisi data'],
            ['2. NIS harus unik dan belum terdaftar'],
            ['3. NIS, Nama, dan Kelas Sekolah wajib diisi'],
            ['4. Impor tidak membuat kelas internal baru'],
            ['5. Satu Generus dapat ditautkan ke beberapa Pamong'],
            ['6. Jika menggunakan foto, siapkan file ZIP berisi foto-foto'],
            ['7. Nama file foto harus sesuai dengan yang ditulis di kolom Foto'],
            ['8. Format foto yang didukung: JPG, JPEG, PNG, GIF'],
            ['9. Siswa dapat login dengan NIS sebagai username'],
        ];

        $instructionSheet->fromArray($instructions, null, 'A1');
        
        // Style instruction header
        $instructionSheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
        ]);
        $instructionSheet->getStyle('A3:C3')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E5E7EB'],
            ],
        ]);
        
        $instructionSheet->getColumnDimension('A')->setWidth(20);
        $instructionSheet->getColumnDimension('B')->setWidth(50);
        $instructionSheet->getColumnDimension('C')->setWidth(25);

        // Set active sheet back to data
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Save template to file
     */
    public function save(string $path): void
    {
        $spreadsheet = $this->create();
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);
    }

    /**
     * Download template
     */
    public function download(): void
    {
        $spreadsheet = $this->create();
        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="template_import_siswa.xlsx"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}
