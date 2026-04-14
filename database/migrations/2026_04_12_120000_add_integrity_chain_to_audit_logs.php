<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_logs', 'hash_version')) {
                $table->string('hash_version', 20)->nullable()->after('metadata');
            }

            if (! Schema::hasColumn('audit_logs', 'previous_hash')) {
                $table->char('previous_hash', 64)->nullable()->after('hash_version');
            }

            if (! Schema::hasColumn('audit_logs', 'entry_hash')) {
                $table->char('entry_hash', 64)->nullable()->after('previous_hash');
            }

            if (! Schema::hasColumn('audit_logs', 'sealed_at')) {
                $table->timestamp('sealed_at', 6)->nullable()->after('entry_hash');
            }
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('entry_hash', 'audit_logs_entry_hash_idx');
            $table->index('previous_hash', 'audit_logs_previous_hash_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            try {
                $table->dropIndex('audit_logs_entry_hash_idx');
            } catch (\Throwable $e) {
                // no-op for databases where index does not exist
            }

            try {
                $table->dropIndex('audit_logs_previous_hash_idx');
            } catch (\Throwable $e) {
                // no-op for databases where index does not exist
            }
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('audit_logs', 'sealed_at')) {
                $table->dropColumn('sealed_at');
            }
            if (Schema::hasColumn('audit_logs', 'entry_hash')) {
                $table->dropColumn('entry_hash');
            }
            if (Schema::hasColumn('audit_logs', 'previous_hash')) {
                $table->dropColumn('previous_hash');
            }
            if (Schema::hasColumn('audit_logs', 'hash_version')) {
                $table->dropColumn('hash_version');
            }
        });
    }
};
