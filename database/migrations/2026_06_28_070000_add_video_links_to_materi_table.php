<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('materi')) {
            return;
        }

        if (! Schema::hasColumn('materi', 'video_links')) {
            Schema::table('materi', function (Blueprint $table) {
                $table->json('video_links')->nullable()->after('video_url');
            });
        }

        DB::table('materi')
            ->whereNotNull('video_url')
            ->where('video_url', '!=', '')
            ->where(function ($query) {
                $query->whereNull('video_links')
                    ->orWhere('video_links', '')
                    ->orWhere('video_links', '[]');
            })
            ->orderBy('id')
            ->select(['id', 'video_url'])
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('materi')
                        ->where('id', $row->id)
                        ->update([
                            'video_links' => json_encode([$row->video_url]),
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('materi') || ! Schema::hasColumn('materi', 'video_links')) {
            return;
        }

        Schema::table('materi', function (Blueprint $table) {
            $table->dropColumn('video_links');
        });
    }
};
