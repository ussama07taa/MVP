<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Expand status enum to include quote-specific statuses
        DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft','sent','paid','partial','cancelled','accepted','refused','expired') DEFAULT 'draft'");

        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedSmallInteger('validity_days')->nullable()->after('due_date')->comment('Number of days the quote is valid');
            $table->date('expiry_date')->nullable()->after('validity_days')->comment('Auto-calculated: issue_date + validity_days');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['validity_days', 'expiry_date']);
        });

        DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft','sent','paid','partial','cancelled') DEFAULT 'draft'");
    }
};
