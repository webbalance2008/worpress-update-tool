<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installed_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // plugin, theme, core
            $table->string('slug');
            $table->string('name');
            $table->string('current_version', 50);
            $table->string('available_version', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_update_enabled')->default(false);
            $table->string('tested_wp_version', 20)->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['site_id', 'type', 'slug']);
            $table->index(['site_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installed_items');
    }
};
