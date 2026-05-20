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
        Schema::table('employee_attendances', function (Blueprint $table) {
            $table->decimal('overtime_hours', 5, 2)->default(0)->after('status');
            $table->decimal('overtime_wage', 10, 2)->default(0)->after('overtime_hours');
            $table->string('notes')->nullable()->after('is_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_attendances', function (Blueprint $table) {
            $table->dropColumn(['overtime_hours', 'overtime_wage', 'notes']);
        });
    }
};
