<?php

use App\Models\Siswa;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->string('kelompok', 60)->nullable()->after('alamat');
            $table->index('kelompok');
        });

        $allowed = array_keys(Siswa::kelompokOptions());

        DB::table('siswa')
            ->whereNotNull('alamat')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($allowed) {
                foreach ($rows as $row) {
                    $normalized = Siswa::normalizeKelompok($row->alamat);

                    if ($normalized && in_array($normalized, $allowed, true)) {
                        DB::table('siswa')
                            ->where('id', $row->id)
                            ->update(['kelompok' => $normalized]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->dropIndex(['kelompok']);
            $table->dropColumn('kelompok');
        });
    }
};
