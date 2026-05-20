<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Each renameColumn is split into its own Schema::table call so that
     * SQLite (used for testing) can apply them; SQLite cannot perform
     * multiple dropColumn / renameColumn operations in a single Blueprint.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->renameColumn('description', 'title');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->renameColumn('type', 'category');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->renameColumn('date', 'expense_date');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->text('notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->renameColumn('title', 'description');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->renameColumn('category', 'type');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->renameColumn('expense_date', 'date');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
