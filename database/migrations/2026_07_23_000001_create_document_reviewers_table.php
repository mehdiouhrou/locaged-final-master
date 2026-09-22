<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_reviewers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'validated', 'rejected'])->default('pending');
            $table->dateTime('deadline')->nullable();
            $table->text('comment')->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'reviewer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_reviewers');
    }
};
