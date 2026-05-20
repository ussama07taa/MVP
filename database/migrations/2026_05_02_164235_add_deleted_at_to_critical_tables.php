<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'deleted_at')) {
                $table->softDeletes();
            }
        });
        Schema::table('stock_panels', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_panels', 'deleted_at')) {
                $table->softDeletes();
            }
        });
        Schema::table('stock_cantos', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_cantos', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('stock_panels', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('stock_cantos', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
