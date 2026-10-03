<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('notification_id', 255);
            $table->foreignId('device_id')->constrained('mobile_device_tokens')->cascadeOnDelete();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['notification_id', 'device_id']);
            $table->index(['notification_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_notification_deliveries');
    }
};
