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
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Agregar soporte para contactos proveedores
            $table->foreignId('supplier_id')->nullable()->after('supplier_company_id')->constrained('contacts')->nullOnDelete();

            // Hacer supplier_company_id nullable ya que ahora puede ser company O contact
            $table->foreignId('supplier_company_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn('supplier_id');

            // Revertir supplier_company_id a NOT NULL si es necesario
            // $table->foreignId('supplier_company_id')->nullable(false)->change();
        });
    }
};
