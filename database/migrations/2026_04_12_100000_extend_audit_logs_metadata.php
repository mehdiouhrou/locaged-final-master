<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enrichit audit_logs pour la traçabilité V2 (sans casser les FK document/version existantes).
     */
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_logs', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }
            if (! Schema::hasColumn('audit_logs', 'metadata')) {
                $table->json('metadata')->nullable()->after('user_agent');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('audit_logs', 'metadata')) {
                $table->dropColumn('metadata');
            }
            if (Schema::hasColumn('audit_logs', 'user_agent')) {
                $table->dropColumn('user_agent');
            }
        });
    }
};
