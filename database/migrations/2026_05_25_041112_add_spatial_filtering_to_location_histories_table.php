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
        Schema::table('location_histories', function (Blueprint $table) {
            $table->decimal('raw_latitude', 10, 8)->nullable()->after('longitude');
            $table->decimal('raw_longitude', 11, 8)->nullable()->after('raw_latitude');
            $table->integer('confidence_score')->default(100)->after('raw_longitude'); // 0-100
            $table->boolean('is_outlier')->default(false)->after('confidence_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('location_histories', function (Blueprint $table) {
            $table->dropColumn([
                'raw_latitude',
                'raw_longitude',
                'confidence_score',
                'is_outlier'
            ]);
        });
    }
};
