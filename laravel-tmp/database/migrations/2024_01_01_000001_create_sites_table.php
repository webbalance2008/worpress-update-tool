<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('url', 500);
            $table->text('auth_secret')->nullable(); // encrypted at rest
            $table->string('registration_token', 100)->nullable()->unique();
            $table->string('status', 20)->default('pending');
            $table->string('wp_version', 20)->nullable();
            $table->string('php_version', 20)->nullable();
            $table->string('active_theme')->nullable();
            $table->string('plugin_version', 20)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('registration_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
