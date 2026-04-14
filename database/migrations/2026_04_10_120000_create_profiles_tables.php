<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * LocaGed V2 — Profils d'accès aux catégories (CDC §2.2).
     */
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('profile_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['profile_id', 'category_id']);
        });

        Schema::create('profile_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['profile_id', 'user_id']);
        });

        Schema::create('profile_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['profile_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_service');
        Schema::dropIfExists('profile_user');
        Schema::dropIfExists('profile_category');
        Schema::dropIfExists('profiles');
    }
};
