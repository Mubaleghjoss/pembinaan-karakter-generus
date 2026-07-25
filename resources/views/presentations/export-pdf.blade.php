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
        .image img {
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
            @elseif($element['type'] === 'image')
                <div class="element image" style="{{ $style }}">
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
