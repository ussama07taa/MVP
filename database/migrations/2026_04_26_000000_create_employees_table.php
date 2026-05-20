<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('role')->default('Menuisier'); // Menuisier, Peintre, Manoeuvre, etc.
            $table->decimal('daily_salary', 10, 2)->default(0); // Salaire par jour
            $table->decimal('total_advances', 10, 2)->default(0); // Salaf
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('employees');
    }
};
