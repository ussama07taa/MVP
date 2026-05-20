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
        Schema::table('stock_panels', function (Blueprint $table) {
            $table->string('color_name')->nullable()->after('color_code');
        });

        Schema::table('stock_cantos', function (Blueprint $table) {
            $table->string('color_name')->nullable()->after('color_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_panels', function (Blueprint $table) {
            $table->dropColumn('color_name');
        });

        Schema::table('stock_cantos', function (Blueprint $table) {
            $table->dropColumn('color_name');
        });
    }
};
