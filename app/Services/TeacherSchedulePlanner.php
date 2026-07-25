<?php

namespace App\Services;

use App\Models\TeacherProfile;
use App\Models\TeacherScheduleAssignment;
use App\Models\TeacherSchedulePeriod;
use App\Models\TeacherScheduleSession;
use App\Models\TeacherScheduleTemplate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TeacherSchedulePlanner
{
    public function generate(TeacherSchedulePeriod $period, ?int $actorId = null): TeacherSchedulePeriod
    {
        if ($period->status === 'published') {
            throw ValidationException::withMessages([
                'month' => 'Jadwal yang sudah diterbitkan tidak dapat dibuat ulang.',
            ]);
        }

        return DB::transaction(function () use ($period, $actorId) {
            $period = TeacherSchedulePeriod::query()->lockForUpdate()->findOrFail($period->id);
            $monthStart = $period->month->copy()->startOfMonth();
            $monthEnd = $period->month->copy()->endOfMonth();
            $templates = TeacherScheduleTemplate::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('weekday')
                ->orderBy('rombel')
                ->get();

            if ($templates->isEmpty()) {
                throw ValidationException::withMessages([
                    'templates' => 'Aktifkan minimal satu Template Slot Mingguan sebelum membuat jadwal.',
                ]);
            }

            foreach ($templates as $template) {
                $targetWeekday = $this->carbonWeekday($template->weekday);
                $cursor = $monthStart->copy();
                if ($cursor->dayOfWeek !== $targetWeekday) {
                    $cursor->next($targetWeekday);
                }

                while ($cursor->lte($monthEnd)) {
                    TeacherScheduleSession::query()->firstOrCreate([
                        'period_id' => $period->id,
                        'session_date' => $cursor->toDateString(),
                        'rombel' => $template->rombel,
                        'start_time' => $template->start_time,
                    ], [
                        'template_id' => $template->id,
                        'end_time' => $template->end_time,
                        'location' => $template->location,
                        'status' => 'scheduled',
                    ]);
                    $cursor->addWeek();
                }
            }

            TeacherScheduleAssignment::query()
                ->whereHas('session', fn ($query) => $query->where('period_id', $period->id))
                ->where('source', 'auto')
                ->where('is_locked', false)
                ->delete();

            $profiles = TeacherProfile::query()->eligible()->orderBy('name')->get();
            $sessions = TeacherScheduleSession::query()
                ->where('period_id', $period->id)
                ->with(['assignments.teacher'])
                ->orderBy('session_date')
                ->orderBy('start_time')
                ->orderBy('rombel')
                ->get();

            foreach ($sessions as $session) {
                foreach (['main', 'backup'] as $role) {
                    $session->load('assignments.teacher');
                    if ($session->assignments->contains('role', $role)) {
                        continue;
                    }

                    $candidate = $this->bestCandidate($profiles, $session, $role, $period);
                    if ($candidate) {
                        $this->createAssignment($session, $candidate, $role, 'auto', false, $actorId);
                    }
                }
            }

            return $period->fresh(['sessions.assignments.teacher']);
        });
    }

    public function createAssignment(
        TeacherScheduleSession $session,
        TeacherProfile $teacher,
        string $role,
        string $source = 'manual',
        bool $locked = true,
        ?int $actorId = null,
        ?string $overloadReason = null
    ): TeacherScheduleAssignment {
        $token = Str::random(64);

        return TeacherScheduleAssignment::create([
            'session_id' => $session->id,
            'teacher_profile_id' => $teacher->id,
            'role' => $role,
            'source' => $source,
            'is_locked' => $locked,
            'confirmation_status' => 'pending',
            'confirmation_token_hash' => hash('sha256', $token),
            'confirmation_token_encrypted' => Crypt::encryptString($token),
            'overload_reason' => $overloadReason,
            'assigned_by' => $actorId,
        ]);
    }

    public function monthlyLoad(TeacherProfile $teacher, TeacherSchedulePeriod $period): int
    {
        return TeacherScheduleAssignment::query()
            ->where('teacher_profile_id', $teacher->id)
            ->whereHas('session', fn ($query) => $query->where('period_id', $period->id))
            ->count();
    }

    public function warnings(TeacherSchedulePeriod $period): array
    {
        $period->loadMissing(['sessions.assignments.teacher']);
        $warnings = [];

        foreach ($period->sessions as $session) {
            foreach (['main' => 'utama', 'backup' => 'cadangan'] as $role => $label) {
                if (! $session->assignments->contains('role', $role)) {
                    $warnings[] = "{$session->session_date->format('d/m/Y')} {$session->rombel}: pengajar {$label} belum diisi.";
                }
            }

            foreach ($session->assignments as $assignment) {
                if ($assignment->confirmation_status === 'declined') {
                    $role = $assignment->role === 'main' ? 'utama' : 'cadangan';
                    $warnings[] = "{$session->session_date->format('d/m/Y')} {$session->rombel}: {$assignment->teacher->name} ({$role}) berhalangan.";
                }
            }
        }

        $loads = TeacherScheduleAssignment::query()
            ->whereHas('session', fn ($query) => $query->where('period_id', $period->id))
            ->with('teacher')
            ->get()
            ->groupBy('teacher_profile_id');

        foreach ($loads as $assignments) {
            $teacher = $assignments->first()?->teacher;
            if ($teacher?->monthly_limit && $assignments->count() > $teacher->monthly_limit) {
                $warnings[] = "{$teacher->name} mendapat {$assignments->count()} tugas, melebihi batas {$teacher->monthly_limit}.";
            }

            $dates = $assignments
                ->map(fn ($assignment) => $assignment->session->session_date->copy()->startOfDay())
                ->unique(fn ($date) => $date->toDateString())
                ->sort()
                ->values();
            for ($index = 1; $index < $dates->count(); $index++) {
                if ($dates[$index - 1]->diffInDays($dates[$index]) === 1) {
                    $warnings[] = "{$teacher->name} dijadwalkan pada dua malam berturut-turut.";
                    break;
                }
            }
        }

        return array_values(array_unique($warnings));
    }

    private function bestCandidate(
        Collection $profiles,
        TeacherScheduleSession $session,
        string $role,
        TeacherSchedulePeriod $period
    ): ?TeacherProfile {
        $weekday = strtolower($session->session_date->englishDayOfWeek);

        return $profiles
            ->filter(fn (TeacherProfile $teacher) => $teacher->canServeRole($role))
            ->filter(fn (TeacherProfile $teacher) => in_array($session->rombel, $teacher->rombels ?? [], true))
            ->filter(fn (TeacherProfile $teacher) => in_array($weekday, $teacher->available_nights ?? [], true))
            ->filter(fn (TeacherProfile $teacher) => ! $session->assignments->contains('teacher_profile_id', $teacher->id))
            ->filter(fn (TeacherProfile $teacher) => ! $this->hasOverlap($teacher, $session))
            ->filter(function (TeacherProfile $teacher) use ($period) {
                return ! $teacher->monthly_limit || $this->monthlyLoad($teacher, $period) < $teacher->monthly_limit;
            })
            ->sortBy(function (TeacherProfile $teacher) use ($session, $weekday, $period) {
                $load = $this->monthlyLoad($teacher, $period);
                $consecutivePenalty = $this->hasAdjacentAssignment($teacher, $session) ? 1 : 0;
                $priority = (int) (($teacher->night_priorities ?? [])[$weekday] ?? 9);
                $asNeededPenalty = $teacher->participation_role === TeacherProfile::ROLE_AS_NEEDED ? 1 : 0;
                $historical = TeacherScheduleAssignment::query()
                    ->where('teacher_profile_id', $teacher->id)
                    ->whereHas('session', fn ($query) => $query->whereDate('session_date', '<', $session->session_date))
                    ->count();

                return sprintf(
                    '%03d-%01d-%02d-%01d-%05d-%05d',
                    $load,
                    $consecutivePenalty,
                    $priority,
                    $asNeededPenalty,
                    $historical,
                    $teacher->id
                );
            })
            ->first();
    }

    private function hasOverlap(TeacherProfile $teacher, TeacherScheduleSession $session): bool
    {
        return TeacherScheduleAssignment::query()
            ->where('teacher_profile_id', $teacher->id)
            ->whereHas('session', function ($query) use ($session) {
                $query->whereDate('session_date', $session->session_date)
                    ->where('start_time', '<', $session->end_time)
                    ->where('end_time', '>', $session->start_time);
            })
            ->exists();
    }

    private function hasAdjacentAssignment(TeacherProfile $teacher, TeacherScheduleSession $session): bool
    {
        $previous = $session->session_date->copy()->subDay()->toDateString();
        $next = $session->session_date->copy()->addDay()->toDateString();

        return TeacherScheduleAssignment::query()
            ->where('teacher_profile_id', $teacher->id)
            ->whereHas('session', fn ($query) => $query->whereIn('session_date', [$previous, $next]))
            ->exists();
    }

    private function carbonWeekday(string $weekday): int
    {
        return match ($weekday) {
            'monday' => Carbon::MONDAY,
            'tuesday' => Carbon::TUESDAY,
            'friday' => Carbon::FRIDAY,
            default => throw new \InvalidArgumentException('Hari template tidak didukung.'),
        };
    }
}
