<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('update_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->string('level', 10);
            $table->text('explanation');
            $table->json('factors');
            $table->timestamps();

            $table->index('update_job_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_assessments');
    }
};
