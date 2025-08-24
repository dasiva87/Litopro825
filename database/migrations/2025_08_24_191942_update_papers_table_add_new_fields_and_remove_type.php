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
            // Agregar nuevos campos
            $table->string('code')->after('company_id'); // Código del papel
            $table->decimal('price', 10, 4)->default(0)->after('cost_per_sheet'); // Precio de venta
            $table->integer('stock')->default(0)->after('price'); // Stock disponible
            $table->boolean('is_own')->default(true)->after('stock'); // Es papel propio
            $table->foreignId('supplier_id')->nullable()->after('is_own')->constrained('contacts')->nullOnDelete(); // Proveedor
            
            // Eliminar campo type
            $table->dropColumn('type');
            
            // Agregar índice único para code por company
            $table->unique(['company_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('papers', function (Blueprint $table) {
            // Restaurar campo type
            $table->string('type')->nullable()->after('name');
            
            // Eliminar nuevos campos
            $table->dropUnique(['company_id', 'code']);
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['code', 'price', 'stock', 'is_own', 'supplier_id']);
        });
    }
};
