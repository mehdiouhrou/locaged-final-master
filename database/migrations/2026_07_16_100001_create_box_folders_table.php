<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('box_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('box_id')->constrained('boxes')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['box_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('box_folders');
    }
};
