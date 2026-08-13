<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE document_comments MODIFY COLUMN type ENUM('reviewer_rejection', 'author_resubmit', 'initial_description') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE document_comments MODIFY COLUMN type ENUM('reviewer_rejection', 'author_resubmit') NOT NULL");
    }
};
