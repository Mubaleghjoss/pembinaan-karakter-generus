@php
    $siswa = $page['siswa'];
    $maskedNis = str_repeat('*', max(0, strlen((string) $siswa->nis) - 4)).substr((string) $siswa->nis, -4);
@endphp
<section class="page">
    <span class="corner tl"></span><span class="corner tr"></span><span class="corner bl"></span><span class="corner br"></span>
    <table class="reference-head">
        <tr>
            <td style="width:22mm">@if($logoDataUri)<img class="brand-logo" src="{{ $logoDataUri }}" alt="Logo PKG">@endif</td>
            <td><div class="reference-title">Peta dan Referensi Khatam Al-Qur'an</div><div class="reference-copy">Pembinaan Karakter Generus &middot; 114 surat, nama surat, dan jumlah ayat &middot; Centang manual setelah selesai dibaca</div></td>
        </tr>
    </table>
    <table class="info"><tr><td><strong>Nama Generus</strong> {{ $siswa->nama }}</td><td><strong>NIS</strong> {{ $maskedNis }}</td><td><strong>Pamong</strong> {{ $page['pamongNames'] }}</td><td><strong>Kelompok</strong> {{ $siswa->kelompok_label ?? $siswa->kelompok ?? '-' }}</td></tr></table>
    <table class="map"><tr>
        @for($column = 0; $column < 3; $column++)
            <td class="column"><table class="surah"><thead><tr><th class="map-no">No.</th><th>Nama surat</th><th class="ayah">Jumlah ayat</th><th class="mark">Selesai</th></tr></thead><tbody>
            @for($row = 1; $row <= 38; $row++)
                @php($number = $column * 38 + $row)
                <tr><td class="map-no">{{ $number }}</td><td>{{ $catalog::name($number) }}</td><td class="ayah">{{ $catalog::ayahCount($number) }}</td><td class="mark"><span class="check-box"></span></td></tr>
            @endfor
            </tbody></table></td>
        @endfor
    </tr></table>
    <div class="print-guide"><strong>Petunjuk:</strong> sisi ini adalah referensi dan checklist manual. Pencatatan digital dilakukan dengan memindai sisi Lembar Bacaan Bulanan. Untuk paket dua sisi, cetak landscape, bolak-balik, dan pilih “balik sisi pendek”.</div>
</section>
