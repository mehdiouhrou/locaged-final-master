<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('authentication_logs', function (Blueprint $table) {
            $table->string('user_name')->nullable()->after('user_id');
        });

        // Populate historical authentication logs with user names
        \DB::statement('
            UPDATE authentication_logs 
            LEFT JOIN users ON authentication_logs.user_id = users.id 
            SET authentication_logs.user_name = users.full_name 
            WHERE authentication_logs.user_id IS NOT NULL AND authentication_logs.user_name IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('authentication_logs', function (Blueprint $table) {
            $table->dropColumn('user_name');
        });
    }
};
