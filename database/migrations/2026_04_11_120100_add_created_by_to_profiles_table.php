<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('profiles')) {
            return;
        }

        Schema::table('profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('profiles', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('description')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('profiles') || ! Schema::hasColumn('profiles', 'created_by')) {
            return;
        }

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });
    }
};
