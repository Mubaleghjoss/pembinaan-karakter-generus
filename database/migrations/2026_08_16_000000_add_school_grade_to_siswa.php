<?php

use App\Support\TargetGrade;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('siswa', 'school_grade')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->string('school_grade', 20)->nullable()->after('kelas_id')->index();
            });
        }

        $validGrades = TargetGrade::values();

        DB::table('siswa')
            ->whereNull('school_grade')
            ->whereIn('target_grade_override', $validGrades)
            ->update(['school_grade' => DB::raw('target_grade_override')]);

        if (Schema::hasTable('generus_registrations')) {
            DB::table('siswa')->whereNull('school_grade')->orderBy('id')->chunkById(100, function ($students) use ($validGrades) {
                foreach ($students as $student) {
                    $grade = DB::table('generus_registrations')
                        ->where('siswa_id', $student->id)
                        ->whereIn('school_grade', $validGrades)
                        ->latest('submitted_at')
                        ->value('school_grade');

                    if ($grade) {
                        DB::table('siswa')->where('id', $student->id)->update(['school_grade' => $grade]);
                    }
                }
            });
        }

        DB::table('siswa')
            ->whereNotNull('school_grade')
            ->whereColumn('target_grade_override', 'school_grade')
            ->update(['target_grade_override' => null]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('siswa', 'school_grade')) {
            return;
        }

        DB::table('siswa')
            ->whereNull('target_grade_override')
            ->whereNotNull('school_grade')
            ->update(['target_grade_override' => DB::raw('school_grade')]);

        Schema::table('siswa', function (Blueprint $table) {
            $table->dropIndex(['school_grade']);
            $table->dropColumn('school_grade');
        });
    }
};
