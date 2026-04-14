<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update documents to point to the first subcategory of their category
        // Note: category_id was already renamed to subcategory_id in previous migration
        // So subcategory_id currently contains category IDs, we need to convert them to subcategory IDs
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: no "UPDATE ... INNER JOIN" (MySQL-only); use correlated subquery.
            DB::statement('
                UPDATE documents
                SET subcategory_id = (
                    SELECT s.id FROM subcategories s
                    WHERE s.category_id = documents.subcategory_id
                    ORDER BY s.id ASC
                    LIMIT 1
                )
                WHERE subcategory_id IS NOT NULL
                AND EXISTS (
                    SELECT 1 FROM subcategories s
                    WHERE s.category_id = documents.subcategory_id
                )
            ');
        } else {
            DB::statement('
                UPDATE documents d
                INNER JOIN subcategories s ON s.category_id = d.subcategory_id
                SET d.subcategory_id = s.id
                WHERE d.subcategory_id IS NOT NULL
                AND s.id = (
                    SELECT id FROM subcategories
                    WHERE category_id = d.subcategory_id
                    ORDER BY id ASC
                    LIMIT 1
                )
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration cannot be easily reversed as we lose the original category_id
        // The original category_id is lost when we rename the column
        // This would require a more complex rollback strategy
        throw new Exception('This migration cannot be reversed automatically. Manual intervention required.');
    }
};
