<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('materi_folders')) {
            Schema::create('materi_folders', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('materi')) {
            Schema::table('materi', function (Blueprint $table) {
                if (! Schema::hasColumn('materi', 'materi_folder_id')) {
                    $table->foreignId('materi_folder_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('materi_folders')
                        ->nullOnDelete();
                }

                if (! Schema::hasColumn('materi', 'rpp_start_time')) {
                    $table->time('rpp_start_time')->nullable()->after('rpp_start_date');
                }

                if (! Schema::hasColumn('materi', 'rpp_end_time')) {
                    $table->time('rpp_end_time')->nullable()->after('rpp_start_time');
                }
            });
        }

        if (Schema::hasTable('materi_folders') && DB::table('materi_folders')->count() === 0) {
            $now = now();
            $names = Schema::hasTable('karakter')
                ? DB::table('karakter')
                    ->whereNotNull('nama')
                    ->select('nama')
                    ->distinct()
                    ->orderBy('nama')
                    ->limit(29)
                    ->pluck('nama')
                    ->filter()
                    ->values()
                    ->all()
                : [];

            if (empty($names)) {
                $names = ['29 Karakter Luhur'];
            }

            foreach ($names as $index => $name) {
                DB::table('materi_folders')->insert([
                    'name' => $name,
                    'description' => $index === 0 && count($names) === 1 ? 'Folder awal untuk materi 29 karakter luhur.' : null,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('materi')) {
            Schema::table('materi', function (Blueprint $table) {
                if (Schema::hasColumn('materi', 'materi_folder_id')) {
                    $table->dropConstrainedForeignId('materi_folder_id');
                }

                foreach (['rpp_end_time', 'rpp_start_time'] as $column) {
                    if (Schema::hasColumn('materi', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('materi_folders');
    }
};
