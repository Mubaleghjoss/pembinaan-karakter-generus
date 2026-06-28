@extends('layouts.public')

@section('title', 'Kalender Aktivitas - ' . ($theme->app_name ?? 'PKG Presensi'))
@section('og_title', 'Kalender Aktivitas')
@section('og_description', 'Kalender jadwal PKG, RPP materi, presensi, dan tenggat tugas.')

@section('content')
    @include('public.partials.calendar-widget', [
        'calendarId' => 'calendar',
        'calendarTitle' => 'Kalender Aktivitas',
        'calendarSubtitle' => 'Agenda PKG yang bisa dilihat siswa, orang tua, dan pengunjung.',
        'calendarSectionClass' => 'bg-slate-50 py-10 dark:bg-slate-950',
        'calendarInitialDate' => sprintf('%04d-%02d-01', (int) $year, (int) $month),
    ])
@endsection
