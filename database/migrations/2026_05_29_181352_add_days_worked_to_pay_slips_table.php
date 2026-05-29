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
        Schema::table('pay_slips', function (Blueprint $table) {
            $table->decimal('days_worked', 8, 2)->default(0)->after('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pay_slips', function (Blueprint $table) {
            $table->dropColumn('days_worked');
        });
    }
};
