<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflow_rules', function (Blueprint $table) {
            $table->dropUnique('uniq_rule');
        });

        Schema::table('workflow_rules', function (Blueprint $table) {
            $table->foreignId('category_id')
                ->nullable()
                ->after('department_id')
                ->constrained('categories')
                ->nullOnDelete();
            $table->unsignedTinyInteger('level')->default(1)->after('category_id');
            $table->index(['department_id', 'from_status', 'to_status'], 'workflow_rules_dept_status_idx');
            $table->index(
                ['department_id', 'category_id', 'from_status', 'to_status'],
                'workflow_rules_dept_cat_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('workflow_rules', function (Blueprint $table) {
            $table->dropIndex('workflow_rules_dept_status_idx');
            $table->dropIndex('workflow_rules_dept_cat_status_idx');
            $table->dropForeign(['category_id']);
            $table->dropColumn(['category_id', 'level']);
        });

        Schema::table('workflow_rules', function (Blueprint $table) {
            $table->unique(['department_id', 'from_status', 'to_status'], 'uniq_rule');
        });
    }
};
