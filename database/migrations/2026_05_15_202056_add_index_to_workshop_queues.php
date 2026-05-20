<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workshop_queues', function (Blueprint $table) {
            $table->index(['tenant_id', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('workshop_queues', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'created_at']);
            $table->dropIndex(['status']);
        });
    }
};
