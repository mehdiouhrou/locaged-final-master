<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('destruction_certificates', function (Blueprint $table) {
            $table->string('proof_package_path')->nullable()->after('pdf_path');
            $table->string('proof_pdf_sha256', 64)->nullable()->after('proof_package_path');
            $table->string('proof_package_sha256', 64)->nullable()->after('proof_pdf_sha256');
            $table->json('proof_manifest')->nullable()->after('proof_package_sha256');
            $table->json('proof_signature')->nullable()->after('proof_manifest');
            $table->json('proof_archive')->nullable()->after('proof_signature');
            $table->timestamp('proof_generated_at', 6)->nullable()->after('proof_archive');
        });
    }

    public function down(): void
    {
        Schema::table('destruction_certificates', function (Blueprint $table) {
            $table->dropColumn([
                'proof_package_path',
                'proof_pdf_sha256',
                'proof_package_sha256',
                'proof_manifest',
                'proof_signature',
                'proof_archive',
                'proof_generated_at',
            ]);
        });
    }
};
