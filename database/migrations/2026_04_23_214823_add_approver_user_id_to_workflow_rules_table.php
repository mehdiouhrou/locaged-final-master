<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflow_rules', function (Blueprint $table) {
            $table->foreignId('approver_user_id')->nullable()->after('approver_role')->constrained('users')->onDelete('set null');
            $table->boolean('is_active')->default(true)->after('to_status');
        });
    }

    public function down(): void
    {
        Schema::table('workflow_rules', function (Blueprint $table) {
            $table->dropForeign(['approver_user_id']);
            $table->dropColumn(['approver_user_id', 'is_active']);
        });
    }
};
