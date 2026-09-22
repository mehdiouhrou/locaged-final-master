<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE document_movements MODIFY document_id BIGINT UNSIGNED NULL');

        Schema::table('document_movements', function (Blueprint $table) {
            $table->foreignId('box_id')->nullable()->after('document_id')->constrained('boxes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('document_movements', function (Blueprint $table) {
            $table->dropForeign(['box_id']);
            $table->dropColumn('box_id');
        });

        DB::statement('ALTER TABLE document_movements MODIFY document_id BIGINT UNSIGNED NOT NULL');
    }
};
