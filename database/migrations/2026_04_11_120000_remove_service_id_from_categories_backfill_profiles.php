<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Retire service_id des catégories (CDC profils) : backfill profile_category puis colonne + contraintes.
     */
    public function up(): void
    {
        if (! Schema::hasTable('categories') || ! Schema::hasColumn('categories', 'service_id')) {
            return;
        }

        if (Schema::hasTable('profile_service') && Schema::hasTable('profile_category')) {
            $pairs = DB::table('categories')
                ->whereNotNull('service_id')
                ->select(['id as category_id', 'service_id'])
                ->get();

            foreach ($pairs as $row) {
                $profileIds = DB::table('profile_service')
                    ->where('service_id', $row->service_id)
                    ->pluck('profile_id');

                foreach ($profileIds as $profileId) {
                    DB::table('profile_category')->updateOrInsert(
                        [
                            'profile_id' => $profileId,
                            'category_id' => $row->category_id,
                        ],
                        [
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }

        // D’abord la FK (sinon MySQL garde l’index unique pour la contrainte → erreur 1553).
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_service_id_name_unique');
        });

        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'service_id')) {
                $table->dropColumn('service_id');
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->unique(
                ['department_id', 'sub_department_id', 'name'],
                'categories_dept_subdept_name_unique'
            );
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            try {
                $table->dropUnique('categories_dept_subdept_name_unique');
            } catch (\Throwable $e) {
                //
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->after('sub_department_id')
                ->constrained('services')->nullOnDelete();
        });

        Schema::table('categories', function (Blueprint $table) {
            try {
                $table->unique(['service_id', 'name'], 'categories_service_id_name_unique');
            } catch (\Throwable $e) {
                //
            }
        });
    }
};
