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
            $table->integer('signal_strength')->nullable()->after('activity');
            $table->boolean('has_internet')->default(false)->after('signal_strength');
            $table->string('tracking_state')->nullable()->after('has_internet');
            $table->string('activity_status')->nullable()->after('tracking_state');
            $table->dateTime('last_status_at')->nullable()->after('activity_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['signal_strength', 'has_internet', 'tracking_state', 'activity_status', 'last_status_at']);
        });
    }
};
