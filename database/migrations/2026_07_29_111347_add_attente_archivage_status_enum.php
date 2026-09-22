<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE documents MODIFY COLUMN status ENUM('pending', 'declined', 'approved', 'archived', 'destroyed', 'brouillon', 'en_relecture', 'valide', 'attente_archivage') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE document_status_history MODIFY COLUMN from_status ENUM('pending', 'declined', 'approved', 'archived', 'destroyed', 'brouillon', 'en_relecture', 'valide', 'attente_archivage') NOT NULL");
        DB::statement("ALTER TABLE document_status_history MODIFY COLUMN to_status ENUM('pending', 'declined', 'approved', 'archived', 'destroyed', 'brouillon', 'en_relecture', 'valide', 'attente_archivage') NOT NULL");
        DB::statement("ALTER TABLE workflow_rules MODIFY COLUMN from_status ENUM('pending', 'declined', 'approved', 'archived', 'destroyed', 'brouillon', 'en_relecture', 'valide', 'attente_archivage') NOT NULL");
        DB::statement("ALTER TABLE workflow_rules MODIFY COLUMN to_status ENUM('pending', 'declined', 'approved', 'archived', 'destroyed', 'brouillon', 'en_relecture', 'valide', 'attente_archivage') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE documents MODIFY COLUMN status ENUM('pending', 'declined', 'approved', 'archived', 'destroyed', 'brouillon', 'en_relecture', 'valide') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE document_status_history MODIFY COLUMN from_status ENUM('pending', 'declined', 'approved', 'archived', 'destroyed', 'brouillon', 'en_relecture', 'valide') NOT NULL");
        DB::statement("ALTER TABLE document_status_history MODIFY COLUMN to_status ENUM('pending', 'declined', 'approved', 'archived', 'destroyed', 'brouillon', 'en_relecture', 'valide') NOT NULL");
        DB::statement("ALTER TABLE workflow_rules MODIFY COLUMN from_status ENUM('pending', 'declined', 'approved', 'archived', 'destroyed', 'brouillon', 'en_relecture', 'valide') NOT NULL");
        DB::statement("ALTER TABLE workflow_rules MODIFY COLUMN to_status ENUM('pending', 'declined', 'approved', 'archived', 'destroyed', 'brouillon', 'en_relecture', 'valide') NOT NULL");
    }
};
