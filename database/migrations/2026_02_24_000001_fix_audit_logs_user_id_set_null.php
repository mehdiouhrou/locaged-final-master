<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make audit_logs.user_id nullable and change onDelete to SET NULL
     * so that audit history is preserved when a user account is deleted.
     */
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // Drop the existing foreign key constraint first
            $table->dropForeign(['user_id']);

            // Make the column nullable so SET NULL can work
            $table->unsignedBigInteger('user_id')->nullable()->change();

            // Re-add the foreign key with SET NULL on delete
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migration — restore CASCADE and NOT NULL.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);

            // Restore NOT NULL (set a placeholder value where null)
            \DB::statement('UPDATE audit_logs SET user_id = 0 WHERE user_id IS NULL');
            $table->unsignedBigInteger('user_id')->nullable(false)->change();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }
};
