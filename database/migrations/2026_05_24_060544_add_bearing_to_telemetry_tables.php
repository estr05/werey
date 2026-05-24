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
            $table->float('bearing')->nullable()->after('last_accuracy')->comment('Orientación en grados (0-359.99)');
        });

        Schema::table('location_histories', function (Blueprint $table) {
            $table->float('bearing')->nullable()->after('accuracy')->comment('Orientación en grados (0-359.99)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('bearing');
        });

        Schema::table('location_histories', function (Blueprint $table) {
            $table->dropColumn('bearing');
        });
    }
};
