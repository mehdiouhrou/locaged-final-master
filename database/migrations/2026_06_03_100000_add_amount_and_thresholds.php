<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->decimal('amount', 12, 2)->nullable()->after('metadata');
        });

        Schema::table('workflow_rules', function (Blueprint $table) {
            $table->decimal('min_amount', 12, 2)->nullable()->after('level');
            $table->decimal('max_amount', 12, 2)->nullable()->after('min_amount');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('amount');
        });

        Schema::table('workflow_rules', function (Blueprint $table) {
            $table->dropColumn(['min_amount', 'max_amount']);
        });
    }
};
