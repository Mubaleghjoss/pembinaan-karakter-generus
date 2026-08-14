<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dokumen Bacaan Al-Qur'an</title>
    <style>
        @page { size: A4 landscape; margin: 7mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 7px; }
        .page { position: relative; height: 194mm; overflow: hidden; page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .corner { position: absolute; width: 17px; height: 17px; border-color: #111827; border-style: solid; }
        .tl { top: 0; left: 0; border-width: 4px 0 0 4px; }
        .tr { top: 0; right: 0; border-width: 4px 4px 0 0; }
        .bl { bottom: 0; left: 0; border-width: 0 0 4px 4px; }
        .br { bottom: 0; right: 0; border-width: 0 4px 4px 0; }
        .letterhead { width: 100%; border-bottom: 2.5px solid #087f5b; border-collapse: collapse; }
        .letterhead td { vertical-align: middle; padding: 2px 5px 5px; }
        .brand-logo { width: 17mm; height: 17mm; object-fit: contain; }
        .brand-name { font-size: 15px; font-weight: 700; letter-spacing: .2px; }
        .brand-subtitle { margin-top: 2px; font-size: 7px; color: #475569; }
        .verse { margin-top: 3px; font-size: 13px; font-weight: 700; direction: rtl; text-align: left; }
        .translation { margin-top: 1px; font-size: 6.5px; font-style: italic; }
        .meaning { margin-top: 1px; max-width: 205mm; color: #475569; font-size: 5.8px; line-height: 1.35; }
        .qr-wrap { width: 38mm; height: 38mm; padding: 1mm; background: #fff; }
        .qr { display: block; width: 36mm; height: 36mm; }
        .info { width: 100%; margin: 3px 0; border-collapse: collapse; table-layout: fixed; }
        .info td { border: 1px solid #94a3b8; padding: 2px 4px; height: 17px; }
        .info strong { font-size: 6.5px; }
        .rows { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .rows th, .rows td { border: 1.15px solid #334155; padding: 0 2px; }
        .rows th { height: 20px; background: #e9f7f1; font-size: 6px; line-height: 1.15; }
        .rows td { height: 14px; }
        .rows .no { width: 3.5%; text-align: center; font-weight: 700; }
        .note { margin-top: 3px; color: #475569; font-size: 5.7px; line-height: 1.3; }
        .token { margin-top: 1px; color: #64748b; font-family: monospace; font-size: 5.2px; }
        .reference-head { width: 100%; border-bottom: 2.5px solid #087f5b; border-collapse: collapse; }
        .reference-head td { vertical-align: middle; padding: 3px 6px 5px; }
        .reference-title { font-size: 17px; font-weight: 700; }
        .reference-copy { margin-top: 2px; color: #475569; font-size: 7px; }
        .map { width: 100%; border-collapse: separate; border-spacing: 4px 0; table-layout: fixed; }
        .column { width: 33.333%; vertical-align: top; }
        .surah { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .surah th, .surah td { height: 10.5px; border: 1px solid #475569; padding: 1px 2px; }
        .surah th { background: #e9f7f1; font-size: 6.2px; }
        .surah .map-no { width: 9%; text-align: center; }
        .surah .ayah { width: 14%; text-align: center; }
        .surah .mark { width: 14%; text-align: center; }
        .check-box { display: inline-block; width: 7px; height: 7px; border: 1.4px solid #111827; vertical-align: middle; }
        .print-guide { margin-top: 3px; color: #475569; font-size: 6px; }
    </style>
</head>
<body>
@foreach($pages as $page)
    @if($page['type'] === 'monthly')
        @include('quran-reading.pdf.partials.monthly-page', ['page' => $page])
    @else
        @include('quran-reading.pdf.partials.surah-reference-page', ['page' => $page])
    @endif
@endforeach
</body>
</html>
