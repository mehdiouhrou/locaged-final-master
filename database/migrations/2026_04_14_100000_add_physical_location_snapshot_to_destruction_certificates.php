<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('destruction_certificates')) {
            return;
        }

        Schema::table('destruction_certificates', function (Blueprint $table) {
            if (! Schema::hasColumn('destruction_certificates', 'physical_location_snapshot')) {
                $table->json('physical_location_snapshot')->nullable()->after('manifest');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('destruction_certificates')) {
            return;
        }

        Schema::table('destruction_certificates', function (Blueprint $table) {
            if (Schema::hasColumn('destruction_certificates', 'physical_location_snapshot')) {
                $table->dropColumn('physical_location_snapshot');
            }
        });
    }
};
