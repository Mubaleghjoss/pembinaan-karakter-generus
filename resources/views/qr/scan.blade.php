@extends('layouts.app')

@section('title', 'Scan QR Code - PKG Presensi')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumb -->
    <div class="flex items-center space-x-2 text-sm text-gray-500 mb-8">
        <a href="{{ route('dashboard') }}" class="hover:text-blue-600">Dashboard</a>
        <span>/</span>
        <span class="text-gray-900 font-medium">Scan QR Code</span>
    </div>

    <div class="pkg-page-header items-center text-center mb-8">
        <div>
            <h1 class="pkg-page-heading text-3xl">Scan QR Code</h1>
            <p class="pkg-page-subheading mt-2">
                Scan QR code siswa untuk melakukan presensi kehadiran.
            </p>
        </div>
    </div>

    <!-- React Scanner Component -->
    <div id="qr-scanner"></div>

</div>
@endsection
