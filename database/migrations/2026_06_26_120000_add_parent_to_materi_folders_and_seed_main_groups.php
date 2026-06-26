<?php

use App\Support\MateriFolderTree;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('materi_folders')) {
            return;
        }

        if (! Schema::hasColumn('materi_folders', 'parent_id')) {
            Schema::table('materi_folders', function (Blueprint $table) {
                $table->foreignId('parent_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('materi_folders')
                    ->nullOnDelete();
            });
        }

        $now = now();

        foreach (MateriFolderTree::MAIN_FOLDERS as $folder) {
            $existing = DB::table('materi_folders')->where('name', $folder['name'])->first();

            if ($existing) {
                DB::table('materi_folders')
                    ->where('id', $existing->id)
                    ->update([
                        'parent_id' => null,
                        'description' => $existing->description ?: $folder['description'],
                        'sort_order' => $folder['sort_order'],
                        'is_active' => true,
                        'updated_at' => $now,
                    ]);

                continue;
            }

            DB::table('materi_folders')->insert([
                'name' => $folder['name'],
                'parent_id' => null,
                'description' => $folder['description'],
                'sort_order' => $folder['sort_order'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $pkgId = DB::table('materi_folders')->where('name', 'PKG')->value('id');

        if (! $pkgId) {
            return;
        }

        foreach (MateriFolderTree::PKG_CHARACTER_FOLDERS as $index => $name) {
            $existing = DB::table('materi_folders')
                ->where('name', $name)
                ->where('id', '!=', $pkgId)
                ->orderBy('id')
                ->first();

            if ($existing) {
                DB::table('materi_folders')
                    ->where('id', $existing->id)
                    ->update([
                        'parent_id' => $pkgId,
                        'sort_order' => $index + 1,
                        'is_active' => true,
                        'updated_at' => $now,
                    ]);

                continue;
            }

            DB::table('materi_folders')->insert([
                'name' => $name,
                'parent_id' => $pkgId,
                'description' => null,
                'sort_order' => $index + 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('materi_folders') || ! Schema::hasColumn('materi_folders', 'parent_id')) {
            return;
        }

        Schema::table('materi_folders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
