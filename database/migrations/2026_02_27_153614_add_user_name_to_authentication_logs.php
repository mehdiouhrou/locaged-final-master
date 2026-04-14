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
        if (! Schema::hasColumn('authentication_logs', 'user_name')) {
            Schema::table('authentication_logs', function (Blueprint $table) {
                $table->string('user_name')->nullable()->after('user_id');
            });
        }

        // Populate historical authentication logs with user names (SQLite-compatible)
        DB::statement('
            UPDATE authentication_logs
            SET user_name = (
                SELECT u.full_name FROM users u WHERE u.id = authentication_logs.user_id
            )
            WHERE authentication_logs.user_id IS NOT NULL
            AND authentication_logs.user_name IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('authentication_logs', 'user_name')) {
            Schema::table('authentication_logs', function (Blueprint $table) {
                $table->dropColumn('user_name');
            });
        }
    }
};
