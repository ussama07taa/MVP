<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workshop_queue_services', function (Blueprint $table) {
            $table->string('material_type')->nullable()->after('label'); // MDF, LATI, etc.
            $table->string('material_color')->nullable()->after('material_type'); // White, Oak, etc.
        });
    }

    public function down(): void
    {
        Schema::table('workshop_queue_services', function (Blueprint $table) {
            $table->dropColumn(['material_type', 'material_color']);
        });
    }
};
