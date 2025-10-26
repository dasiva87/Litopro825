<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega campos para el tamaño de corte del papel en Purchase Orders
     * Ejemplo: Membrete carta = 21.5 x 28 cm de corte
     */
    public function up(): void
    {
        Schema::table('document_item_purchase_order', function (Blueprint $table) {
            $table->decimal('cut_width', 8, 2)->nullable()->after('sheets_quantity')
                ->comment('Ancho del corte en centímetros');
            $table->decimal('cut_height', 8, 2)->nullable()->after('cut_width')
                ->comment('Alto del corte en centímetros');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_item_purchase_order', function (Blueprint $table) {
            $table->dropColumn(['cut_width', 'cut_height']);
        });
    }
};
