<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_panels', function (Blueprint $table) {
            $table->integer('alert_threshold')->default(3)->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('stock_panels', function (Blueprint $table) {
            $table->dropColumn('alert_threshold');
        });
    }
};
