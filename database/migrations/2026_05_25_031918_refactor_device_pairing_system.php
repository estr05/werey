<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('pairing_code')->nullable()->unique()->after('identifier');
            $table->string('pairing_status')->default('pending')->after('pairing_code'); // pending, paired, active, replaced, revoked, expired
            $table->timestamp('pairing_expires_at')->nullable()->after('pairing_status');
            $table->string('device_uuid')->nullable()->after('pairing_expires_at');
            $table->string('device_manufacturer')->nullable()->after('device_uuid');
            $table->string('device_model')->nullable()->after('device_manufacturer');
            $table->string('os_version')->nullable()->after('device_model');
            $table->string('app_version')->nullable()->after('os_version');
        });

        Schema::create('device_pairing_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('event_type'); // generated, paired, replacement_requested, replaced, revoked, failed_attempt
            $table->string('previous_device_uuid')->nullable();
            $table->string('new_device_uuid')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_pairing_events');

        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'pairing_code',
                'pairing_status',
                'pairing_expires_at',
                'device_uuid',
                'device_manufacturer',
                'device_model',
                'os_version',
                'app_version',
            ]);
        });
    }
};
