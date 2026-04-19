<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permet de conserver une trace d’audit après suppression définitive du document
     * (FK document / version sans ligne cible).
     */
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['document_id']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['version_id']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('document_id')->nullable()->change();
            $table->unsignedBigInteger('version_id')->nullable()->change();
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreign('document_id')
                ->references('id')
                ->on('documents')
                ->nullOnDelete();

            $table->foreign('version_id')
                ->references('id')
                ->on('document_versions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['document_id']);
            $table->dropForeign(['version_id']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('document_id')->nullable(false)->change();
            $table->unsignedBigInteger('version_id')->nullable(false)->change();
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreign('document_id')
                ->references('id')
                ->on('documents')
                ->onDelete('cascade');

            $table->foreign('version_id')
                ->references('id')
                ->on('document_versions')
                ->onDelete('cascade');
        });
    }
};
