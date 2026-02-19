<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('finishings', function (Blueprint $table) {
            // Eliminar índice único primero
            $table->dropUnique('finishings_company_code_unique');
            // Eliminar columna code
            $table->dropColumn('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finishings', function (Blueprint $table) {
            $table->string('code')->nullable()->after('supplier_id');
            $table->unique(['company_id', 'code'], 'finishings_company_code_unique');
        });
    }
};
