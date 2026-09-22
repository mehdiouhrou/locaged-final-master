<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_chain_state', function (Blueprint $table) {
            $table->id();
            $table->char('last_hash', 64)->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        DB::table('audit_chain_state')->insert([
            'id' => 1,
            'last_hash' => null,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_chain_state');
    }
};
