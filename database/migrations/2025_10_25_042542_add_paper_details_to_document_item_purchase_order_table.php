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
        Schema::table('document_item_purchase_order', function (Blueprint $table) {
            // Eliminar el constraint único para permitir múltiples filas por item (para revistas con diferentes papeles)
            $table->dropUnique('unique_item_per_order');

            // Agregar campos para identificar el papel específico (útil para revistas)
            $table->unsignedBigInteger('paper_id')->nullable()->after('document_item_id');
            $table->string('paper_description')->nullable()->after('paper_id');
            $table->integer('sheets_quantity')->default(0)->after('quantity_ordered');

            // Crear nuevo índice compuesto que incluye paper_id
            $table->unique(['document_item_id', 'purchase_order_id', 'paper_id'], 'unique_item_paper_per_order');

            // Foreign key para paper
            $table->foreign('paper_id')->references('id')->on('papers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_item_purchase_order', function (Blueprint $table) {
            // Eliminar foreign key y campos agregados
            $table->dropForeign(['paper_id']);
            $table->dropUnique('unique_item_paper_per_order');
            $table->dropColumn(['paper_id', 'paper_description', 'sheets_quantity']);

            // Restaurar constraint único original
            $table->unique(['document_item_id', 'purchase_order_id'], 'unique_item_per_order');
        });
    }
};
