<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_movements')) {
            return;
        }

        Schema::table('document_movements', function (Blueprint $table) {
            if (! Schema::hasColumn('document_movements', 'borrowed_by_user_id')) {
                $table->foreignId('borrowed_by_user_id')->nullable()->after('moved_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('document_movements', 'borrower_name')) {
                $table->string('borrower_name')->nullable()->after('borrowed_by_user_id');
            }
            if (! Schema::hasColumn('document_movements', 'due_at')) {
                $table->timestamp('due_at', 6)->nullable()->after('borrower_name');
            }
            if (! Schema::hasColumn('document_movements', 'returned_at')) {
                $table->timestamp('returned_at', 6)->nullable()->after('due_at');
            }
            if (! Schema::hasColumn('document_movements', 'returned_by_user_id')) {
                $table->foreignId('returned_by_user_id')->nullable()->after('returned_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('document_movements', 'movement_note')) {
                $table->text('movement_note')->nullable()->after('returned_by_user_id');
            }
            if (! Schema::hasColumn('document_movements', 'return_note')) {
                $table->text('return_note')->nullable()->after('movement_note');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('document_movements')) {
            return;
        }

        Schema::table('document_movements', function (Blueprint $table) {
            if (Schema::hasColumn('document_movements', 'return_note')) {
                $table->dropColumn('return_note');
            }
            if (Schema::hasColumn('document_movements', 'movement_note')) {
                $table->dropColumn('movement_note');
            }
            if (Schema::hasColumn('document_movements', 'returned_by_user_id')) {
                $table->dropConstrainedForeignId('returned_by_user_id');
            }
            if (Schema::hasColumn('document_movements', 'returned_at')) {
                $table->dropColumn('returned_at');
            }
            if (Schema::hasColumn('document_movements', 'due_at')) {
                $table->dropColumn('due_at');
            }
            if (Schema::hasColumn('document_movements', 'borrower_name')) {
                $table->dropColumn('borrower_name');
            }
            if (Schema::hasColumn('document_movements', 'borrowed_by_user_id')) {
                $table->dropConstrainedForeignId('borrowed_by_user_id');
            }
        });
    }
};
