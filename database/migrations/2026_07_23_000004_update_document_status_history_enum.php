<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE document_status_history MODIFY from_status ENUM(
            'pending',
            'declined',
            'approved',
            'archived',
            'destroyed',
            'brouillon',
            'en_relecture',
            'valide'
        ) NOT NULL");

        DB::statement("ALTER TABLE document_status_history MODIFY to_status ENUM(
            'pending',
            'declined',
            'approved',
            'archived',
            'destroyed',
            'brouillon',
            'en_relecture',
            'valide'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE document_status_history MODIFY from_status ENUM(
            'pending',
            'declined',
            'approved',
            'locked',
            'unlocked',
            'moved',
            'archived',
            'destroyed'
        ) NOT NULL");

        DB::statement("ALTER TABLE document_status_history MODIFY to_status ENUM(
            'pending',
            'declined',
            'approved',
            'locked',
            'unlocked',
            'moved',
            'archived',
            'destroyed'
        ) NOT NULL");
    }
};
