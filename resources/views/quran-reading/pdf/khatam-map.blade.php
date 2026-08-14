<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><title>Peta Khatam Al-Qur'an</title><style>
@page{margin:7mm}body{font-family:DejaVu Sans,sans-serif;color:#111827;font-size:7px;margin:0}.page{position:relative;height:194mm;page-break-after:always}.page:last-child{page-break-after:auto}.corner{position:absolute;width:18px;height:18px;border-color:#111;border-style:solid}.tl{top:0;left:0;border-width:5px 0 0 5px}.tr{top:0;right:0;border-width:5px 5px 0 0}.bl{bottom:0;left:0;border-width:0 0 5px 5px}.br{bottom:0;right:0;border-width:0 5px 5px 0}.head{width:100%;border-bottom:2.5px solid #087f5b}.head td{vertical-align:middle;padding:2px 5px}.title{font-size:17px;font-weight:bold}.qr-wrap{width:42mm;height:42mm;padding:3mm;background:#fff}.qr{display:block;width:36mm;height:36mm}.info{width:100%;margin:3px 0 4px;border-collapse:collapse}.info td{padding:2px 5px;border:1px solid #94a3b8}.map{width:100%;border-collapse:separate;border-spacing:4px 0;table-layout:fixed}.column{width:33.333%;vertical-align:top}.surah{width:100%;border-collapse:collapse;table-layout:fixed}.surah th,.surah td{border:1px solid #475569;padding:1px 2px;height:10px}.surah th{background:#e9f7f1;font-size:6.5px}.no{width:8%;text-align:center}.ayat{width:12%;text-align:center}.mark{width:14%;text-align:center}.omr{display:inline-block;width:7px;height:7px;border:1.7px solid #111;border-radius:50%;vertical-align:middle}.omr.done{background:#111}.footer{margin-top:3px;color:#475569;font-size:6.5px}.token{font-family:monospace;color:#64748b;font-size:6px}
</style></head><body>
@foreach($pages as $page)
@php($sheet = $page['sheet']) @php($siswa = $page['siswa']) @php($summary = $page['summary']) @php($cycle = $page['cycle'])
@php($completed = collect($summary['completed_surahs'] ?? []))
@php($maskedNis = str_repeat('*', max(0, strlen((string)$siswa->nis)-4)).substr((string)$siswa->nis, -4))
<section class="page"><span class="corner tl"></span><span class="corner tr"></span><span class="corner bl"></span><span class="corner br"></span>
<table class="head"><tr><td><div class="title">Peta Khatam Al-Qur'an</div><div>PKG Desa Panunggangan &middot; Tandai lingkaran setelah satu surat selesai dibaca</div></td><td width="165"><div class="qr-wrap"><img class="qr" src="{{ $page['qrDataUri'] }}" alt="QR Peta Khatam"></div></td></tr></table>
<table class="info"><tr><td><strong>Nama</strong> {{ $siswa->nama }}</td><td><strong>NIS</strong> {{ $maskedNis }}</td><td><strong>Kelas/Kelompok</strong> {{ $siswa->kelas?->nama ?? '-' }} / {{ $siswa->kelompok_label ?? $siswa->kelompok ?? '-' }}</td><td><strong>Siklus</strong> {{ $cycle->cycle_number }}</td></tr><tr><td><strong>Surat aktif</strong> {{ $summary['active_surah'] ? $summary['active_surah'].' - '.$catalog::name((int)$summary['active_surah']) : '........' }}</td><td><strong>Ayat terakhir</strong> {{ $summary['active_ayah'] ?: '........' }}</td><td><strong>Tanggal pembaruan</strong> .... / .... / ........</td><td><strong>Selesai</strong> {{ $summary['completed_count'] ?? 0 }}/114</td></tr></table>
<table class="map"><tr>
@for($column=0;$column<3;$column++)
<td class="column"><table class="surah"><thead><tr><th class="no">No.</th><th>Nama surat</th><th class="ayat">Ayat</th><th class="mark">Selesai</th></tr></thead><tbody>
@for($row=1;$row<=38;$row++) @php($number = $column * 38 + $row)
<tr><td class="no">{{ $number }}</td><td>{{ $catalog::name($number) }}</td><td class="ayat">{{ $catalog::ayahCount($number) }}</td><td class="mark"><span class="omr {{ $completed->contains($number) ? 'done' : '' }}"></span></td></tr>
@endfor
</tbody></table></td>
@endfor
</tr></table>
<div class="footer"><strong>Cara mengisi:</strong> hitamkan lingkaran hanya setelah surat selesai. Tulis satu surat aktif dan ayat terakhir pada kotak atas. Scan tidak pernah membatalkan progres yang sudah terverifikasi.</div>
<div class="token">ID lembar: {{ $sheet->public_id }} &middot; PKGQM v{{ $sheet->template_version }} &middot; Baseline {{ $summary['completed_count'] ?? 0 }} surat &middot; Dibuat {{ $sheet->created_at->format('d/m/Y H:i') }} WIB</div>
</section>
@endforeach
</body></html>
