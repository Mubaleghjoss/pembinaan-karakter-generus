<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('subscribable_type', 80);
            $table->unsignedBigInteger('subscribable_id');
            $table->string('endpoint', 500)->unique();
            $table->string('public_key')->nullable();
            $table->string('auth_token')->nullable();
            $table->string('content_encoding')->nullable();
            $table->timestamps();

            $table->index(
                ['subscribable_type', 'subscribable_id'],
                'push_subscriptions_subscribable_morph_idx'
            );
        });

        Schema::create('pwa_notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('notifiable_type', 80);
            $table->unsignedBigInteger('notifiable_id');
            $table->string('notification_key', 120);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['notifiable_type', 'notifiable_id', 'notification_key'],
                'pwa_delivery_notifiable_key_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pwa_notification_deliveries');
        Schema::dropIfExists('push_subscriptions');
    }
};
