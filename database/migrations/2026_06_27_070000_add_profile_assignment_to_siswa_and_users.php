<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('siswa') && ! Schema::hasColumn('siswa', 'profile_assignment_confirmed_at')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->timestamp('profile_assignment_confirmed_at')
                    ->nullable()
                    ->after('target_grade_override');
            });
        }

        if (Schema::hasTable('users')) {
            if (! Schema::hasColumn('users', 'kelompok')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('kelompok', 60)->nullable()->after('phone')->index();
                });
            }

            if (! Schema::hasColumn('users', 'profile_assignment_confirmed_at')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->timestamp('profile_assignment_confirmed_at')
                        ->nullable()
                        ->after('kelompok');
                });
            }
        }

        if (Schema::hasTable('settings')) {
            $settingColumns = Schema::getColumnListing('settings');
            $now = now();

            foreach ([
                'popup_profile_assignment_prompt_enabled' => '1',
                'popup_profile_assignment_prompt_required' => '1',
                'popup_biometric_prompt_enabled' => '0',
                'popup_biometric_prompt_required' => '0',
            ] as $key => $value) {
                $attributes = [
                    'value' => $value,
                    'updated_at' => $now,
                ];

                if (in_array('group', $settingColumns, true)) {
                    $attributes['group'] = 'popup';
                }
                if (in_array('type', $settingColumns, true)) {
                    $attributes['type'] = 'boolean';
                }
                if (in_array('created_at', $settingColumns, true)) {
                    $attributes['created_at'] = $now;
                }

                DB::table('settings')->updateOrInsert(['key' => $key], $attributes);
            }
        }

        if (
            Schema::hasTable('materi_targets')
            && Schema::hasColumn('materi_targets', 'source_key')
            && Schema::hasColumn('materi_targets', 'semester')
        ) {
            $smaTwelveTargets = DB::table('materi_targets')
                ->where('target_grade', 'sma_12')
                ->whereNotNull('source_key')
                ->get();

            foreach ($smaTwelveTargets as $target) {
                $record = (array) $target;
                unset($record['id']);
                $record['source_key'] = 'kmgt_pranikah_'.$record['source_key'];
                $record['target_grade'] = 'pranikah';
                $record['description'] = trim(
                    ($record['description'] ?? '')."\n\nTarget lanjutan untuk generus Pranikah setelah menyelesaikan SMA/K."
                );
                $record['created_at'] = now();
                $record['updated_at'] = now();

                DB::table('materi_targets')->updateOrInsert(
                    ['source_key' => $record['source_key']],
                    $record
                );
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('materi_targets') && Schema::hasColumn('materi_targets', 'source_key')) {
            DB::table('materi_targets')
                ->where('source_key', 'like', 'kmgt_pranikah_%')
                ->delete();
        }

        if (Schema::hasTable('settings')) {
            DB::table('settings')
                ->whereIn('key', [
                    'popup_profile_assignment_prompt_enabled',
                    'popup_profile_assignment_prompt_required',
                ])
                ->delete();
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'profile_assignment_confirmed_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('profile_assignment_confirmed_at');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'kelompok')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['kelompok']);
                $table->dropColumn('kelompok');
            });
        }

        if (Schema::hasTable('siswa') && Schema::hasColumn('siswa', 'profile_assignment_confirmed_at')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->dropColumn('profile_assignment_confirmed_at');
            });
        }
    }
};
