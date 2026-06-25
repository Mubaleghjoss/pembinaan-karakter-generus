<!DOCTYPE html>
<html lang="id">
<head>
    @php
        $cardBaseColor = $cardSettings['card_color'] ?? '#0f766e';
        $cardFooterText = $cardSettings['card_footer_text'] ?? 'Kartu ini adalah identitas resmi peserta PKG Panunggangan';
        $rawCardTitle = trim($cardSettings['card_title'] ?? 'KARTU IDENTITAS');
        $displayCardTitle = strcasecmp($rawCardTitle, 'KARTU PESERTA') === 0 ? 'KARTU IDENTITAS' : $rawCardTitle;
        $roleLabel = $user->operationalRoleLabel();
        $orgLabel = $user->organizationalLabel();
    @endphp
    @include('components.id-card-print-single-styles', ['labelWidth' => '10mm'])
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ID Card - {{ $user->display_name }}</title>
</head>
<body onload="setTimeout(() => window.print(), 250)">
    <div class="id-card">
        <div class="card-header">
            <div class="card-logo">
                @if(!empty($cardSettings['card_logo']))
                    <img src="{{ Storage::url($cardSettings['card_logo']) }}" alt="Logo">
                @elseif(!empty($siteSettings['site_logo']))
                    <img src="{{ asset('storage/' . $siteSettings['site_logo']) }}" alt="Logo">
                @else
                    {{ substr($siteSettings['site_title'] ?? 'PKG', 0, 3) }}
                @endif
            </div>
            <div class="card-title">
                <h3>{{ $displayCardTitle }}</h3>
                <p>{{ $cardSettings['card_subtitle'] ?? ($siteSettings['site_name'] ?? 'Pembinaan Karakter Generus') }}</p>
            </div>
        </div>

        <div class="card-body">
            <div class="left-section">
                <div class="person-photo">
                    @if($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->display_name }}">
                    @else
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                    @endif
                </div>

                <div class="person-info">
                    <div class="person-name">{{ $user->display_name }}</div>
                    <div class="info-row">
                        <span class="info-label">User</span>
                        <span class="info-value">: {{ $user->username }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Role</span>
                        <span class="info-value">: {{ $roleLabel }}</span>
                    </div>
                    @if($orgLabel)
                        <div class="info-row">
                            <span class="info-label">Bidang</span>
                            <span class="info-value">: {{ Str::limit($orgLabel, 24) }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="qr-section">
                <img src="{{ $qrData['qr_image_base64'] }}" alt="QR Code">
            </div>
        </div>

        <div class="card-footer">
            {{ $cardFooterText }}
        </div>
    </div>
</body>
</html>
