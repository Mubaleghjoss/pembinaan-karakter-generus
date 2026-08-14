@php
    $sheet = $page['sheet'];
    $siswa = $page['siswa'];
    $last = $sheet->last_position;
    $maskedNis = str_repeat('*', max(0, strlen((string) $siswa->nis) - 4)).substr((string) $siswa->nis, -4);
@endphp
<section class="page">
    <span class="corner tl"></span><span class="corner tr"></span><span class="corner bl"></span><span class="corner br"></span>
    <table class="letterhead">
        <tr>
            <td style="width:22mm">@if($logoDataUri)<img class="brand-logo" src="{{ $logoDataUri }}" alt="Logo PKG">@endif</td>
            <td>
                <div class="brand-name">Pembinaan Karakter Generus</div>
                <div class="brand-subtitle">Lembar Bacaan Al-Qur'an Bulanan &middot; Gunakan tinta gelap dan tulis angka dengan jelas</div>
                <div class="verse" lang="ar">وَرَتِّلِ الْقُرْاٰنَ تَرْتِيْلًاۗ</div>
                <div class="translation">Surah Al-Muzzammil ayat 4: “Dan bacalah Al-Qur'an itu dengan perlahan-lahan (tartil).”</div>
                <div class="meaning"><strong>Makna:</strong> Ayat ini adalah perintah langsung dari Allah untuk membaca Al-Qur'an dengan tidak terburu-buru agar bisa meresapi maknanya.</div>
            </td>
            <td style="width:41mm"><div class="qr-wrap"><img class="qr" src="{{ $page['qrDataUri'] }}" alt="QR lembar bacaan bulanan"></div></td>
        </tr>
    </table>
    <table class="info">
        <tr>
            <td style="width:10%"><strong>Nama Generus</strong></td><td style="width:24%">{{ $siswa->nama }} &middot; NIS {{ $maskedNis }}</td>
            <td style="width:8%"><strong>Pamong</strong></td><td style="width:25%">{{ $page['pamongNames'] }}</td>
            <td style="width:9%"><strong>Kelompok</strong></td><td style="width:14%">{{ $siswa->kelompok_label ?? $siswa->kelompok ?? '-' }}</td>
            <td style="width:10%"><strong>Bulan/Tahun</strong><br>........ / ........</td>
        </tr>
        <tr><td><strong>Posisi terakhir</strong></td><td colspan="6">@if($last)Halaman {{ $last['page_end'] }} &middot; {{ $catalog::name((int) $last['surah_end']) }} ayat {{ $last['ayah_end'] }} &middot; {{ $last['reading_date'] }}@else Belum ada bacaan terverifikasi @endif</td></tr>
    </table>
    <table class="rows">
        <thead><tr><th class="no">No.</th><th style="width:9%">Tanggal</th><th style="width:7%">Hal.<br>awal</th><th style="width:7%">Hal.<br>akhir</th><th style="width:9%">No. surat<br>awal</th><th style="width:8%">Ayat<br>awal</th><th style="width:9%">No. surat<br>akhir</th><th style="width:8%">Ayat<br>akhir</th><th>Catatan/Paraf</th></tr></thead>
        <tbody>@for($i = 1; $i <= $sheet->row_count; $i++)<tr><td class="no">{{ $i }}</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>@endfor</tbody>
    </table>
    <div class="note"><strong>Cara mengisi:</strong> tanggal DD/MM/YYYY; nomor surat 1&ndash;114; satu rangkaian bacaan per baris. Scan sisi ini melalui menu Scan Lembar dan periksa kembali seluruh hasil sebelum disimpan.</div>
    <div class="token">ID lembar: {{ $sheet->public_id }} &middot; Template bulanan v{{ $sheet->template_version }} &middot; Dibuat {{ $sheet->created_at->format('d/m/Y H:i') }} WIB</div>
</section>
