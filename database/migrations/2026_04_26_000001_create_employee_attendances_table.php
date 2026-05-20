<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('employee_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('date');
            $table->string('status')->default('present'); // present, absent, half_day
            $table->decimal('wage_earned', 10, 2)->default(0);
            $table->boolean('is_paid')->default(false);
            $table->timestamps();
            
            // Un employé ne peut avoir qu'un seul pointage par jour
            $table->unique(['employee_id', 'date']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('employee_attendances');
    }
};
