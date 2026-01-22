<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Índices de rendimiento para optimización de consultas.
     * Basado en análisis de pruebas de estrés y patrones de uso frecuente.
     */
    public function up(): void
    {
        // Índices para documents
        Schema::table('documents', function (Blueprint $table) {
            $table->index(['company_id', 'status'], 'idx_documents_company_status');
            $table->index(['company_id', 'document_type_id'], 'idx_documents_company_type');
            $table->index(['valid_until'], 'idx_documents_valid_until');
        });

        // Índices para purchase_orders
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->index(['company_id', 'status'], 'idx_purchase_orders_company_status');
            $table->index(['company_id', 'supplier_company_id'], 'idx_purchase_orders_company_supplier');
            $table->index(['email_sent_at'], 'idx_purchase_orders_email_sent');
        });

        // Índices para collection_accounts
        Schema::table('collection_accounts', function (Blueprint $table) {
            $table->index(['company_id', 'status'], 'idx_collection_accounts_company_status');
            $table->index(['client_company_id', 'status'], 'idx_collection_accounts_client_status');
            $table->index(['due_date'], 'idx_collection_accounts_due_date');
        });

        // Índices para simple_items
        Schema::table('simple_items', function (Blueprint $table) {
            $table->index(['company_id', 'paper_id'], 'idx_simple_items_company_paper');
        });

        // Índices para document_items
        Schema::table('document_items', function (Blueprint $table) {
            $table->index(['document_id', 'itemable_type', 'itemable_id'], 'idx_document_items_polymorphic');
            $table->index(['order_status', 'company_id'], 'idx_document_items_order_status');
        });

        // Índices para production_orders
        Schema::table('production_orders', function (Blueprint $table) {
            $table->index(['company_id', 'status'], 'idx_production_orders_company_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('idx_documents_company_status');
            $table->dropIndex('idx_documents_company_type');
            $table->dropIndex('idx_documents_valid_until');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex('idx_purchase_orders_company_status');
            $table->dropIndex('idx_purchase_orders_company_supplier');
            $table->dropIndex('idx_purchase_orders_email_sent');
        });

        Schema::table('collection_accounts', function (Blueprint $table) {
            $table->dropIndex('idx_collection_accounts_company_status');
            $table->dropIndex('idx_collection_accounts_client_status');
            $table->dropIndex('idx_collection_accounts_due_date');
        });

        Schema::table('simple_items', function (Blueprint $table) {
            $table->dropIndex('idx_simple_items_company_paper');
        });

        Schema::table('document_items', function (Blueprint $table) {
            $table->dropIndex('idx_document_items_polymorphic');
            $table->dropIndex('idx_document_items_order_status');
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropIndex('idx_production_orders_company_status');
        });
    }
};
