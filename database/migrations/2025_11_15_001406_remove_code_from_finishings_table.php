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
            // Eliminar el índice único primero
            $table->dropUnique('finishings_code_unique');
            // Luego eliminar la columna
            $table->dropColumn('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finishings', function (Blueprint $table) {
            // Restaurar la columna
            $table->string('code')->after('supplier_id')->nullable();
            // Restaurar el índice único
            $table->unique('code', 'finishings_code_unique');
        });
    }
};
