<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Refactoriza la arquitectura de Purchase Orders:
     * - Elimina order_type y document_id de purchase_orders
     * - Crea tabla pivot document_item_purchase_order (many-to-many)
     * - Migra datos de purchase_order_items a la nueva tabla pivot
     * - Elimina purchase_order_items
     */
    public function up(): void
    {
        // PASO 1: Crear tabla pivot many-to-many (solo si no existe)
        if (!Schema::hasTable('document_item_purchase_order')) {
            Schema::create('document_item_purchase_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');

            // Datos específicos de la relación
            $table->decimal('quantity_ordered', 10, 2)->default(1);
            $table->decimal('unit_price', 10, 4)->nullable();
            $table->decimal('total_price', 12, 2)->nullable();
            $table->enum('status', ['pending', 'confirmed', 'received', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();

            $table->timestamps();

            // Índices para performance
            $table->index(['purchase_order_id', 'status']);
            $table->index(['document_item_id']);

            // Constraint: un item no puede estar duplicado en la misma orden
            $table->unique(['document_item_id', 'purchase_order_id'], 'unique_item_per_order');
            });
        }

        // PASO 2: Migrar datos de purchase_order_items a document_item_purchase_order
        if (Schema::hasTable('purchase_order_items')) {
            DB::statement("
                INSERT INTO document_item_purchase_order (
                    document_item_id,
                    purchase_order_id,
                    quantity_ordered,
                    unit_price,
                    total_price,
                    status,
                    notes,
                    created_at,
                    updated_at
                )
                SELECT
                    document_item_id,
                    purchase_order_id,
                    quantity_ordered,
                    unit_price,
                    total_price,
                    status,
                    notes,
                    created_at,
                    updated_at
                FROM purchase_order_items
                WHERE document_item_id IS NOT NULL
            ");
        }

        // PASO 3: Crear backup de purchase_order_items antes de eliminar
        if (Schema::hasTable('purchase_order_items')) {
            Schema::rename('purchase_order_items', 'purchase_order_items_backup');
        }

        // PASO 4: Eliminar columnas de purchase_orders
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Eliminar foreign key y columna document_id
            if (Schema::hasColumn('purchase_orders', 'document_id')) {
                $table->dropForeign(['document_id']);
                $table->dropColumn('document_id');
            }

            // Eliminar columna order_type
            if (Schema::hasColumn('purchase_orders', 'order_type')) {
                $table->dropColumn('order_type');
            }
        });

        // PASO 5: Agregar índice a document_items.order_status si no existe
        try {
            Schema::table('document_items', function (Blueprint $table) {
                $table->index(['order_status'], 'document_items_order_status_index');
            });
        } catch (\Exception $e) {
            // Índice ya existe, continuar
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // PASO 1: Restaurar columnas en purchase_orders
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('document_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('order_type', ['papel', 'producto'])->nullable();
        });

        // PASO 2: Restaurar purchase_order_items desde backup
        if (Schema::hasTable('purchase_order_items_backup')) {
            Schema::rename('purchase_order_items_backup', 'purchase_order_items');
        } else {
            // Recrear tabla si no hay backup
            Schema::create('purchase_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
                $table->foreignId('document_item_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('item_type')->nullable();
                $table->decimal('quantity_ordered', 10, 2);
                $table->decimal('unit_price', 10, 4)->nullable();
                $table->decimal('total_price', 12, 2)->nullable();
                $table->integer('paper_sheets_needed')->nullable();
                $table->string('paper_cut_size')->nullable();
                $table->string('paper_type')->nullable();
                $table->string('product_name')->nullable();
                $table->string('product_code')->nullable();
                $table->enum('status', ['pending', 'confirmed', 'received', 'cancelled'])->default('pending');
                $table->text('notes')->nullable();
                $table->timestamps();
            });

            // Migrar datos de vuelta
            DB::statement("
                INSERT INTO purchase_order_items (
                    purchase_order_id,
                    document_item_id,
                    quantity_ordered,
                    unit_price,
                    total_price,
                    status,
                    notes,
                    created_at,
                    updated_at
                )
                SELECT
                    purchase_order_id,
                    document_item_id,
                    quantity_ordered,
                    unit_price,
                    total_price,
                    status,
                    notes,
                    created_at,
                    updated_at
                FROM document_item_purchase_order
            ");
        }

        // PASO 3: Eliminar tabla pivot
        Schema::dropIfExists('document_item_purchase_order');

        // PASO 4: Eliminar índice de document_items si existe
        try {
            Schema::table('document_items', function (Blueprint $table) {
                $table->dropIndex('document_items_order_status_index');
            });
        } catch (\Exception $e) {
            // Índice no existe, continuar
        }
    }
};
