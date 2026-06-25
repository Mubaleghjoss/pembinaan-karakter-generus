<?php

namespace App\Exports;

use App\Models\Siswa;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PresensiTemplateExport
{
    protected string $tanggal;

    public function __construct(
        protected bool $withSampleData = true,
        ?string $tanggal = null
    ) {
        $this->tanggal = $tanggal ?? now()->format('Y-m-d');
    }

    public function download(string $filename = 'template-presensi.xlsx'): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();

        $this->fillSiswaSheet($spreadsheet->getActiveSheet());
        $this->fillPamongSheet($spreadsheet->createSheet());
        $this->fillPetunjukSheet($spreadsheet->createSheet());

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    protected function fillSiswaSheet($sheet): void
    {
        $sheet->setTitle('Presensi Siswa');

        $headers = ['tipe', 'nis', 'nama_siswa', 'kelas', 'tanggal', 'status', 'jam_masuk', 'jam_keluar', 'keterangan'];
        $this->writeHeaders($sheet, $headers, '4F46E5');
        $this->setWidths($sheet, [
            'A' => 12,
            'B' => 16,
            'C' => 32,
            'D' => 20,
            'E' => 16,
            'F' => 16,
            'G' => 14,
            'H' => 14,
            'I' => 36,
        ]);

        if ($this->withSampleData) {
            $row = 2;
            Siswa::query()
                ->select(['id', 'nis', 'nama', 'kelas_id', 'is_active', 'status'])
                ->with('kelas:id,nama')
                ->where('is_active', true)
                ->orderBy('kelas_id')
                ->orderBy('nama')
                ->chunk(500, function ($siswaList) use ($sheet, &$row) {
                    foreach ($siswaList as $siswa) {
                        $sheet->setCellValue('A' . $row, 'siswa');
                        $sheet->setCellValueExplicit('B' . $row, (string) $siswa->nis, DataType::TYPE_STRING);
                        $sheet->setCellValue('C' . $row, $siswa->nama);
                        $sheet->setCellValue('D' . $row, $siswa->kelas->nama ?? '-');
                        $sheet->setCellValue('E' . $row, $this->tanggal);
                        $sheet->setCellValue('F' . $row, 'hadir');
                        $sheet->setCellValue('G' . $row, '07:00');
                        $sheet->setCellValue('H' . $row, '14:00');
                        $sheet->setCellValue('I' . $row, '');
                        $row++;
                    }
                });
        }

        $this->applyStatusValidation($sheet, 'F2:F2000');
        $this->applyDateFormat($sheet, 'E2:E2000');
        $sheet->setAutoFilter('A1:I1');
        $sheet->freezePane('A2');
    }

    protected function fillPamongSheet($sheet): void
    {
        $sheet->setTitle('Presensi Pamong');

        $headers = ['tipe', 'username', 'email', 'nama_pamong', 'tanggal', 'status', 'jam_masuk', 'jam_keluar', 'keterangan'];
        $this->writeHeaders($sheet, $headers, '0F766E');
        $this->setWidths($sheet, [
            'A' => 12,
            'B' => 22,
            'C' => 28,
            'D' => 32,
            'E' => 16,
            'F' => 16,
            'G' => 14,
            'H' => 14,
            'I' => 36,
        ]);

        if ($this->withSampleData) {
            $row = 2;
            User::query()
                ->select(['id', 'username', 'email', 'name', 'role_id', 'status'])
                ->where('status', 'active')
                ->whereHas('role', fn ($query) => $query->whereIn('name', User::attendanceRoleNames()))
                ->orderBy('name')
                ->orderBy('username')
                ->chunk(500, function ($users) use ($sheet, &$row) {
                    foreach ($users as $user) {
                        $sheet->setCellValue('A' . $row, 'pamong');
                        $sheet->setCellValue('B' . $row, $user->username);
                        $sheet->setCellValue('C' . $row, $user->email);
                        $sheet->setCellValue('D' . $row, $user->name);
                        $sheet->setCellValue('E' . $row, $this->tanggal);
                        $sheet->setCellValue('F' . $row, 'hadir');
                        $sheet->setCellValue('G' . $row, '07:00');
                        $sheet->setCellValue('H' . $row, '14:00');
                        $sheet->setCellValue('I' . $row, '');
                        $row++;
                    }
                });
        }

        $this->applyStatusValidation($sheet, 'F2:F2000');
        $this->applyDateFormat($sheet, 'E2:E2000');
        $sheet->setAutoFilter('A1:I1');
        $sheet->freezePane('A2');
    }

    protected function fillPetunjukSheet($sheet): void
    {
        $sheet->setTitle('Petunjuk');

        $rows = [
            ['PETUNJUK IMPORT PRESENSI HISTORIS'],
            [''],
            ['Isi sheet Presensi Siswa untuk siswa dan Presensi Pamong untuk pamong/pengurus PKG.'],
            ['Ubah kolom tanggal sesuai tanggal presensi lama yang ingin dimasukkan.'],
            ['Status yang diterima: hadir, terlambat, izin, sakit, alpha.'],
            ['Jam masuk dan jam keluar memakai format HH:MM, boleh dikosongkan untuk izin/sakit/alpha.'],
            ['Kolom nama_siswa, kelas, dan nama_pamong hanya referensi; sistem mencocokkan siswa dari NIS dan pamong dari username/email.'],
            ['Jika data tanggal yang sama sudah ada, import akan memperbarui data tersebut.'],
        ];

        $row = 1;
        foreach ($rows as $values) {
            $sheet->fromArray($values, null, 'A' . $row);
            $row++;
        }

        $sheet->setCellValue('A11', 'Daftar Status');
        $sheet->fromArray([
            ['hadir'],
            ['terlambat'],
            ['izin'],
            ['sakit'],
            ['alpha'],
        ], null, 'A12');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A11')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(110);
    }

    protected function writeHeaders($sheet, array $headers, string $color): void
    {
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . '1', $header);
            $column++;
        }

        $lastColumn = chr(ord('A') + count($headers) - 1);
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $color],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);
    }

    protected function setWidths($sheet, array $widths): void
    {
        foreach ($widths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    protected function applyStatusValidation($sheet, string $range): void
    {
        $validation = $sheet->getCell(explode(':', $range)[0])->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setFormula1('"hadir,terlambat,izin,sakit,alpha"');

        foreach ($sheet->rangeToArray($range, null, true, true, true) as $rowNumber => $_columns) {
            $sheet->getCell('F' . $rowNumber)->setDataValidation(clone $validation);
        }
    }

    protected function applyDateFormat($sheet, string $range): void
    {
        $sheet->getStyle($range)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
    }
}
