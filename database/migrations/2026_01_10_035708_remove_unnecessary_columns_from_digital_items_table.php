<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verificar si existe el índice único antes de intentar eliminarlo
        $indexExists = collect(DB::select("SHOW INDEXES FROM digital_items WHERE Key_name = 'digital_items_company_id_code_unique'"))->isNotEmpty();

        if ($indexExists) {
            Schema::table('digital_items', function (Blueprint $table) {
                $table->dropUnique(['company_id', 'code']);
            });
        }

        // Eliminar columnas solo si existen
        Schema::table('digital_items', function (Blueprint $table) {
            $columns = Schema::getColumnListing('digital_items');

            if (in_array('code', $columns)) {
                $table->dropColumn('code');
            }
            if (in_array('purchase_price', $columns)) {
                $table->dropColumn('purchase_price');
            }
            if (in_array('profit_margin', $columns)) {
                $table->dropColumn('profit_margin');
            }
            if (in_array('unit_value', $columns)) {
                $table->dropColumn('unit_value');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('digital_items', function (Blueprint $table) {
            // Restaurar columnas si es necesario hacer rollback
            $table->string('code')->nullable();
            $table->decimal('purchase_price', 10, 2)->default(0.00);
            $table->decimal('profit_margin', 5, 2)->default(0.00);
            $table->decimal('unit_value', 10, 2)->nullable();

            // Restaurar índice
            $table->index(['code']);
        });
    }
};
