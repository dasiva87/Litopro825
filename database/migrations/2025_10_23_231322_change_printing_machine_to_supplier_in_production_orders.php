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
        // Verificar si la columna printing_machine_id existe antes de modificarla
        if (Schema::hasColumn('production_orders', 'printing_machine_id')) {
            Schema::table('production_orders', function (Blueprint $table) {
                // Drop foreign key and index for printing_machine_id
                $table->dropForeign(['printing_machine_id']);
                $table->dropIndex(['printing_machine_id']);

                // Rename column from printing_machine_id to supplier_id
                $table->renameColumn('printing_machine_id', 'supplier_id');
            });

            // Add foreign key constraint to contacts table
            Schema::table('production_orders', function (Blueprint $table) {
                $table->foreign('supplier_id')
                    ->references('id')
                    ->on('contacts')
                    ->nullOnDelete();

                $table->index('supplier_id');
            });
        }
        // Si no existe printing_machine_id, significa que ya está como supplier_id o es una BD nueva
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Solo intentar revertir si la columna supplier_id existe
        if (Schema::hasColumn('production_orders', 'supplier_id')) {
            Schema::table('production_orders', function (Blueprint $table) {
                // Drop foreign key and index for supplier_id
                $table->dropForeign(['supplier_id']);
                $table->dropIndex(['supplier_id']);

                // Rename column back to printing_machine_id
                $table->renameColumn('supplier_id', 'printing_machine_id');
            });

            // Add back foreign key constraint to printing_machines table
            Schema::table('production_orders', function (Blueprint $table) {
                $table->foreign('printing_machine_id')
                    ->references('id')
                    ->on('printing_machines')
                    ->nullOnDelete();

                $table->index('printing_machine_id');
            });
        }
    }
};
