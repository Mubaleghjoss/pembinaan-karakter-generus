<?php

namespace App\Services;

use App\Models\QuranReadingSheet;
use App\Models\Siswa;
use App\Support\QuranCatalog;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Response;

class QuranReadingDocumentService
{
    public function __construct(private readonly QuranReadingScanService $scanner)
    {
    }

    public function report(Siswa $siswa, Collection $entries, array $filters = []): Response
    {
        return $this->render('quran-reading.pdf.report', [
            'siswa' => $siswa,
            'entries' => $entries,
            'filters' => $filters,
            'catalog' => QuranCatalog::class,
        ], $this->filename($siswa, 'Laporan Bacaan Al-Quran'));
    }

    public function sheet(QuranReadingSheet $sheet, string $plainToken): Response
    {
        $sheet->loadMissing('siswa.kelas');
        $payload = $this->scanner->payload($sheet, $plainToken);

        return $this->render('quran-reading.pdf.sheet', [
            'sheet' => $sheet,
            'siswa' => $sheet->siswa,
            'qrDataUri' => $this->qrDataUri($payload),
            'catalog' => QuranCatalog::class,
        ], $this->filename($sheet->siswa, 'Lembar Lanjutan Bacaan Al-Quran'));
    }

    private function render(string $view, array $data, string $filename): Response
    {
        abort_unless(class_exists(Dompdf::class), 503, 'Generator PDF belum tersedia di server.');

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view($view, $data)->render(), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename,
                Str::ascii($filename),
            ),
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    private function filename(Siswa $siswa, string $document): string
    {
        $name = preg_replace('/[\\\\\/:*?"<>|%\x00-\x1F\x7F]+/u', ' ', trim((string) $siswa->nama));
        $name = preg_replace('/\s+/u', ' ', (string) $name);

        return ($name !== '' ? $name : 'Generus').' - '.$document.'.pdf';
    }

    private function qrDataUri(string $payload): string
    {
        $result = Builder::create()
            ->writer(new SvgWriter())
            ->data($payload)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::Quartile)
            ->size(300)
            ->margin(18)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();

        return 'data:image/svg+xml;base64,'.base64_encode($result->getString());
    }
}
