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
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            
            // Drop old unique index if it exists (standard Spatie)
            $table->dropUnique(['name', 'guard_name']);
            
            // Add new scoped unique index
            $table->unique(['name', 'guard_name', 'tenant_id']);
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropUnique(['name', 'guard_name', 'tenant_id']);
            $table->unique(['name', 'guard_name']);
            $table->dropColumn('tenant_id');
        });
    }
};
