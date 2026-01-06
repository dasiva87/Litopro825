<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * TERMINOLOGY CLARIFICATION:
     * - PLIEGO (Paper Sheet): Papel como viene del proveedor (ej: 70x100cm)
     * - HOJA (Printing Form): Corte del pliego donde se imprime (ej: 50x70cm - tamaño máquina)
     * - COPIA (Copy/Unit): Producto final (ej: 10x15cm volante)
     *
     * FLOW: PLIEGO → [divisor] → HOJAS → [mounting] → COPIAS
     */
    public function up(): void
    {
        Schema::table('simple_items', function (Blueprint $table) {
            // RENOMBRAR COLUMNAS EXISTENTES

            // mounting_quantity → copies_per_form (copias que caben en una hoja)
            $table->renameColumn('mounting_quantity', 'copies_per_form');

            // paper_cuts_h → cuts_per_form_h (cortes horizontales en la hoja)
            $table->renameColumn('paper_cuts_h', 'cuts_per_form_h');

            // paper_cuts_v → cuts_per_form_v (cortes verticales en la hoja)
            $table->renameColumn('paper_cuts_v', 'cuts_per_form_v');
        });

        Schema::table('simple_items', function (Blueprint $table) {
            // AGREGAR NUEVAS COLUMNAS

            // Hojas por pliego (divisor: cuántas hojas salen de un pliego)
            $table->integer('forms_per_paper_sheet')->default(0)->after('copies_per_form');

            // Pliegos necesarios (cantidad de pliegos a comprar)
            $table->integer('paper_sheets_needed')->default(0)->after('forms_per_paper_sheet');

            // Hojas a imprimir (total de hojas/formas necesarias)
            $table->integer('printing_forms_needed')->default(0)->after('paper_sheets_needed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('simple_items', function (Blueprint $table) {
            // ELIMINAR COLUMNAS AGREGADAS
            $table->dropColumn(['forms_per_paper_sheet', 'paper_sheets_needed', 'printing_forms_needed']);
        });

        Schema::table('simple_items', function (Blueprint $table) {
            // REVERTIR NOMBRES
            $table->renameColumn('copies_per_form', 'mounting_quantity');
            $table->renameColumn('cuts_per_form_h', 'paper_cuts_h');
            $table->renameColumn('cuts_per_form_v', 'paper_cuts_v');
        });
    }
};
