<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('update_job_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('update_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('installed_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->string('slug');
            $table->string('old_version', 50);
            $table->string('requested_version', 50);
            $table->string('resulting_version', 50)->nullable();
            $table->string('status', 20)->default('pending');
            $table->json('raw_result')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['update_job_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('update_job_items');
    }
};
