<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->updateOrInsert(
            ['name' => 'guru'],
            [
                'display_name' => 'Guru',
                'description' => 'Guru PKG dengan akses Portal Guru',
                'permissions' => json_encode([]),
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $hasMustChangePassword = Schema::hasColumn('users', 'must_change_password');
        $hasPasswordChangedAt = Schema::hasColumn('users', 'password_changed_at');
        Schema::table('users', function (Blueprint $table) use ($hasMustChangePassword, $hasPasswordChangedAt) {
            if (! $hasMustChangePassword) {
                $table->boolean('must_change_password')->default(false)->after('password');
            }
            if (! $hasPasswordChangedAt) {
                $table->timestamp('password_changed_at')->nullable()->after('must_change_password');
            }
        });

        Schema::create('teacher_materials', function (Blueprint $table) {
            $table->id();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->string('google_drive_url', 1000);
            $table->json('rombels')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('teacher_material_session', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_material_id')->constrained('teacher_materials')->cascadeOnDelete();
            $table->foreignId('teacher_schedule_session_id')->constrained('teacher_schedule_sessions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['teacher_material_id', 'teacher_schedule_session_id'],
                'teacher_material_session_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_material_session');
        Schema::dropIfExists('teacher_materials');

        $columns = collect(['must_change_password', 'password_changed_at'])
            ->filter(fn (string $column) => Schema::hasColumn('users', $column))
            ->values()
            ->all();
        Schema::table('users', function (Blueprint $table) use ($columns) {
            if ($columns) {
                $table->dropColumn($columns);
            }
        });

        if (! DB::table('users')->whereIn('role_id', DB::table('roles')->where('name', 'guru')->select('id'))->exists()) {
            DB::table('roles')->where('name', 'guru')->delete();
        }
    }
};
