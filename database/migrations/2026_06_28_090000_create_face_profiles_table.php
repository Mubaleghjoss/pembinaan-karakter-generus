<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type', 20);
            $table->unsignedBigInteger('subject_id');
            $table->text('descriptor_payload');
            $table->string('photo_path')->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('enrolled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_used_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id', 'status']);
            $table->index(['status', 'subject_type']);
        });

        if (Schema::hasTable('settings')) {
            $now = now();
            $settings = [
                'face_attendance_enabled_siswa' => '1',
                'face_attendance_enabled_pamong' => '1',
                'face_attendance_center_lat' => '-6.219501040781815',
                'face_attendance_center_lng' => '106.64336089878178',
                'face_attendance_radius_value' => '100',
                'face_attendance_radius_unit' => 'meter',
                'face_attendance_match_threshold' => '35.00',
                'face_attendance_max_accuracy_meters' => '150',
                'popup_face_enrollment_prompt_enabled' => '1',
                'popup_face_enrollment_prompt_required' => '1',
            ];

            foreach ($settings as $key => $value) {
                DB::table('settings')->updateOrInsert(
                    ['key' => $key],
                    [
                        'value' => $value,
                        'group' => str_starts_with($key, 'popup_') ? 'popup' : 'face_attendance',
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('face_profiles');

        if (Schema::hasTable('settings')) {
            DB::table('settings')
                ->whereIn('key', [
                    'face_attendance_enabled_siswa',
                    'face_attendance_enabled_pamong',
                    'face_attendance_center_lat',
                    'face_attendance_center_lng',
                    'face_attendance_radius_value',
                    'face_attendance_radius_unit',
                    'face_attendance_match_threshold',
                    'face_attendance_max_accuracy_meters',
                    'popup_face_enrollment_prompt_enabled',
                    'popup_face_enrollment_prompt_required',
                ])
                ->delete();
        }
    }
};
