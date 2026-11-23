<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mobile_device_tokens', function (Blueprint $table): void {
            $table->string('actor_role', 20)->nullable()->after('owner_id');
            $table->index(['owner_type', 'owner_id', 'actor_role', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::table('mobile_device_tokens', function (Blueprint $table): void {
            $table->dropIndex(['mobile_device_tokens_owner_type_owner_id_actor_role_revoked_at_index']);
            $table->dropColumn('actor_role');
        });
    }
};
