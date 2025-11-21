<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * OBJETIVO: Permitir múltiples procesos (impresión + N acabados) para el mismo DocumentItem
     * en una ProductionOrder. Cada acabado se registra como un proceso separado con sus parámetros.
     */
    public function up(): void
    {
        // 1. Eliminar constraint UNIQUE si existe
        $constraintExists = \DB::select("
            SELECT COUNT(*) as count
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'document_item_production_order'
            AND CONSTRAINT_NAME = 'dipr_doc_prod_unique'
        ");

        if ($constraintExists[0]->count > 0) {
            Schema::table('document_item_production_order', function (Blueprint $table) {
                $table->dropUnique('dipr_doc_prod_unique');
            });
        }

        // 2. Agregar nuevos campos
        Schema::table('document_item_production_order', function (Blueprint $table) {
            // Verificar si los campos ya existen antes de agregarlos
            if (!Schema::hasColumn('document_item_production_order', 'document_item_finishing_id')) {
                $table->foreignId('document_item_finishing_id')
                    ->nullable()
                    ->after('document_item_id')
                    ->constrained('document_item_finishings', indexName: 'dipr_doc_item_finishing_fk')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('document_item_production_order', 'finishing_quantity')) {
                $table->decimal('finishing_quantity', 12, 2)->nullable()->after('process_description');
            }

            if (!Schema::hasColumn('document_item_production_order', 'finishing_width')) {
                $table->decimal('finishing_width', 8, 2)->nullable()->after('finishing_quantity');
            }

            if (!Schema::hasColumn('document_item_production_order', 'finishing_height')) {
                $table->decimal('finishing_height', 8, 2)->nullable()->after('finishing_width');
            }

            if (!Schema::hasColumn('document_item_production_order', 'finishing_unit')) {
                $table->string('finishing_unit')->nullable()->after('finishing_height');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_item_production_order', function (Blueprint $table) {
            // Restaurar unique constraint
            $table->unique(['document_item_id', 'production_order_id'], 'dipr_doc_prod_unique');

            // Eliminar campos de acabados
            $table->dropForeign(['document_item_finishing_id']);
            $table->dropColumn([
                'document_item_finishing_id',
                'finishing_quantity',
                'finishing_width',
                'finishing_height',
                'finishing_unit',
            ]);
        });
    }
};
