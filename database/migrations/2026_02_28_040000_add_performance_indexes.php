<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Verifica si un índice existe en una tabla
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();
        $result = DB::select("
            SELECT COUNT(*) as count
            FROM information_schema.statistics
            WHERE table_schema = ?
            AND table_name = ?
            AND index_name = ?
        ", [$database, $table, $indexName]);

        return $result[0]->count > 0;
    }

    /**
     * Run the migrations.
     *
     * Agrega índices para mejorar el rendimiento de queries frecuentes
     */
    public function up(): void
    {
        // Índices para papers - tabla crítica sin índices
        Schema::table('papers', function (Blueprint $table) {
            if (!$this->indexExists('papers', 'papers_company_active_index')) {
                $table->index(['company_id', 'is_active'], 'papers_company_active_index');
            }
            if (!$this->indexExists('papers', 'papers_supplier_index')) {
                $table->index('supplier_id', 'papers_supplier_index');
            }
            if (!$this->indexExists('papers', 'papers_code_index')) {
                $table->index('code', 'papers_code_index');
            }
        });

        // Índices para stock_movements - usado en reportes y widgets
        if (Schema::hasTable('stock_movements')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                if (!$this->indexExists('stock_movements', 'stock_movements_created_at_index')) {
                    $table->index('created_at', 'stock_movements_created_at_index');
                }
                if (!$this->indexExists('stock_movements', 'stock_movements_company_created_index')) {
                    $table->index(['company_id', 'created_at'], 'stock_movements_company_created_index');
                }
            });
        }

        // Índice para production_orders - usado en gráficos de tendencias
        Schema::table('production_orders', function (Blueprint $table) {
            if (!$this->indexExists('production_orders', 'production_orders_created_at_index')) {
                $table->index('created_at', 'production_orders_created_at_index');
            }
        });

        // Índice para collection_accounts - usado en reportes por período
        Schema::table('collection_accounts', function (Blueprint $table) {
            if (!$this->indexExists('collection_accounts', 'collection_accounts_created_at_index')) {
                $table->index('created_at', 'collection_accounts_created_at_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('papers', function (Blueprint $table) {
            if ($this->indexExists('papers', 'papers_company_active_index')) {
                $table->dropIndex('papers_company_active_index');
            }
            if ($this->indexExists('papers', 'papers_supplier_index')) {
                $table->dropIndex('papers_supplier_index');
            }
            if ($this->indexExists('papers', 'papers_code_index')) {
                $table->dropIndex('papers_code_index');
            }
        });

        if (Schema::hasTable('stock_movements')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                if ($this->indexExists('stock_movements', 'stock_movements_created_at_index')) {
                    $table->dropIndex('stock_movements_created_at_index');
                }
                if ($this->indexExists('stock_movements', 'stock_movements_company_created_index')) {
                    $table->dropIndex('stock_movements_company_created_index');
                }
            });
        }

        Schema::table('production_orders', function (Blueprint $table) {
            if ($this->indexExists('production_orders', 'production_orders_created_at_index')) {
                $table->dropIndex('production_orders_created_at_index');
            }
        });

        Schema::table('collection_accounts', function (Blueprint $table) {
            if ($this->indexExists('collection_accounts', 'collection_accounts_created_at_index')) {
                $table->dropIndex('collection_accounts_created_at_index');
            }
        });
    }
};
