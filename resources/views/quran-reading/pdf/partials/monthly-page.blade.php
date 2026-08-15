@php
    $isBlank = (bool) ($page['blank'] ?? false);
    $sheet = $page['sheet'] ?? null;
    $siswa = $page['siswa'] ?? null;
    $last = $sheet?->last_position;
    $maskedNis = $siswa ? str_repeat('*', max(0, strlen((string) $siswa->nis) - 4)).substr((string) $siswa->nis, -4) : null;
    $rowCount = (int) ($page['rowCount'] ?? $sheet?->row_count ?? 31);
@endphp
<section class="page">
    <span class="corner tl"></span><span class="corner tr"></span><span class="corner bl"></span><span class="corner br"></span>
    <table class="letterhead">
        <tr>
            <td style="width:25mm">@if($logoDataUri)<img class="brand-logo" src="{{ $logoDataUri }}" alt="Logo PKG">@endif</td>
            <td>
                <div class="organization-name">Pembinaan Karakter Generus</div>
                <div class="document-title">Lembar Bacaan Al-Qur'an Bulanan</div>
                <div class="brand-subtitle">Gunakan tinta gelap dan tulis angka dengan jelas</div>
                <div class="verse-wrap" lang="ar" dir="rtl">
                    @if($verseImageDataUri ?? null)
                        <img class="verse-image" src="{{ $verseImageDataUri }}" alt="وَرَتِّلِ الْقُرْاٰنَ تَرْتِيْلًاۗ">
                    @else
                        <div class="verse-fallback">وَرَتِّلِ الْقُرْاٰنَ تَرْتِيْلًاۗ</div>
                    @endif
                </div>
                <div class="translation">Surah Al-Muzzammil ayat 4: “Dan bacalah Al-Qur'an itu dengan perlahan-lahan (tartil).”</div>
                <div class="meaning"><strong>Makna:</strong> Ayat ini adalah perintah langsung dari Allah untuk membaca Al-Qur'an dengan tidak terburu-buru agar bisa meresapi maknanya.</div>
            </td>
            <td style="width:41mm">
                @if($isBlank)
                    <div class="manual-document-mark">DOKUMEN MANUAL<br><span>Tanpa QR</span></div>
                @else
                    <div class="qr-wrap"><img class="qr" src="{{ $page['qrDataUri'] }}" alt="QR lembar bacaan bulanan"></div>
                @endif
            </td>
        </tr>
    </table>
    <table class="info">
        <tr>
            <td style="width:8%"><span class="info-label">Nama</span></td><td style="width:27%"><span class="student-name">{{ $isBlank ? '........................................................' : $siswa->nama }}</span>@unless($isBlank)<span class="identity-meta">NIS {{ $maskedNis }}</span>@endunless</td>
            <td style="width:7%"><span class="info-label">Pamong</span></td><td style="width:25%"><span class="info-value">{{ $isBlank ? '........................................................' : $page['pamongNames'] }}</span></td>
            <td style="width:8%"><span class="info-label">Kelompok</span></td><td style="width:15%"><span class="info-value">{{ $isBlank ? '........................' : ($siswa->kelompok_label ?? $siswa->kelompok ?? '-') }}</span></td>
            <td style="width:10%"><span class="info-label">Bulan/Tahun</span><br>........ / ........</td>
        </tr>
        <tr><td><span class="info-label">Posisi terakhir</span></td><td colspan="6"><span class="info-value">@if($isBlank)................................................................................................................................................................................@elseif($last){{ $last['page_end'] ? 'Halaman '.$last['page_end'].' · ' : 'Halaman tidak dicatat · ' }}{{ $catalog::name((int) $last['surah_end']) }} ayat {{ $last['ayah_end'] }} &middot; {{ $last['reading_date'] }}@else Belum ada bacaan terverifikasi @endif</span></td></tr>
    </table>
    <table class="rows">
        <thead><tr><th class="no">No.</th><th style="width:9%">Tanggal</th><th style="width:7%">Hal.<br>awal</th><th style="width:7%">Hal.<br>akhir</th><th style="width:9%">No. surat<br>awal</th><th style="width:8%">Ayat<br>awal</th><th style="width:9%">No. surat<br>akhir</th><th style="width:8%">Ayat<br>akhir</th><th>Catatan/Paraf</th></tr></thead>
        <tbody>@for($i = 1; $i <= $rowCount; $i++)<tr><td class="no">{{ $i }}</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>@endfor</tbody>
    </table>
    @if($isBlank)
        <div class="note"><strong>Untuk pencatatan manual — tidak dipindai.</strong> Isi tanggal DD/MM/YYYY, nomor surat 1&ndash;114, dan satu rangkaian bacaan per baris.</div>
    @else
        <div class="note"><strong>Cara mengisi:</strong> tanggal DD/MM/YYYY; nomor surat 1&ndash;114; satu rangkaian bacaan per baris. Scan sisi ini melalui menu Scan Lembar dan periksa kembali seluruh hasil sebelum disimpan.</div>
        <div class="token">ID lembar: {{ $sheet->public_id }} &middot; Template bulanan v{{ $sheet->template_version }} &middot; Dibuat {{ $sheet->created_at->format('d/m/Y H:i') }} WIB</div>
    @endif
</section>
