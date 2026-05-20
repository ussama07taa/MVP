<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshop_queue_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_id')->constrained('workshop_queues')->cascadeOnDelete();
            $table->string('label'); // Découpe, Pose Canto, Ponçage...
            $table->boolean('is_done')->default(false);
            $table->timestamp('done_at')->nullable();
            $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_queue_services');
    }
};
