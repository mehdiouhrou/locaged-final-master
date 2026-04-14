<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catégories sans rattachement structure (pôle / sous-département) : unicité globale sur le nom.
     */
    public function up(): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        if (Schema::hasColumn('categories', 'department_id') || Schema::hasColumn('categories', 'sub_department_id')) {
            try {
                Schema::table('categories', function (Blueprint $table) {
                    $table->dropUnique('categories_dept_subdept_name_unique');
                });
            } catch (\Throwable $e) {
                //
            }

            // Résoudre les homonymes avant unique sur name
            $dupNames = DB::table('categories')
                ->select('name')
                ->groupBy('name')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('name');

            foreach ($dupNames as $name) {
                $ids = DB::table('categories')->where('name', $name)->orderBy('id')->pluck('id');
                foreach ($ids->slice(1) as $id) {
                    DB::table('categories')->where('id', $id)->update([
                        'name' => $name.' ('.$id.')',
                        'updated_at' => now(),
                    ]);
                }
            }

            Schema::table('categories', function (Blueprint $table) {
                if (Schema::hasColumn('categories', 'department_id')) {
                    try {
                        $table->dropForeign(['department_id']);
                    } catch (\Throwable $e) {
                        //
                    }
                }
                if (Schema::hasColumn('categories', 'sub_department_id')) {
                    try {
                        $table->dropForeign(['sub_department_id']);
                    } catch (\Throwable $e) {
                        //
                    }
                }
            });

            Schema::table('categories', function (Blueprint $table) {
                if (Schema::hasColumn('categories', 'department_id')) {
                    $table->dropColumn('department_id');
                }
                if (Schema::hasColumn('categories', 'sub_department_id')) {
                    $table->dropColumn('sub_department_id');
                }
            });
        }

        Schema::table('categories', function (Blueprint $table) {
            if (! $this->indexExists('categories', 'categories_name_unique')) {
                $table->unique('name', 'categories_name_unique');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            try {
                $table->dropUnique('categories_name_unique');
            } catch (\Throwable $e) {
                //
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('sub_department_id')->nullable()->constrained('sub_departments')->nullOnDelete();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->unique(
                ['department_id', 'sub_department_id', 'name'],
                'categories_dept_subdept_name_unique'
            );
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        if ($driver === 'sqlite') {
            $indexes = $connection->select("PRAGMA index_list('{$table}')");

            return collect($indexes)->contains(fn ($row) => ($row->name ?? null) === $indexName);
        }

        $db = $connection->getDatabaseName();

        $r = $connection->select(
            'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$db, $table, $indexName]
        );

        return isset($r[0]) && (int) $r[0]->c > 0;
    }
};
