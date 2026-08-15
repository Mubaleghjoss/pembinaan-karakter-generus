<?php

namespace App\Services;

use App\Models\QuranReadingSheet;
use App\Models\Setting;
use App\Models\Siswa;
use App\Models\ThemeSetting;
use App\Support\QuranCatalog;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use setasign\Fpdi\Fpdi;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class QuranReadingDocumentService
{
    private const RENDER_CHUNK_SIZE = 6;

    public function __construct(private readonly QuranReadingScanService $scanner) {}

    public function report(Siswa $siswa, Collection $entries, array $filters = []): Response
    {
        $siswa->loadMissing('pamongAssignments.pamong:id,name');

        return $this->render('quran-reading.pdf.report', [
            'siswa' => $siswa,
            'pamongNames' => $this->pamongNames($siswa),
            'entries' => $entries,
            'filters' => $filters,
            'catalog' => QuranCatalog::class,
        ], $this->filename($siswa, 'Laporan Bacaan Al-Quran'));
    }

    public function sheet(QuranReadingSheet $sheet, string $plainToken): Response
    {
        return $this->renderPages(
            [$this->monthlyPage($sheet, $plainToken)],
            $this->filename($sheet->siswa, 'Lembar Bacaan Al-Quran Bulanan'),
        );
    }

    public function surahReference(Siswa $siswa): Response
    {
        return $this->renderPages(
            [$this->referencePage($siswa)],
            $this->filename($siswa, 'Peta dan Referensi Khatam Al-Quran'),
        );
    }

    public function duplex(QuranReadingSheet $sheet, string $plainToken): Response
    {
        return $this->renderPages([
            $this->monthlyPage($sheet, $plainToken),
            $this->referencePage($sheet->siswa),
        ], $this->filename($sheet->siswa, 'Paket Bacaan Bulanan dan Peta Khatam'));
    }

    public function bulkDocuments(array $pages, string $filename): Response
    {
        return $this->renderPages($pages, $filename);
    }

    public function blankMonthly(): Response
    {
        return $this->renderPages(
            [$this->blankMonthlyPage()],
            'Lembar Bacaan Al-Quran Bulanan Kosong.pdf',
        );
    }

    public function blankSurahReference(): Response
    {
        return $this->renderPages(
            [$this->blankReferencePage()],
            'Referensi 114 Surat Kosong.pdf',
        );
    }

    public function blankDuplex(): Response
    {
        return $this->renderPages([
            $this->blankMonthlyPage(),
            $this->blankReferencePage(),
        ], 'Paket Bacaan Al-Quran Kosong Bolak-Balik.pdf');
    }

    public function monthlyPage(QuranReadingSheet $sheet, string $plainToken): array
    {
        $sheet->loadMissing(['siswa.pamongAssignments.pamong:id,name']);

        return [
            'type' => 'monthly',
            'sheet' => $sheet,
            'siswa' => $sheet->siswa,
            'pamongNames' => $this->pamongNames($sheet->siswa),
            'qrDataUri' => $this->qrDataUri(route('public.quran.scan.open', [
                'code' => $this->scanner->publicCode($sheet, $plainToken),
            ])),
        ];
    }

    public function referencePage(Siswa $siswa): array
    {
        $siswa->loadMissing('pamongAssignments.pamong:id,name');

        return [
            'type' => 'reference',
            'siswa' => $siswa,
            'pamongNames' => $this->pamongNames($siswa),
        ];
    }

    public function blankMonthlyPage(): array
    {
        return [
            'type' => 'monthly',
            'blank' => true,
            'rowCount' => 31,
        ];
    }

    public function blankReferencePage(): array
    {
        return [
            'type' => 'reference',
            'blank' => true,
        ];
    }

    private function renderPages(array $pages, string $filename): Response
    {
        abort_unless(class_exists(Dompdf::class), 503, 'Generator PDF belum tersedia di server.');

        if (count($pages) <= self::RENDER_CHUNK_SIZE) {
            return $this->pdfResponse($this->renderPageChunk($pages), $filename);
        }

        abort_unless(class_exists(Fpdi::class), 503, 'Penggabung PDF belum tersedia di server.');
        $jobDirectory = storage_path('app/private/quran-pdf-temp/'.Str::uuid());
        File::ensureDirectoryExists($jobDirectory);

        try {
            $chunkFiles = [];
            foreach (array_chunk($pages, self::RENDER_CHUNK_SIZE) as $index => $chunk) {
                $path = $jobDirectory.'/chunk-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT).'.pdf';
                File::put($path, $this->renderPageChunk($chunk));
                $chunkFiles[] = $path;
            }

            $merger = new Fpdi('L', 'mm', 'A4');
            $merger->SetAutoPageBreak(false);
            foreach ($chunkFiles as $path) {
                $pageCount = $merger->setSourceFile($path);
                for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                    $template = $merger->importPage($pageNumber);
                    $size = $merger->getTemplateSize($template);
                    $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
                    $merger->AddPage($orientation, [$size['width'], $size['height']]);
                    $merger->useTemplate($template);
                }
            }

            return $this->pdfResponse($merger->Output('S'), $filename);
        } finally {
            File::deleteDirectory($jobDirectory);
        }
    }

    private function renderPageChunk(array $pages): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('quran-reading.pdf.document', [
            'pages' => $pages,
            'catalog' => QuranCatalog::class,
            'logoDataUri' => $this->logoDataUri(),
            'verseImageDataUri' => $this->verseImageDataUri(),
        ])->render(), 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $output = $dompdf->output();
        unset($dompdf);
        gc_collect_cycles();

        return $output;
    }

    private function render(string $view, array $data, string $filename, string $orientation = 'portrait'): Response
    {
        abort_unless(class_exists(Dompdf::class), 503, 'Generator PDF belum tersedia di server.');

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view($view, $data)->render(), 'UTF-8');
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();

        return $this->pdfResponse($dompdf->output(), $filename);
    }

    private function pdfResponse(string $contents, string $filename): Response
    {
        return response($contents, 200, [
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

    private function pamongNames(Siswa $siswa): string
    {
        $names = $siswa->pamongAssignments
            ->pluck('pamong.name')
            ->filter()
            ->unique()
            ->values()
            ->implode(', ');

        return $names !== '' ? $names : 'Belum ditetapkan';
    }

    private function qrDataUri(string $payload): string
    {
        return Builder::create()
            ->writer(new PngWriter)
            ->data($payload)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::Quartile)
            ->size(420)
            ->margin(24)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build()
            ->getDataUri();
    }

    private function logoDataUri(): ?string
    {
        $configured = Setting::get('site_logo') ?: ThemeSetting::current()->logo_path;
        if ($configured && Storage::disk('public')->exists($configured)) {
            return $this->fileDataUri(Storage::disk('public')->path($configured));
        }

        $fallback = public_path('images/icons/pkg-logo-192.png');

        return File::exists($fallback) ? $this->fileDataUri($fallback) : null;
    }

    private function verseImageDataUri(): ?string
    {
        $path = resource_path('images/quran/muzzammil-4.png');

        return File::exists($path) ? $this->fileDataUri($path) : null;
    }

    private function fileDataUri(string $path): string
    {
        $contents = File::get($path);
        $mime = File::mimeType($path) ?: match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'svg' => 'image/svg+xml',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'image/png',
        };

        if ($contents === '') {
            throw new RuntimeException('Berkas logo PKG kosong.');
        }

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }
}
