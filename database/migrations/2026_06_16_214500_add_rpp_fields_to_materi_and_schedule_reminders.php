<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('materi')) {
            Schema::table('materi', function (Blueprint $table) {
                if (! Schema::hasColumn('materi', 'rpp_is_enabled')) {
                    $table->boolean('rpp_is_enabled')->default(false)->after('is_active');
                }

                if (! Schema::hasColumn('materi', 'rpp_status')) {
                    $table->string('rpp_status', 20)->default('draft')->after('rpp_is_enabled');
                }

                if (! Schema::hasColumn('materi', 'rpp_total_pages')) {
                    $table->unsignedInteger('rpp_total_pages')->nullable()->after('rpp_status');
                }

                if (! Schema::hasColumn('materi', 'rpp_start_page')) {
                    $table->unsignedInteger('rpp_start_page')->nullable()->after('rpp_total_pages');
                }

                if (! Schema::hasColumn('materi', 'rpp_pages_per_session')) {
                    $table->unsignedInteger('rpp_pages_per_session')->nullable()->after('rpp_start_page');
                }

                if (! Schema::hasColumn('materi', 'rpp_start_date')) {
                    $table->date('rpp_start_date')->nullable()->after('rpp_pages_per_session');
                }

                if (! Schema::hasColumn('materi', 'rpp_end_date')) {
                    $table->date('rpp_end_date')->nullable()->after('rpp_start_date');
                }

                if (! Schema::hasColumn('materi', 'rpp_extra_sessions')) {
                    $table->json('rpp_extra_sessions')->nullable()->after('rpp_end_date');
                }

                if (! Schema::hasColumn('materi', 'rpp_published_at')) {
                    $table->timestamp('rpp_published_at')->nullable()->after('rpp_extra_sessions');
                }
            });
        }

        if (Schema::hasTable('schedule_reminders')) {
            Schema::table('schedule_reminders', function (Blueprint $table) {
                if (! Schema::hasColumn('schedule_reminders', 'source_type')) {
                    $table->string('source_type', 50)->nullable()->after('created_by');
                }

                if (! Schema::hasColumn('schedule_reminders', 'source_id')) {
                    $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
                }

                if (! Schema::hasColumn('schedule_reminders', 'source_payload')) {
                    $table->json('source_payload')->nullable()->after('source_id');
                }
            });

            Schema::table('schedule_reminders', function (Blueprint $table) {
                $table->index(['source_type', 'source_id'], 'schedule_reminders_source_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('schedule_reminders')) {
            Schema::table('schedule_reminders', function (Blueprint $table) {
                $table->dropIndex('schedule_reminders_source_index');
            });

            Schema::table('schedule_reminders', function (Blueprint $table) {
                foreach (['source_payload', 'source_id', 'source_type'] as $column) {
                    if (Schema::hasColumn('schedule_reminders', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('materi')) {
            Schema::table('materi', function (Blueprint $table) {
                foreach ([
                    'rpp_published_at',
                    'rpp_extra_sessions',
                    'rpp_end_date',
                    'rpp_start_date',
                    'rpp_pages_per_session',
                    'rpp_start_page',
                    'rpp_total_pages',
                    'rpp_status',
                    'rpp_is_enabled',
                ] as $column) {
                    if (Schema::hasColumn('materi', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
