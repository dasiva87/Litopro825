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
        // Índices para documents - filtrado frecuente por status
        Schema::table('documents', function (Blueprint $table) {
            if (!$this->indexExists('documents', 'documents_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('documents', 'documents_company_id_status_index')) {
                $table->index(['company_id', 'status']);
            }
        });

        // Índices para stock_movements - reportes por fecha
        Schema::table('stock_movements', function (Blueprint $table) {
            if (!$this->indexExists('stock_movements', 'stock_movements_created_at_index')) {
                $table->index('created_at');
            }
            if (!$this->indexExists('stock_movements', 'stock_movements_company_id_created_at_index')) {
                $table->index(['company_id', 'created_at']);
            }
            if (!$this->indexExists('stock_movements', 'stock_movements_type_created_at_index')) {
                $table->index(['type', 'created_at']);
            }
        });

        // Índices para purchase_orders - dashboard widgets
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!$this->indexExists('purchase_orders', 'purchase_orders_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('purchase_orders', 'purchase_orders_company_id_status_index')) {
                $table->index(['company_id', 'status']);
            }
            if (!$this->indexExists('purchase_orders', 'purchase_orders_supplier_company_id_status_index')) {
                $table->index(['supplier_company_id', 'status']);
            }
        });

        // Índices para products - queries de stock
        Schema::table('products', function (Blueprint $table) {
            if (!$this->indexExists('products', 'products_company_id_active_index')) {
                $table->index(['company_id', 'active']);
            }
        });

        // Índices para papers - queries de stock
        Schema::table('papers', function (Blueprint $table) {
            if (!$this->indexExists('papers', 'papers_company_id_is_active_index')) {
                $table->index(['company_id', 'is_active']);
            }
        });

        // Índices para stock_alerts - queries frecuentes
        Schema::table('stock_alerts', function (Blueprint $table) {
            if (!$this->indexExists('stock_alerts', 'stock_alerts_severity_status_index')) {
                $table->index(['severity', 'status']);
            }
            if (!$this->indexExists('stock_alerts', 'stock_alerts_company_id_status_index')) {
                $table->index(['company_id', 'status']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('documents_status_index');
            $table->dropIndex('documents_company_id_status_index');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('stock_movements_created_at_index');
            $table->dropIndex('stock_movements_company_id_created_at_index');
            $table->dropIndex('stock_movements_type_created_at_index');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex('purchase_orders_status_index');
            $table->dropIndex('purchase_orders_company_id_status_index');
            $table->dropIndex('purchase_orders_supplier_company_id_status_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_company_id_active_index');
        });

        Schema::table('papers', function (Blueprint $table) {
            $table->dropIndex('papers_company_id_is_active_index');
        });

        Schema::table('stock_alerts', function (Blueprint $table) {
            $table->dropIndex('stock_alerts_severity_status_index');
            $table->dropIndex('stock_alerts_company_id_status_index');
        });
    }

    /**
     * Helper method to check if index exists
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = Schema::getIndexes($table);

        foreach ($indexes as $index) {
            if ($index['name'] === $indexName) {
                return true;
            }
        }

        return false;
    }
};
