<?php

namespace App\Services;

use Carbon\Carbon;
use InvalidArgumentException;

class MateriRppPlanner
{
    public function plan(array $input): array
    {
        $totalPages = $this->positiveInt($input['total_pages'] ?? null, 'Total halaman');
        $startPage = max(1, (int) ($input['start_page'] ?? 1));
        $pagesPerSession = $this->positiveInt($input['pages_per_session'] ?? null, 'Target halaman per pertemuan');
        $startDate = Carbon::parse($input['start_date'] ?? throw new InvalidArgumentException('Tanggal mulai wajib diisi.'))->startOfDay();
        $startTime = $this->normalizeTime($input['start_time'] ?? null);
        $endTime = $this->normalizeTime($input['end_time'] ?? null);

        if ($startPage > $totalPages) {
            throw new InvalidArgumentException('Halaman mulai tidak boleh melebihi total halaman.');
        }

        $extraSessions = $this->normalizeExtraSessions($input['extra_sessions'] ?? [], $pagesPerSession, $startDate);
        $catchUpRanges = $this->normalizeCatchUpRanges($input['catch_up_ranges'] ?? [], $pagesPerSession, $startDate);
        $catchUpSlots = $this->expandCatchUpSlots($catchUpRanges);
        $teacherPool = $this->normalizeTeacherPool($input['teacher_pool'] ?? []);
        $weeklyPatterns = [[
            'date' => $startDate->toDateString(),
            'next_date' => $startDate->toDateString(),
            'pages' => $pagesPerSession,
            'type' => 'regular',
            'order' => 0,
            'weekday' => $startDate->dayOfWeekIso,
            'weekday_label' => $this->weekdayLabel($startDate->dayOfWeekIso),
        ]];

        foreach ($extraSessions as $index => $extraSession) {
            $weeklyPatterns[] = [
                'date' => $extraSession['date'],
                'next_date' => $extraSession['date'],
                'pages' => $extraSession['pages'],
                'type' => 'weekly_extra',
                'order' => $index + 1,
                'weekday' => $extraSession['weekday'],
                'weekday_label' => $extraSession['weekday_label'],
            ];
        }

        $sessions = [];
        $currentPage = $startPage;

        while ($currentPage <= $totalPages) {
            usort($weeklyPatterns, fn (array $left, array $right) => [
                $left['next_date'],
                $left['order'],
            ] <=> [
                $right['next_date'],
                $right['order'],
            ]);

            $weeklySlot = $weeklyPatterns[0];
            $catchUpSlot = $catchUpSlots[0] ?? null;

            if ($catchUpSlot && [
                $catchUpSlot['next_date'],
                $catchUpSlot['order'],
            ] < [
                $weeklySlot['next_date'],
                $weeklySlot['order'],
            ]) {
                $slot = array_shift($catchUpSlots);
            } else {
                $slot = $weeklySlot;
                $weeklyPatterns[0]['next_date'] = Carbon::parse($slot['next_date'])->addWeek()->toDateString();
            }

            $sessionPages = max(1, (int) $slot['pages']);
            $pageStart = $currentPage;
            $pageEnd = min($totalPages, $currentPage + $sessionPages - 1);

            $sessions[] = [
                'number' => count($sessions) + 1,
                'date' => Carbon::parse($slot['next_date'])->toDateString(),
                'type' => $slot['type'],
                'weekday' => $slot['weekday'],
                'weekday_label' => $slot['weekday_label'],
                'page_start' => $pageStart,
                'page_end' => $pageEnd,
                'pages' => $pageEnd - $pageStart + 1,
                'page_range' => "Halaman {$pageStart}-{$pageEnd}",
                'range_start_date' => $slot['range_start_date'] ?? null,
                'range_end_date' => $slot['range_end_date'] ?? null,
            ];

            $currentPage = $pageEnd + 1;
        }

        $sessions = $this->assignTeachers($sessions, $teacherPool);

        return [
            'total_pages' => $totalPages,
            'start_page' => $startPage,
            'pages_per_session' => $pagesPerSession,
            'start_date' => $startDate->toDateString(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'end_date' => $sessions ? end($sessions)['date'] : $startDate->toDateString(),
            'total_sessions' => count($sessions),
            'extra_sessions' => $extraSessions,
            'catch_up_ranges' => $catchUpRanges,
            'teacher_pool' => $teacherPool,
            'teacher_overrides' => [],
            'sessions' => $sessions,
        ];
    }

    protected function positiveInt(mixed $value, string $label): int
    {
        $number = (int) $value;

        if ($number < 1) {
            throw new InvalidArgumentException("{$label} minimal 1.");
        }

        return $number;
    }

    protected function normalizeTime(mixed $value): ?string
    {
        $time = trim((string) ($value ?? ''));

        if ($time === '') {
            return null;
        }

        return strlen($time) === 5 ? $time . ':00' : $time;
    }

    protected function normalizeExtraSessions(mixed $sessions, int $defaultPages, Carbon $startDate): array
    {
        if (! is_array($sessions)) {
            return [];
        }

        $normalized = [];
        $usedWeekdays = [$startDate->dayOfWeekIso => true];

        foreach ($sessions as $session) {
            if (! is_array($session) || empty($session['date'])) {
                continue;
            }

            $date = Carbon::parse($session['date'])->startOfDay();

            if ($date->lt($startDate)) {
                throw new InvalidArgumentException('Tanggal hari tambahan mingguan tidak boleh sebelum tanggal mulai.');
            }

            if (isset($usedWeekdays[$date->dayOfWeekIso])) {
                throw new InvalidArgumentException('Hari tambahan mingguan tidak boleh sama dengan hari tanggal mulai atau hari tambahan lain.');
            }

            $usedWeekdays[$date->dayOfWeekIso] = true;

            $normalized[] = [
                'date' => $date->toDateString(),
                'pages' => max(1, (int) ($session['pages'] ?? $defaultPages)),
                'weekday' => $date->dayOfWeekIso,
                'weekday_label' => $this->weekdayLabel($date->dayOfWeekIso),
            ];
        }

        usort($normalized, fn (array $left, array $right) => $left['date'] <=> $right['date']);

        return $normalized;
    }

    protected function normalizeCatchUpRanges(mixed $ranges, int $defaultPages, Carbon $startDate): array
    {
        if (! is_array($ranges)) {
            return [];
        }

        $normalized = [];

        foreach ($ranges as $range) {
            if (! is_array($range) || empty($range['start_date']) || empty($range['end_date'])) {
                continue;
            }

            $rangeStart = Carbon::parse($range['start_date'])->startOfDay();
            $rangeEnd = Carbon::parse($range['end_date'])->startOfDay();

            if ($rangeStart->lt($startDate) || $rangeEnd->lt($startDate)) {
                throw new InvalidArgumentException('Tanggal kejar target tidak boleh sebelum tanggal mulai.');
            }

            if ($rangeEnd->lt($rangeStart)) {
                throw new InvalidArgumentException('Tanggal akhir kejar target tidak boleh sebelum tanggal awal.');
            }

            $normalized[] = [
                'start_date' => $rangeStart->toDateString(),
                'end_date' => $rangeEnd->toDateString(),
                'pages' => max(1, (int) ($range['pages'] ?? $defaultPages)),
                'days' => (int) $rangeStart->diffInDays($rangeEnd) + 1,
            ];
        }

        usort($normalized, fn (array $left, array $right) => [
            $left['start_date'],
            $left['end_date'],
        ] <=> [
            $right['start_date'],
            $right['end_date'],
        ]);

        return $normalized;
    }

    protected function expandCatchUpSlots(array $ranges): array
    {
        $slots = [];

        foreach ($ranges as $index => $range) {
            $cursor = Carbon::parse($range['start_date'])->startOfDay();
            $end = Carbon::parse($range['end_date'])->startOfDay();

            while ($cursor->lte($end)) {
                $slots[] = [
                    'date' => $cursor->toDateString(),
                    'next_date' => $cursor->toDateString(),
                    'pages' => $range['pages'],
                    'type' => 'catch_up',
                    'order' => 1000 + $index,
                    'weekday' => $cursor->dayOfWeekIso,
                    'weekday_label' => $this->weekdayLabel($cursor->dayOfWeekIso),
                    'range_start_date' => $range['start_date'],
                    'range_end_date' => $range['end_date'],
                ];

                $cursor->addDay();
            }
        }

        usort($slots, fn (array $left, array $right) => [
            $left['next_date'],
            $left['order'],
        ] <=> [
            $right['next_date'],
            $right['order'],
        ]);

        return $slots;
    }

    protected function normalizeTeacherPool(mixed $teachers): array
    {
        if (! is_array($teachers)) {
            return [];
        }

        $normalized = [];

        foreach ($teachers as $teacher) {
            if (! is_array($teacher)) {
                continue;
            }

            $name = trim((string) ($teacher['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $normalized[] = [
                'user_id' => ! empty($teacher['user_id']) ? (int) $teacher['user_id'] : null,
                'name' => $name,
                'is_manual' => empty($teacher['user_id']),
            ];
        }

        return array_values($normalized);
    }

    protected function assignTeachers(array $sessions, array $teacherPool): array
    {
        if (empty($teacherPool)) {
            return $sessions;
        }

        $teacherCount = count($teacherPool);

        foreach ($sessions as $index => $session) {
            $teacher = $teacherCount > 0 ? $teacherPool[$index % $teacherCount] : null;

            if (! $teacher) {
                continue;
            }

            $sessions[$index]['teacher_name'] = $teacher['name'];
            $sessions[$index]['teacher_user_id'] = $teacher['user_id'] ?? null;
            $sessions[$index]['teacher_is_manual'] = (bool) ($teacher['is_manual'] ?? false);
            $sessions[$index]['teacher_is_override'] = false;
        }

        return $sessions;
    }

    protected function weekdayLabel(int $dayOfWeekIso): string
    {
        return match ($dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
            default => '-',
        };
    }
}
