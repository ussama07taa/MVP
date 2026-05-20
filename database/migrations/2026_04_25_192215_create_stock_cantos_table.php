<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('stock_cantos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name')->nullable()->comment('e.g. Bandchant PVC, Canto ABS');
            $table->string('color_code');
            $table->string('finish_type')->nullable()->comment('e.g. Mat, Brillant, 3D');
            $table->string('provider_catalog')->nullable()->comment('e.g. Catalogue SIBU / Palette Suncity');
            $table->integer('width_mm')->comment('e.g. 22');
            $table->decimal('thickness_mm', 3, 2)->comment('e.g. 0.80');
            $table->decimal('total_length_remaining', 10, 2)->comment('Meters');
            $table->decimal('alert_threshold', 8, 2)->default(5.00);
            $table->decimal('cost_price_per_m', 8, 2)->comment('Cost price per meter');
            $table->decimal('base_price_sell_per_m', 8, 2);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'color_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_cantos');
    }
};
