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
        Schema::table('papers', function (Blueprint $table) {
            // Eliminar el índice único compuesto primero
            $table->dropUnique('papers_company_id_code_unique');
            // Hacer la columna nullable
            $table->string('code', 50)->nullable()->change();
            // Recrear el índice único compuesto (permitirá múltiples NULL)
            $table->unique(['company_id', 'code'], 'papers_company_id_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('papers', function (Blueprint $table) {
            // Eliminar el índice único compuesto
            $table->dropUnique('papers_company_id_code_unique');
            // Hacer la columna no nullable nuevamente
            $table->string('code', 50)->nullable(false)->change();
            // Recrear el índice único compuesto
            $table->unique(['company_id', 'code'], 'papers_company_id_code_unique');
        });
    }
};
