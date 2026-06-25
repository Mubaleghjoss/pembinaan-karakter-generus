{{-- Biodata Completion Prompt --}}
{{-- Shows popup if siswa has incomplete biodata --}}

@php
    $popupConfig = \App\Support\PopupManager::config('biodata_prompt');
    $biodataPopupEnabled = $popupConfig['enabled'];
    $biodataPopupRequired = $popupConfig['required'];
@endphp

@if($biodataPopupEnabled && Auth::guard('siswa')->check())
    @php
        $siswa = Auth::guard('siswa')->user();
        $missingFields = [];

        if (empty($siswa->nama)) {
            $missingFields[] = 'Nama lengkap';
        }
        if (empty($siswa->kelompok)) {
            $missingFields[] = 'Kelompok';
        }
        if (empty($siswa->tanggal_lahir)) {
            $missingFields[] = 'Tanggal lahir';
        }
        if (empty($siswa->phone)) {
            $missingFields[] = 'No. HP pribadi';
        }
        if (empty($siswa->phone_wali)) {
            $missingFields[] = 'No. HP wali';
        }
        if (empty($siswa->foto_path)) {
            $missingFields[] = 'Foto profil';
        }

        $dismissedForBrowser = !$biodataPopupRequired && session('biodata_prompt_dismissed');
        $skipOnProfilePage = $biodataPopupRequired && request()->routeIs('siswa.profile');
    @endphp

    @if(count($missingFields) > 0 && !$dismissedForBrowser && !$skipOnProfilePage)
        <div id="biodataPrompt" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.72);z-index:91;align-items:center;justify-content:center;padding:16px;">
            <div style="background:white;border-radius:20px;max-width:430px;width:100%;padding:28px;text-align:center;box-shadow:0 24px 60px rgba(15,23,42,0.28);">
                <div style="width:72px;height:72px;border-radius:9999px;background:linear-gradient(135deg,#3b82f6,#2563eb);display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
                    <svg fill="none" stroke="white" viewBox="0 0 24 24" style="width:34px;height:34px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h2 style="font-size:22px;font-weight:800;color:#0f172a;margin-bottom:10px;">Lengkapi Biodata Kamu</h2>
                <p style="color:#475569;margin-bottom:16px;font-size:14px;line-height:1.7;">
                    Beberapa data profil masih kosong. Lengkapi biodata agar fitur siswa berjalan lebih rapi dan data kamu bisa dipakai saat kegiatan.
                </p>

                @if($biodataPopupRequired)
                    <div style="background:#eff6ff;border:1px solid #93c5fd;border-radius:12px;padding:12px 14px;margin-bottom:18px;text-align:left;color:#1d4ed8;font-size:13px;line-height:1.6;">
                        Pengaturan ini sedang ditandai <strong>wajib</strong>. Popup akan terus muncul sampai biodata yang diminta dilengkapi.
                    </div>
                @endif

                <div style="background:#fff7ed;border-radius:14px;padding:14px 16px;margin-bottom:22px;text-align:left;border:1px solid #fdba74;">
                    <p style="font-size:13px;font-weight:700;color:#9a3412;margin-bottom:8px;">Data yang belum diisi:</p>
                    <ul style="list-style:none;padding:0;margin:0;">
                        @foreach($missingFields as $field)
                            <li style="font-size:13px;color:#9a3412;padding:3px 0;">
                                - {{ $field }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <a href="{{ route('siswa.profile') }}"
                        style="display:flex;align-items:center;justify-content:center;gap:8px;background:linear-gradient(to right,#3b82f6,#1d4ed8);width:100%;padding:13px 16px;color:white;font-weight:600;border-radius:12px;text-decoration:none;font-size:14px;box-shadow:0 10px 25px rgba(37,99,235,0.2);">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:20px;height:20px;flex-shrink:0;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Lengkapi Sekarang
                    </a>

                    @if(!$biodataPopupRequired)
                        <button onclick="dismissBiodataPrompt()"
                            style="width:100%;padding:10px 16px;color:#64748b;font-size:13px;font-weight:600;background:none;border:none;cursor:pointer;margin-top:12px;">
                            Nanti saja
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <script>
        (function() {
            if (!@json($biodataPopupRequired) && sessionStorage.getItem('biodata_prompt_dismissed')) {
                return;
            }

            window.setTimeout(function() {
                var prompt = document.getElementById('biodataPrompt');
                if (prompt) {
                    prompt.style.display = 'flex';
                }
            }, 2500);
        })();

        function dismissBiodataPrompt() {
            document.getElementById('biodataPrompt').style.display = 'none';
            sessionStorage.setItem('biodata_prompt_dismissed', '1');
        }
        </script>
    @endif
@endif
