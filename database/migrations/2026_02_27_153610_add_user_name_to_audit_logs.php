<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('audit_logs', 'user_name')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->string('user_name')->nullable()->after('user_id');
            });
        }

        // Populate historical audit logs with user names (subquery: works on SQLite + MySQL; no UPDATE…LEFT JOIN)
        DB::statement('
            UPDATE audit_logs
            SET user_name = (
                SELECT u.full_name FROM users u WHERE u.id = audit_logs.user_id
            )
            WHERE audit_logs.user_id IS NOT NULL
            AND audit_logs.user_name IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('audit_logs', 'user_name')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropColumn('user_name');
            });
        }
    }
};
