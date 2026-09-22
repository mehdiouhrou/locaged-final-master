<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE document_reviewers MODIFY status ENUM('pending','validated','rejected','cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE document_reviewers MODIFY status ENUM('pending','validated','rejected') NOT NULL DEFAULT 'pending'");
    }
};
