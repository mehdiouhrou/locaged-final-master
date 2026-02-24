<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Change authentication_logs.user_id onDelete from CASCADE to SET NULL
     * so that login/logout history is preserved when a user account is deleted.
     * The column is already nullable, so only the foreign key needs updating.
     */
    public function up(): void
    {
        Schema::table('authentication_logs', function (Blueprint $table) {
            // Drop the existing CASCADE foreign key
            $table->dropForeign(['user_id']);

            // Re-add with SET NULL on delete
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migration — restore CASCADE.
     */
    public function down(): void
    {
        Schema::table('authentication_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }
};
