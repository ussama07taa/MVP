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
        Schema::table('employee_advances', function (Blueprint $table) {
            $table->string('type')->default('advance')->after('employee_id'); // advance, bonus, sanction
            $table->text('notes')->nullable()->after('amount');
        });

        Schema::table('pay_slips', function (Blueprint $table) {
            $table->decimal('overtime_hours_total', 8, 2)->default(0)->after('days_worked');
            $table->decimal('sanctions_total', 10, 2)->default(0)->after('bonuses_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_advances', function (Blueprint $table) {
            $table->dropColumn(['type', 'notes']);
        });

        Schema::table('pay_slips', function (Blueprint $table) {
            $table->dropColumn(['overtime_hours_total', 'sanctions_total']);
        });
    }
};
