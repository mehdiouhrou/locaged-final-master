<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflow_rules', function (Blueprint $table) {
            if (! Schema::hasColumn('workflow_rules', 'approver_role')) {
                $table->string('approver_role', 120)->nullable()->after('level');
            }
        });
    }

    public function down(): void
    {
        Schema::table('workflow_rules', function (Blueprint $table) {
            if (Schema::hasColumn('workflow_rules', 'approver_role')) {
                $table->dropColumn('approver_role');
            }
        });
    }
};
