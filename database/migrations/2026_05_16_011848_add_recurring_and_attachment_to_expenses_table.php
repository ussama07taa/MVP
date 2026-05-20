<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'is_recurring')) {
                $table->boolean('is_recurring')->default(false)->after('notes');
            }
            if (!Schema::hasColumn('expenses', 'attachment')) {
                $table->string('attachment')->nullable()->after('is_recurring');
            }
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['is_recurring', 'attachment']);
        });
    }
};
