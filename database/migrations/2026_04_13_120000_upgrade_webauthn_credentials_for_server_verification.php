<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webauthn_credentials', function (Blueprint $table) {
            $table->longText('credential_public_key')->nullable()->after('credential_id');
            $table->unsignedBigInteger('signature_counter')->nullable()->after('credential_public_key');
            $table->string('attestation_format', 50)->nullable()->after('signature_counter');
            $table->string('aaguid', 64)->nullable()->after('attestation_format');
            $table->json('transports')->nullable()->after('aaguid');
            $table->string('user_handle')->nullable()->after('transports');
            $table->boolean('user_verified')->nullable()->after('user_handle');
            $table->boolean('backup_eligible')->nullable()->after('user_verified');
            $table->boolean('backed_up')->nullable()->after('backup_eligible');
        });
    }

    public function down(): void
    {
        Schema::table('webauthn_credentials', function (Blueprint $table) {
            $table->dropColumn([
                'credential_public_key',
                'signature_counter',
                'attestation_format',
                'aaguid',
                'transports',
                'user_handle',
                'user_verified',
                'backup_eligible',
                'backed_up',
            ]);
        });
    }
};
