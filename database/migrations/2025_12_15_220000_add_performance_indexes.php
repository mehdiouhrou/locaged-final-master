<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * AUDIT FIX #7: Add database indexes for performance optimization
 *
 * This migration adds indexes on frequently queried columns:
 * - documents.status (filtered in most queries)
 * - documents.expire_at (scheduled task queries)
 * - documents.created_by (user document filtering)
 * - documents.created_at (date range queries)
 * - audit_logs compound index (document_id, occurred_at)
 * - document_versions.ocr_text (FULLTEXT for search)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes to documents table (idempotent: partial runs can leave indexes in place)
        Schema::table('documents', function (Blueprint $table) {
            if (! $this->indexExists('documents', 'idx_documents_status')) {
                $table->index('status', 'idx_documents_status');
            }
            if (! $this->indexExists('documents', 'idx_documents_expire_at')) {
                $table->index('expire_at', 'idx_documents_expire_at');
            }
            if (! $this->indexExists('documents', 'idx_documents_created_by')) {
                $table->index('created_by', 'idx_documents_created_by');
            }
            if (! $this->indexExists('documents', 'idx_documents_created_at')) {
                $table->index('created_at', 'idx_documents_created_at');
            }
            if (! $this->indexExists('documents', 'idx_documents_status_created')) {
                $table->index(['status', 'created_at'], 'idx_documents_status_created');
            }
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            if (! $this->indexExists('audit_logs', 'idx_audit_logs_doc_occurred')) {
                $table->index(['document_id', 'occurred_at'], 'idx_audit_logs_doc_occurred');
            }
            if (! $this->indexExists('audit_logs', 'idx_audit_logs_occurred_at')) {
                $table->index('occurred_at', 'idx_audit_logs_occurred_at');
            }
        });

        // FULLTEXT is MySQL/MariaDB only; SQLite and PostgreSQL do not support this ALTER form.
        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)
            && ! $this->indexExists('document_versions', 'idx_document_versions_ocr_fulltext')) {
            DB::statement('ALTER TABLE document_versions ADD FULLTEXT INDEX idx_document_versions_ocr_fulltext (ocr_text)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if ($this->indexExists('documents', 'idx_documents_status')) {
                $table->dropIndex('idx_documents_status');
            }
            if ($this->indexExists('documents', 'idx_documents_expire_at')) {
                $table->dropIndex('idx_documents_expire_at');
            }
            if ($this->indexExists('documents', 'idx_documents_created_by')) {
                $table->dropIndex('idx_documents_created_by');
            }
            if ($this->indexExists('documents', 'idx_documents_created_at')) {
                $table->dropIndex('idx_documents_created_at');
            }
            if ($this->indexExists('documents', 'idx_documents_status_created')) {
                $table->dropIndex('idx_documents_status_created');
            }
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            if ($this->indexExists('audit_logs', 'idx_audit_logs_doc_occurred')) {
                $table->dropIndex('idx_audit_logs_doc_occurred');
            }
            if ($this->indexExists('audit_logs', 'idx_audit_logs_occurred_at')) {
                $table->dropIndex('idx_audit_logs_occurred_at');
            }
        });

        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)
            && $this->indexExists('document_versions', 'idx_document_versions_ocr_fulltext')) {
            DB::statement('ALTER TABLE document_versions DROP INDEX idx_document_versions_ocr_fulltext');
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            foreach (DB::select('PRAGMA index_list('.str_replace(['"', "'", ';'], '', $table).')') as $row) {
                if (isset($row->name) && strcasecmp((string) $row->name, $indexName) === 0) {
                    return true;
                }
            }

            return false;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $database = Schema::getConnection()->getDatabaseName();
            $row = DB::selectOne(
                'SELECT COUNT(*) AS cnt FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
                [$database, $table, $indexName]
            );

            return $row && (int) $row->cnt > 0;
        }

        if ($driver === 'pgsql') {
            $row = DB::selectOne(
                'SELECT COUNT(*) AS cnt FROM pg_indexes WHERE schemaname = current_schema() AND tablename = ? AND indexname = ?',
                [$table, $indexName]
            );

            return $row && (int) $row->cnt > 0;
        }

        return false;
    }
};
