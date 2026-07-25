<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $presentation->title }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: "DejaVu Sans", sans-serif; }
        .slide {
            position: relative;
            width: 960pt;
            height: 540pt;
            overflow: hidden;
            page-break-after: always;
        }
        .slide:last-child { page-break-after: auto; }
        .element {
            position: absolute;
            overflow: hidden;
            transform-origin: center;
        }
        .text {
            padding: 6pt;
            white-space: pre-wrap;
            line-height: 1.15;
        }
        .image img, .logo img {
            display: block;
            width: 100%;
            height: 100%;
        }
        .diagram {
            width: 100%;
            height: 100%;
            border-spacing: 8pt;
            table-layout: fixed;
        }
        .diagram td {
            padding: 8pt;
            text-align: center;
            vertical-align: middle;
            border: 1.5pt solid #64748b;
            border-radius: 6pt;
            font-weight: bold;
        }
        .missing-image {
            width: 100%;
            height: 100%;
            background: #e2e8f0;
            color: #475569;
            text-align: center;
            padding-top: 20pt;
        }
        .youtube, .link, .shape {
            display: table;
            padding: 10pt;
            text-align: center;
            font-weight: bold;
        }
        .youtube > div, .link > div, .shape > div {
            display: table-cell;
            vertical-align: middle;
        }
        .youtube { background: #0f172a !important; color: #fff !important; border-radius: 10pt; }
        .link { border: 1.5pt solid currentColor; border-radius: 8pt; }
        .diagram-radial { position: relative; width: 100%; height: 100%; }
        .diagram-radial-center {
            position: absolute; left: 36%; top: 34%; width: 28%; height: 32%;
            padding: 12pt 6pt; border: 2pt solid currentColor; border-radius: 50%;
            background: #fff; text-align: center; font-weight: bold;
        }
        .diagram-radial-item {
            display: inline-block; width: 23%; margin: 3%; padding: 8pt 4pt;
            border: 1.5pt solid currentColor; border-radius: 18pt;
            background: #e2e8f0; text-align: center; font-weight: bold;
        }
        .export-line {
            display: table;
            width: 100%;
            height: 100%;
            background: transparent !important;
        }
        .export-line-row { display: table-cell; vertical-align: middle; white-space: nowrap; }
        .export-line-segment { display: inline-block; width: 88%; vertical-align: middle; }
    </style>
</head>
<body>
@foreach($slides as $slide)
    <section class="slide" style="background: {{ $slide['backgroundColor'] }};">
        @foreach($slide['elements'] as $element)
            @php
                $background = ($element['backgroundColor'] ?? 'transparent') === 'transparent'
                    ? 'transparent'
                    : $element['backgroundColor'];
                $style = sprintf(
                    'left:%s%%;top:%s%%;width:%s%%;height:%s%%;transform:rotate(%sdeg);background:%s;color:%s;',
                    $element['left'],
                    $element['top'],
                    $element['widthPercent'],
                    $element['heightPercent'],
                    $element['rotation'],
                    $background,
                    $element['color'] ?? '#0f172a'
                );
            @endphp

            @if($element['type'] === 'text')
                <div
                    class="element text"
                    style="{{ $style }}font-size:{{ max(10, min(160, (float) ($element['fontSize'] ?? 32))) * 0.75 }}pt;text-align:{{ $element['align'] ?? 'left' }};font-weight:{{ !empty($element['bold']) ? 'bold' : 'normal' }};"
                >{{ $element['text'] ?? '' }}</div>
            @elseif(in_array($element['type'], ['image', 'logo'], true))
                <div class="element {{ $element['type'] }}" style="{{ $style }}border-radius:{{ $element['type'] === 'logo' && ($element['shape'] ?? 'circle') === 'circle' ? '50%' : '8pt' }};">
                    @if($element['dataUrl'])
                        <img
                            src="{{ $element['dataUrl'] }}"
                            alt="{{ $element['alt'] ?? 'Gambar presentasi' }}"
                            style="object-fit:{{ ($element['fit'] ?? 'cover') === 'contain' ? 'contain' : 'cover' }};"
                        >
                    @else
                        <div class="missing-image">Gambar tidak tersedia</div>
                    @endif
                </div>
            @elseif($element['type'] === 'youtube')
                <div class="element youtube" style="{{ $style }}"><div>Video YouTube<br><span style="font-size:10pt;font-weight:normal;">{{ $element['youtubeUrl'] ?? '' }}</span></div></div>
            @elseif($element['type'] === 'link')
                <div class="element link" style="{{ $style }}"><div>{{ $element['text'] ?? 'Buka tautan' }}<br><span style="font-size:9pt;font-weight:normal;">{{ $element['url'] ?? '' }}</span></div></div>
            @elseif($element['type'] === 'shape')
                <div class="element shape" style="{{ $style }}border-radius:{{ ($element['shapeType'] ?? '') === 'circle' ? '50%' : (($element['borderRadius'] ?? 24) * 0.75).'pt' }};font-size:{{ max(10, min(160, (float) ($element['fontSize'] ?? 28))) * 0.75 }}pt;"><div>{{ $element['text'] ?? '' }}</div></div>
            @elseif($element['type'] === 'line')
                @php
                    $lineBorderStyle = match($element['lineStyle'] ?? 'solid') {
                        'dashed' => 'dashed',
                        'dotted' => 'dotted',
                        default => 'solid',
                    };
                    $lineArrow = $element['arrow'] ?? 'none';
                @endphp
                <div class="element export-line" style="{{ $style }}">
                    <div class="export-line-row">
                        {{ in_array($lineArrow, ['start', 'both'], true) ? '◀' : '' }}<span class="export-line-segment" style="border-top:{{ max(1, min(20, (float) ($element['strokeWidth'] ?? 4))) * 0.75 }}pt {{ $lineBorderStyle }} {{ $element['color'] ?? '#0f172a' }};"></span>{{ in_array($lineArrow, ['end', 'both'], true) ? '▶' : '' }}
                    </div>
                </div>
            @elseif($element['type'] === 'diagram' && ($element['diagramType'] ?? '') === 'radial')
                <div class="element diagram-radial" style="{{ $style }}">
                    <div class="diagram-radial-center">{{ $element['centerText'] ?? 'Logo / Tema' }}</div>
                    @foreach(array_slice($element['items'] ?? [], 0, 8) as $item)
                        <span class="diagram-radial-item">{{ $item }}</span>
                    @endforeach
                </div>
            @elseif($element['type'] === 'diagram')
                <div class="element" style="{{ $style }}">
                    <table class="diagram">
                        <tr>
                            @forelse(array_slice($element['items'] ?? [], 0, 8) as $item)
                                <td style="background:{{ $background === 'transparent' ? '#e2e8f0' : $background }};color:{{ $element['color'] ?? '#0f172a' }};">{{ $item }}</td>
                            @empty
                                <td>Diagram</td>
                            @endforelse
                        </tr>
                    </table>
                </div>
            @endif
        @endforeach
    </section>
@endforeach
</body>
</html>
