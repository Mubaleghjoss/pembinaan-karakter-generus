<?php

namespace App\Services;

use App\Models\Presentation;
use App\Models\PresentationAsset;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

class PresentationExportService
{
    private const SLIDE_WIDTH_EMU = 12192000;

    private const SLIDE_HEIGHT_EMU = 6858000;

    public function pdf(Presentation $presentation): Response
    {
        $presentation->loadMissing(['assets', 'creator']);

        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('presentations.export-pdf', [
            'presentation' => $presentation,
            'slides' => $this->pdfSlides($presentation),
        ])->render(), 'UTF-8');
        $dompdf->setPaper([0, 0, 960, 540]);
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->filename($presentation, 'pdf').'"',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function pptx(Presentation $presentation): BinaryFileResponse
    {
        $presentation->loadMissing(['assets', 'creator']);

        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi ZIP belum tersedia pada server.');
        }

        $temporaryDirectory = storage_path('app/tmp/presentation-exports');
        File::ensureDirectoryExists($temporaryDirectory);
        $temporaryPath = tempnam($temporaryDirectory, 'presentation-');

        if ($temporaryPath === false) {
            throw new RuntimeException('Folder sementara untuk ekspor tidak dapat digunakan.');
        }

        $zip = new ZipArchive;
        if ($zip->open($temporaryPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($temporaryPath);
            throw new RuntimeException('File PowerPoint tidak dapat dibuat.');
        }

        try {
            $this->writePptxPackage($zip, $presentation);
        } finally {
            $zip->close();
        }

        return response()
            ->download(
                $temporaryPath,
                $this->filename($presentation, 'pptx'),
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'Cache-Control' => 'private, no-store, max-age=0',
                ]
            )
            ->deleteFileAfterSend(true);
    }

    private function pdfSlides(Presentation $presentation): array
    {
        $assets = $presentation->assets->keyBy(fn (PresentationAsset $asset) => (string) $asset->id);
        $slides = [];

        foreach (($presentation->canvas_data['frames'] ?? []) as $index => $frame) {
            $frameWidth = max(1, (float) ($frame['width'] ?? 800));
            $frameHeight = max(1, (float) ($frame['height'] ?? 450));
            $elements = [];

            foreach (($frame['elements'] ?? []) as $element) {
                $type = (string) ($element['type'] ?? '');
                if (! in_array($type, ['text', 'image', 'logo', 'youtube', 'link', 'shape', 'line', 'diagram'], true)) {
                    continue;
                }

                $prepared = $element;
                $prepared['left'] = $this->percentage($element['x'] ?? 0, $frameWidth);
                $prepared['top'] = $this->percentage($element['y'] ?? 0, $frameHeight);
                $prepared['widthPercent'] = $this->percentage($element['width'] ?? 100, $frameWidth);
                $prepared['heightPercent'] = $this->percentage($element['height'] ?? 100, $frameHeight);
                $prepared['rotation'] = max(-180, min(180, (float) ($element['rotation'] ?? 0)));

                if (in_array($type, ['image', 'logo'], true)) {
                    $asset = $assets->get((string) ($element['assetId'] ?? ''));
                    $prepared['dataUrl'] = $asset ? $this->assetDataUrl($asset) : null;
                }

                $elements[] = $prepared;
            }

            $slides[] = [
                'number' => $index + 1,
                'title' => (string) ($frame['title'] ?? 'Slide '.($index + 1)),
                'backgroundColor' => $this->safeColor($frame['backgroundColor'] ?? null, '#ffffff'),
                'elements' => $elements,
            ];
        }

        return $slides;
    }

    private function writePptxPackage(ZipArchive $zip, Presentation $presentation): void
    {
        $frames = array_values($presentation->canvas_data['frames'] ?? []);
        if ($frames === []) {
            throw new RuntimeException('Presentasi belum memiliki frame.');
        }

        $media = $this->pptxMedia($presentation);

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml(count($frames), $media));
        $zip->addFromString('_rels/.rels', $this->rootRelationshipsXml());
        $zip->addFromString('docProps/app.xml', $this->appPropertiesXml(count($frames)));
        $zip->addFromString('docProps/core.xml', $this->corePropertiesXml($presentation));
        $zip->addFromString('ppt/presentation.xml', $this->presentationXml(count($frames)));
        $zip->addFromString('ppt/_rels/presentation.xml.rels', $this->presentationRelationshipsXml(count($frames)));
        $zip->addFromString('ppt/slideMasters/slideMaster1.xml', $this->slideMasterXml());
        $zip->addFromString('ppt/slideMasters/_rels/slideMaster1.xml.rels', $this->slideMasterRelationshipsXml());
        $zip->addFromString('ppt/slideLayouts/slideLayout1.xml', $this->slideLayoutXml());
        $zip->addFromString('ppt/slideLayouts/_rels/slideLayout1.xml.rels', $this->slideLayoutRelationshipsXml());
        $zip->addFromString('ppt/theme/theme1.xml', $this->themeXml());

        foreach ($media as $item) {
            $zip->addFromString('ppt/media/'.$item['filename'], $item['bytes']);
        }

        foreach ($frames as $index => $frame) {
            [$slideXml, $relationshipsXml] = $this->slideXml($frame, $index + 1, $media);
            $zip->addFromString('ppt/slides/slide'.($index + 1).'.xml', $slideXml);

            if ($relationshipsXml !== null) {
                $zip->addFromString(
                    'ppt/slides/_rels/slide'.($index + 1).'.xml.rels',
                    $relationshipsXml
                );
            }
        }
    }

    private function pptxMedia(Presentation $presentation): array
    {
        $media = [];
        $sequence = 1;

        foreach ($presentation->assets as $asset) {
            $path = Storage::disk('public')->path($asset->path);
            if (! is_file($path)) {
                continue;
            }

            $bytes = file_get_contents($path);
            if ($bytes === false) {
                continue;
            }

            $extension = match (strtolower(pathinfo($asset->path, PATHINFO_EXTENSION))) {
                'jpg', 'jpeg' => 'jpeg',
                'png' => 'png',
                'webp' => 'webp',
                default => null,
            };

            if ($extension === null) {
                continue;
            }

            if ($extension === 'webp') {
                [$bytes, $extension] = $this->convertWebpForPowerPoint($bytes);
            }

            $media[(string) $asset->id] = [
                'filename' => 'image'.$sequence.'.'.$extension,
                'extension' => $extension,
                'contentType' => $extension === 'jpeg' ? 'image/jpeg' : 'image/png',
                'bytes' => $bytes,
            ];
            $sequence++;
        }

        return $media;
    }

    private function convertWebpForPowerPoint(string $bytes): array
    {
        if (! function_exists('imagecreatefromstring')) {
            throw new RuntimeException('Gambar WebP belum dapat diekspor ke PowerPoint pada server ini.');
        }

        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            throw new RuntimeException('Salah satu gambar WebP tidak dapat dibaca.');
        }

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        if (! is_string($png)) {
            throw new RuntimeException('Gambar WebP tidak dapat dikonversi.');
        }

        return [$png, 'png'];
    }

    private function slideXml(array $frame, int $slideNumber, array $media): array
    {
        $frameWidth = max(1, (float) ($frame['width'] ?? 800));
        $frameHeight = max(1, (float) ($frame['height'] ?? 450));
        $shapeId = 2;
        $shapes = '';
        $relationships = [[
            'id' => 'rId1',
            'type' => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout',
            'target' => '../slideLayouts/slideLayout1.xml',
        ]];

        foreach (($frame['elements'] ?? []) as $element) {
            $type = (string) ($element['type'] ?? '');
            $geometry = $this->elementGeometry($element, $frameWidth, $frameHeight);

            if (in_array($type, ['text', 'shape', 'link', 'youtube'], true)) {
                if ($type === 'youtube') {
                    $element['text'] = "Video YouTube\n".($element['youtubeUrl'] ?? '');
                    $element['fontSize'] = 24;
                    $element['align'] = 'center';
                    $element['bold'] = true;
                    $element['shapeType'] = 'rounded';
                } elseif ($type === 'link') {
                    $element['text'] = ($element['text'] ?? 'Buka tautan')."\n".($element['url'] ?? '');
                    $element['fontSize'] = 22;
                    $element['align'] = 'center';
                    $element['bold'] = true;
                    $element['shapeType'] = 'rounded';
                }
                $shapes .= $this->textShapeXml($shapeId++, $element, $geometry);
            } elseif (in_array($type, ['image', 'logo'], true)) {
                $assetId = (string) ($element['assetId'] ?? '');
                if (! isset($media[$assetId])) {
                    continue;
                }

                $relationshipId = 'rId'.(count($relationships) + 1);
                $relationships[] = [
                    'id' => $relationshipId,
                    'type' => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image',
                    'target' => '../media/'.$media[$assetId]['filename'],
                ];
                $shapes .= $this->pictureShapeXml($shapeId++, $element, $geometry, $relationshipId);
            } elseif ($type === 'diagram') {
                [$diagramXml, $shapeId] = $this->diagramShapesXml($shapeId, $element, $geometry);
                $shapes .= $diagramXml;
            } elseif ($type === 'line') {
                $shapes .= $this->lineShapeXml($shapeId++, $element, $geometry);
            }
        }

        $background = $this->pptColor($frame['backgroundColor'] ?? '#ffffff', 'FFFFFF');
        $slideName = $this->xml((string) ($frame['title'] ?? 'Slide '.$slideNumber));
        $slideXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" '
            .'xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">'
            .'<p:cSld name="'.$slideName.'">'
            .'<p:bg><p:bgPr><a:solidFill><a:srgbClr val="'.$background.'"/></a:solidFill><a:effectLst/></p:bgPr></p:bg>'
            .'<p:spTree>'
            .'<p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>'
            .'<p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>'
            .$shapes
            .'</p:spTree></p:cSld>'
            .'<p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr>'
            .'</p:sld>';

        $relationshipXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        foreach ($relationships as $relationship) {
            $relationshipXml .= '<Relationship Id="'.$relationship['id'].'" '
                .'Type="'.$relationship['type'].'" '
                .'Target="'.$this->xml($relationship['target']).'"/>';
        }
        $relationshipXml .= '</Relationships>';

        return [$slideXml, $relationshipXml];
    }

    private function textShapeXml(int $id, array $element, array $geometry): string
    {
        $rotation = (int) round(max(-180, min(180, (float) ($element['rotation'] ?? 0))) * 60000);
        $fontSize = (int) round(max(10, min(160, (float) ($element['fontSize'] ?? 32))) * 75);
        $fontColor = $this->pptColor($element['color'] ?? '#0f172a', '0F172A');
        $background = $this->shapeFillXml($element['backgroundColor'] ?? 'transparent');
        $presetGeometry = match ($element['shapeType'] ?? null) {
            'circle' => 'ellipse',
            'rounded' => 'roundRect',
            'hexagon' => 'hexagon',
            default => 'rect',
        };
        $alignment = match ($element['align'] ?? 'left') {
            'center' => 'ctr',
            'right' => 'r',
            default => 'l',
        };
        $bold = ! empty($element['bold']) ? ' b="1"' : '';
        $paragraphs = '';
        $lines = preg_split('/\R/u', (string) ($element['text'] ?? 'Teks')) ?: [''];

        foreach ($lines as $line) {
            $paragraphs .= '<a:p><a:pPr algn="'.$alignment.'"/>'
                .'<a:r><a:rPr lang="id-ID" sz="'.$fontSize.'"'.$bold.'>'
                .'<a:solidFill><a:srgbClr val="'.$fontColor.'"/></a:solidFill>'
                .'</a:rPr><a:t>'.$this->xml($line).'</a:t></a:r>'
                .'<a:endParaRPr lang="id-ID" sz="'.$fontSize.'"/></a:p>';
        }

        return '<p:sp>'
            .'<p:nvSpPr><p:cNvPr id="'.$id.'" name="Teks '.$id.'"/><p:cNvSpPr txBox="1"/><p:nvPr/></p:nvSpPr>'
            .'<p:spPr><a:xfrm'.($rotation !== 0 ? ' rot="'.$rotation.'"' : '').'>'
            .$this->geometryXml($geometry)
            .'</a:xfrm><a:prstGeom prst="'.$presetGeometry.'"><a:avLst/></a:prstGeom>'
            .$background.'<a:ln><a:noFill/></a:ln></p:spPr>'
            .'<p:txBody><a:bodyPr wrap="square" anchor="ctr"/><a:lstStyle/>'.$paragraphs.'</p:txBody>'
            .'</p:sp>';
    }

    private function pictureShapeXml(int $id, array $element, array $geometry, string $relationshipId): string
    {
        $rotation = (int) round(max(-180, min(180, (float) ($element['rotation'] ?? 0))) * 60000);
        $name = $this->xml((string) ($element['alt'] ?? 'Gambar presentasi'));

        return '<p:pic>'
            .'<p:nvPicPr><p:cNvPr id="'.$id.'" name="'.$name.'"/><p:cNvPicPr><a:picLocks noChangeAspect="1"/></p:cNvPicPr><p:nvPr/></p:nvPicPr>'
            .'<p:blipFill><a:blip r:embed="'.$relationshipId.'"/><a:stretch><a:fillRect/></a:stretch></p:blipFill>'
            .'<p:spPr><a:xfrm'.($rotation !== 0 ? ' rot="'.$rotation.'"' : '').'>'
            .$this->geometryXml($geometry)
            .'</a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:ln><a:noFill/></a:ln></p:spPr>'
            .'</p:pic>';
    }

    private function diagramShapesXml(int $shapeId, array $element, array $geometry): array
    {
        $items = array_values(array_filter(array_map(
            static fn ($item) => trim((string) $item),
            array_slice($element['items'] ?? [], 0, 8)
        )));

        if ($items === []) {
            $items = ['Diagram'];
        }

        $type = (string) ($element['diagramType'] ?? 'process');
        $count = count($items);
        $xml = '';
        $nodeGeometry = [];

        if ($type === 'radial') {
            $centerWidth = (int) round($geometry['cx'] * 0.28);
            $centerHeight = (int) round($geometry['cy'] * 0.3);
            $center = [
                'x' => $geometry['x'] + (int) round(($geometry['cx'] - $centerWidth) / 2),
                'y' => $geometry['y'] + (int) round(($geometry['cy'] - $centerHeight) / 2),
                'cx' => $centerWidth,
                'cy' => $centerHeight,
            ];
            $centerElement = $element;
            $centerElement['text'] = $element['centerText'] ?? 'Logo / Tema';
            $centerElement['fontSize'] = 20;
            $centerElement['align'] = 'center';
            $centerElement['bold'] = true;
            $centerElement['shapeType'] = $element['nodeShape'] ?? 'circle';
            $centerElement['backgroundColor'] = ($element['backgroundColor'] ?? 'transparent') === 'transparent'
                ? '#ffffff'
                : $element['backgroundColor'];
            $xml .= $this->textShapeXml($shapeId++, $centerElement, $center);

            foreach ($items as $index => $item) {
                $angle = ((M_PI * 2) / $count) * $index - (M_PI / 2);
                $nodeWidth = (int) round($geometry['cx'] * 0.24);
                $nodeHeight = (int) round($geometry['cy'] * 0.22);
                $node = [
                    'x' => $geometry['x'] + (int) round(($geometry['cx'] * (0.5 + cos($angle) * 0.38)) - ($nodeWidth / 2)),
                    'y' => $geometry['y'] + (int) round(($geometry['cy'] * (0.5 + sin($angle) * 0.36)) - ($nodeHeight / 2)),
                    'cx' => $nodeWidth,
                    'cy' => $nodeHeight,
                ];
                $xml .= $this->connectorShapeXml($shapeId++, $center, $node, $element['color'] ?? '#475569');
                $nodeElement = $element;
                $nodeElement['text'] = $item;
                $nodeElement['fontSize'] = 15;
                $nodeElement['align'] = 'center';
                $nodeElement['bold'] = true;
                $nodeElement['shapeType'] = $element['nodeShape'] ?? 'circle';
                $nodeElement['backgroundColor'] = ($element['backgroundColor'] ?? 'transparent') === 'transparent'
                    ? '#e2e8f0'
                    : $element['backgroundColor'];
                $xml .= $this->textShapeXml($shapeId++, $nodeElement, $node);
            }

            return [$xml, $shapeId];
        }

        foreach ($items as $index => $item) {
            if ($type === 'hierarchy' && $count > 1) {
                if ($index === 0) {
                    $nodeWidth = (int) round($geometry['cx'] * 0.42);
                    $nodeHeight = (int) round($geometry['cy'] * 0.32);
                    $x = $geometry['x'] + (int) round(($geometry['cx'] - $nodeWidth) / 2);
                    $y = $geometry['y'];
                } else {
                    $children = $count - 1;
                    $nodeWidth = (int) round(($geometry['cx'] - (($children - 1) * 100000)) / $children);
                    $nodeHeight = (int) round($geometry['cy'] * 0.34);
                    $x = $geometry['x'] + (($index - 1) * ($nodeWidth + 100000));
                    $y = $geometry['y'] + (int) round($geometry['cy'] * 0.62);
                }
            } else {
                $gap = min(120000, (int) round($geometry['cx'] * 0.02));
                $nodeWidth = (int) max(100000, round(($geometry['cx'] - (($count - 1) * $gap)) / $count));
                $nodeHeight = (int) round($geometry['cy'] * 0.72);
                $x = $geometry['x'] + ($index * ($nodeWidth + $gap));
                $y = $geometry['y'] + (int) round(($geometry['cy'] - $nodeHeight) / 2);
            }

            $nodeGeometry[] = ['x' => $x, 'y' => $y, 'cx' => $nodeWidth, 'cy' => $nodeHeight];
            $node = $element;
            $node['text'] = $item;
            $node['fontSize'] = max(12, min(28, (float) ($element['fontSize'] ?? 20)));
            $node['align'] = 'center';
            $node['bold'] = true;
            $node['backgroundColor'] = ($element['backgroundColor'] ?? 'transparent') === 'transparent'
                ? '#e2e8f0'
                : $element['backgroundColor'];
            $xml .= $this->textShapeXml($shapeId++, $node, end($nodeGeometry));
        }

        for ($index = 0; $index < $count - 1; $index++) {
            $from = $nodeGeometry[$type === 'hierarchy' ? 0 : $index];
            $to = $nodeGeometry[$index + 1];
            $xml .= $this->connectorShapeXml($shapeId++, $from, $to, $element['color'] ?? '#475569');
        }

        if ($type === 'cycle' && $count > 2) {
            $xml .= $this->connectorShapeXml(
                $shapeId++,
                $nodeGeometry[$count - 1],
                $nodeGeometry[0],
                $element['color'] ?? '#475569'
            );
        }

        return [$xml, $shapeId];
    }

    private function connectorShapeXml(int $id, array $from, array $to, mixed $color): string
    {
        $fromX = $from['x'] + (int) round($from['cx'] / 2);
        $fromY = $from['y'] + (int) round($from['cy'] / 2);
        $toX = $to['x'] + (int) round($to['cx'] / 2);
        $toY = $to['y'] + (int) round($to['cy'] / 2);
        $x = min($fromX, $toX);
        $y = min($fromY, $toY);
        $cx = max(1, abs($toX - $fromX));
        $cy = max(1, abs($toY - $fromY));
        $flipH = $toX < $fromX ? ' flipH="1"' : '';
        $flipV = $toY < $fromY ? ' flipV="1"' : '';

        return '<p:cxnSp>'
            .'<p:nvCxnSpPr><p:cNvPr id="'.$id.'" name="Konektor '.$id.'"/><p:cNvCxnSpPr/><p:nvPr/></p:nvCxnSpPr>'
            .'<p:spPr><a:xfrm'.$flipH.$flipV.'><a:off x="'.$x.'" y="'.$y.'"/><a:ext cx="'.$cx.'" cy="'.$cy.'"/></a:xfrm>'
            .'<a:prstGeom prst="line"><a:avLst/></a:prstGeom><a:ln w="19050">'
            .'<a:solidFill><a:srgbClr val="'.$this->pptColor($color, '475569').'"/></a:solidFill>'
            .'<a:tailEnd type="none"/><a:headEnd type="triangle"/></a:ln></p:spPr>'
            .'</p:cxnSp>';
    }

    private function lineShapeXml(int $id, array $element, array $geometry): string
    {
        $rotation = (int) round(max(-180, min(180, (float) ($element['rotation'] ?? 0))) * 60000);
        $width = (int) round(max(1, min(20, (float) ($element['strokeWidth'] ?? 4))) * 12700);
        $dash = match ($element['lineStyle'] ?? 'solid') {
            'dashed' => '<a:prstDash val="dash"/>',
            'dotted' => '<a:prstDash val="dot"/>',
            default => '<a:prstDash val="solid"/>',
        };
        $arrow = (string) ($element['arrow'] ?? 'none');
        $tail = in_array($arrow, ['start', 'both'], true) ? 'triangle' : 'none';
        $head = in_array($arrow, ['end', 'both'], true) ? 'triangle' : 'none';
        $y = $geometry['y'] + (int) round($geometry['cy'] / 2);

        return '<p:cxnSp>'
            .'<p:nvCxnSpPr><p:cNvPr id="'.$id.'" name="Garis '.$id.'"/><p:cNvCxnSpPr/><p:nvPr/></p:nvCxnSpPr>'
            .'<p:spPr><a:xfrm'.($rotation !== 0 ? ' rot="'.$rotation.'"' : '').'><a:off x="'.$geometry['x'].'" y="'.$y.'"/>'
            .'<a:ext cx="'.$geometry['cx'].'" cy="1"/></a:xfrm><a:prstGeom prst="line"><a:avLst/></a:prstGeom>'
            .'<a:ln w="'.$width.'"><a:solidFill><a:srgbClr val="'.$this->pptColor($element['color'] ?? '#0f172a', '0F172A').'"/></a:solidFill>'
            .$dash.'<a:tailEnd type="'.$tail.'"/><a:headEnd type="'.$head.'"/></a:ln></p:spPr>'
            .'</p:cxnSp>';
    }

    private function elementGeometry(array $element, float $frameWidth, float $frameHeight): array
    {
        return [
            'x' => (int) round(max(0, (float) ($element['x'] ?? 0)) / $frameWidth * self::SLIDE_WIDTH_EMU),
            'y' => (int) round(max(0, (float) ($element['y'] ?? 0)) / $frameHeight * self::SLIDE_HEIGHT_EMU),
            'cx' => (int) round(max(1, (float) ($element['width'] ?? 100)) / $frameWidth * self::SLIDE_WIDTH_EMU),
            'cy' => (int) round(max(1, (float) ($element['height'] ?? 100)) / $frameHeight * self::SLIDE_HEIGHT_EMU),
        ];
    }

    private function geometryXml(array $geometry): string
    {
        return '<a:off x="'.$geometry['x'].'" y="'.$geometry['y'].'"/>'
            .'<a:ext cx="'.$geometry['cx'].'" cy="'.$geometry['cy'].'"/>';
    }

    private function shapeFillXml(mixed $color): string
    {
        if ($color === 'transparent' || ! is_string($color)) {
            return '<a:noFill/>';
        }

        return '<a:solidFill><a:srgbClr val="'.$this->pptColor($color, 'FFFFFF').'"/></a:solidFill>';
    }

    private function contentTypesXml(int $slideCount, array $media): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>';

        foreach (collect($media)->pluck('contentType', 'extension')->unique() as $extension => $contentType) {
            $xml .= '<Default Extension="'.$extension.'" ContentType="'.$contentType.'"/>';
        }

        $xml .= '<Override PartName="/ppt/presentation.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml"/>'
            .'<Override PartName="/ppt/slideMasters/slideMaster1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideMaster+xml"/>'
            .'<Override PartName="/ppt/slideLayouts/slideLayout1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideLayout+xml"/>'
            .'<Override PartName="/ppt/theme/theme1.xml" ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>';

        for ($index = 1; $index <= $slideCount; $index++) {
            $xml .= '<Override PartName="/ppt/slides/slide'.$index.'.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide+xml"/>';
        }

        return $xml.'</Types>';
    }

    private function rootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="ppt/presentation.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            .'</Relationships>';
    }

    private function presentationXml(int $slideCount): string
    {
        $slideIds = '';
        for ($index = 1; $index <= $slideCount; $index++) {
            $slideIds .= '<p:sldId id="'.(255 + $index).'" r:id="rId'.($index + 1).'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<p:presentation xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" '
            .'xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">'
            .'<p:sldMasterIdLst><p:sldMasterId id="2147483648" r:id="rId1"/></p:sldMasterIdLst>'
            .'<p:sldIdLst>'.$slideIds.'</p:sldIdLst>'
            .'<p:sldSz cx="'.self::SLIDE_WIDTH_EMU.'" cy="'.self::SLIDE_HEIGHT_EMU.'" type="screen16x9"/>'
            .'<p:notesSz cx="6858000" cy="9144000"/>'
            .'<p:defaultTextStyle/>'
            .'</p:presentation>';
    }

    private function presentationRelationshipsXml(int $slideCount): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="slideMasters/slideMaster1.xml"/>';

        for ($index = 1; $index <= $slideCount; $index++) {
            $xml .= '<Relationship Id="rId'.($index + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide" Target="slides/slide'.$index.'.xml"/>';
        }

        return $xml.'</Relationships>';
    }

    private function slideMasterXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<p:sldMaster xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" '
            .'xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">'
            .'<p:cSld name="PKG"><p:spTree>'
            .'<p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>'
            .'<p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>'
            .'</p:spTree></p:cSld>'
            .'<p:clrMap accent1="accent1" accent2="accent2" accent3="accent3" accent4="accent4" accent5="accent5" accent6="accent6" bg1="lt1" bg2="lt2" folHlink="folHlink" hlink="hlink" tx1="dk1" tx2="dk2"/>'
            .'<p:sldLayoutIdLst><p:sldLayoutId id="1" r:id="rId1"/></p:sldLayoutIdLst>'
            .'<p:txStyles><p:titleStyle/><p:bodyStyle/><p:otherStyle/></p:txStyles>'
            .'</p:sldMaster>';
    }

    private function slideMasterRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="../theme/theme1.xml"/>'
            .'</Relationships>';
    }

    private function slideLayoutXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<p:sldLayout xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" '
            .'xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" type="blank" preserve="1">'
            .'<p:cSld name="Kosong"><p:spTree>'
            .'<p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>'
            .'<p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>'
            .'</p:spTree></p:cSld><p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr>'
            .'</p:sldLayout>';
    }

    private function slideLayoutRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="../slideMasters/slideMaster1.xml"/>'
            .'</Relationships>';
    }

    private function themeXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="PKG">'
            .'<a:themeElements>'
            .'<a:clrScheme name="PKG"><a:dk1><a:srgbClr val="0F172A"/></a:dk1><a:lt1><a:srgbClr val="FFFFFF"/></a:lt1>'
            .'<a:dk2><a:srgbClr val="1E293B"/></a:dk2><a:lt2><a:srgbClr val="F8FAFC"/></a:lt2>'
            .'<a:accent1><a:srgbClr val="10B981"/></a:accent1><a:accent2><a:srgbClr val="0EA5E9"/></a:accent2>'
            .'<a:accent3><a:srgbClr val="F59E0B"/></a:accent3><a:accent4><a:srgbClr val="8B5CF6"/></a:accent4>'
            .'<a:accent5><a:srgbClr val="EC4899"/></a:accent5><a:accent6><a:srgbClr val="64748B"/></a:accent6>'
            .'<a:hlink><a:srgbClr val="0563C1"/></a:hlink><a:folHlink><a:srgbClr val="954F72"/></a:folHlink></a:clrScheme>'
            .'<a:fontScheme name="PKG"><a:majorFont><a:latin typeface="Aptos Display"/><a:ea typeface=""/><a:cs typeface=""/></a:majorFont>'
            .'<a:minorFont><a:latin typeface="Aptos"/><a:ea typeface=""/><a:cs typeface=""/></a:minorFont></a:fontScheme>'
            .'<a:fmtScheme name="PKG"><a:fillStyleLst>'
            .'<a:solidFill><a:schemeClr val="phClr"/></a:solidFill>'
            .'<a:solidFill><a:schemeClr val="phClr"><a:tint val="50000"/><a:satMod val="300000"/></a:schemeClr></a:solidFill>'
            .'<a:solidFill><a:schemeClr val="phClr"><a:tint val="80000"/><a:satMod val="300000"/></a:schemeClr></a:solidFill>'
            .'</a:fillStyleLst><a:lnStyleLst>'
            .'<a:ln w="6350"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:prstDash val="solid"/></a:ln>'
            .'<a:ln w="12700"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:prstDash val="solid"/></a:ln>'
            .'<a:ln w="19050"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:prstDash val="solid"/></a:ln>'
            .'</a:lnStyleLst><a:effectStyleLst>'
            .'<a:effectStyle><a:effectLst/></a:effectStyle>'
            .'<a:effectStyle><a:effectLst/></a:effectStyle>'
            .'<a:effectStyle><a:effectLst/></a:effectStyle>'
            .'</a:effectStyleLst><a:bgFillStyleLst>'
            .'<a:solidFill><a:schemeClr val="phClr"/></a:solidFill>'
            .'<a:solidFill><a:schemeClr val="phClr"><a:tint val="95000"/><a:satMod val="170000"/></a:schemeClr></a:solidFill>'
            .'<a:solidFill><a:schemeClr val="phClr"><a:shade val="80000"/><a:satMod val="200000"/></a:schemeClr></a:solidFill>'
            .'</a:bgFillStyleLst></a:fmtScheme>'
            .'</a:themeElements><a:objectDefaults/><a:extraClrSchemeLst/></a:theme>';
    }

    private function appPropertiesXml(int $slideCount): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" '
            .'xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            .'<Application>PKG Panunggangan</Application><PresentationFormat>Layar Lebar</PresentationFormat>'
            .'<Slides>'.$slideCount.'</Slides><Notes>0</Notes><HiddenSlides>0</HiddenSlides><MMClips>0</MMClips>'
            .'<ScaleCrop>false</ScaleCrop><Company>PKG Panunggangan</Company><AppVersion>1.0</AppVersion>'
            .'</Properties>';
    }

    private function corePropertiesXml(Presentation $presentation): string
    {
        $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            .'xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" '
            .'xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:title>'.$this->xml($presentation->title).'</dc:title>'
            .'<dc:creator>'.$this->xml($presentation->creator?->name ?? 'PKG Panunggangan').'</dc:creator>'
            .'<cp:lastModifiedBy>PKG Panunggangan</cp:lastModifiedBy>'
            .'<dcterms:created xsi:type="dcterms:W3CDTF">'.$timestamp.'</dcterms:created>'
            .'<dcterms:modified xsi:type="dcterms:W3CDTF">'.$timestamp.'</dcterms:modified>'
            .'</cp:coreProperties>';
    }

    private function assetDataUrl(PresentationAsset $asset): ?string
    {
        $path = Storage::disk('public')->path($asset->path);
        if (! is_file($path)) {
            return null;
        }

        $bytes = file_get_contents($path);
        if ($bytes === false) {
            return null;
        }

        $mime = in_array($asset->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true)
            ? $asset->mime_type
            : 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode($bytes);
    }

    private function percentage(mixed $value, float $total): float
    {
        return round(max(0, (float) $value) / max(1, $total) * 100, 4);
    }

    private function safeColor(mixed $color, string $fallback): string
    {
        return is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color)
            ? strtolower($color)
            : $fallback;
    }

    private function pptColor(mixed $color, string $fallback): string
    {
        return is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color)
            ? strtoupper(substr($color, 1))
            : $fallback;
    }

    private function filename(Presentation $presentation, string $extension): string
    {
        return (Str::slug($presentation->title) ?: 'presentasi').'.'.$extension;
    }

    private function xml(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
