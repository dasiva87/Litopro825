<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Aumenta la precisión de todos los campos de precio/costo en las tablas de items
     * de decimal(10,2) a decimal(15,2) para soportar valores hasta 9,999,999,999,999.99
     */
    public function up(): void
    {
        // simple_items
        if (Schema::hasTable('simple_items')) {
            Schema::table('simple_items', function (Blueprint $table) {
                $table->decimal('design_value', 15, 2)->default(0.00)->change();
                $table->decimal('transport_value', 15, 2)->default(0.00)->change();
                $table->decimal('rifle_value', 15, 2)->default(0.00)->change();
                $table->decimal('paper_cost', 15, 2)->default(0.00)->change();
                $table->decimal('printing_cost', 15, 2)->default(0.00)->change();
                $table->decimal('cutting_cost', 15, 2)->default(0.00)->change();
                $table->decimal('mounting_cost', 15, 2)->default(0.00)->change();
                $table->decimal('total_cost', 15, 2)->default(0.00)->change();
                $table->decimal('final_price', 15, 2)->default(0.00)->change();
            });
        }

        // document_items
        if (Schema::hasTable('document_items')) {
            Schema::table('document_items', function (Blueprint $table) {
                $table->decimal('paper_cost', 15, 2)->default(0.00)->change();
                $table->decimal('printing_cost', 15, 2)->default(0.00)->change();
                $table->decimal('cutting_cost', 15, 2)->default(0.00)->change();
                $table->decimal('design_cost', 15, 2)->default(0.00)->change();
                $table->decimal('transport_cost', 15, 2)->default(0.00)->change();
                $table->decimal('other_costs', 15, 2)->default(0.00)->change();
                $table->decimal('unit_price', 15, 2)->default(0.00)->change();
                $table->decimal('total_price', 15, 2)->default(0.00)->change();
            });
        }

        // talonario_items
        if (Schema::hasTable('talonario_items')) {
            Schema::table('talonario_items', function (Blueprint $table) {
                $table->decimal('sheets_total_cost', 15, 2)->default(0)->change();
                $table->decimal('finishing_cost', 15, 2)->default(0)->change();
                $table->decimal('design_value', 15, 2)->default(0)->change();
                $table->decimal('transport_value', 15, 2)->default(0)->change();
                $table->decimal('total_cost', 15, 2)->default(0)->change();
                $table->decimal('final_price', 15, 2)->default(0)->change();
            });
        }

        // magazine_items
        if (Schema::hasTable('magazine_items')) {
            Schema::table('magazine_items', function (Blueprint $table) {
                $table->decimal('binding_cost', 15, 2)->default(0)->change();
                $table->decimal('assembly_cost', 15, 2)->default(0)->change();
                $table->decimal('finishing_cost', 15, 2)->default(0)->change();
                $table->decimal('transport_value', 15, 2)->default(0)->change();
                $table->decimal('design_value', 15, 2)->default(0)->change();
                $table->decimal('pages_total_cost', 15, 2)->default(0)->change();
                $table->decimal('total_cost', 15, 2)->default(0)->change();
                $table->decimal('final_price', 15, 2)->default(0)->change();
            });
        }

        // digital_items
        if (Schema::hasTable('digital_items')) {
            Schema::table('digital_items', function (Blueprint $table) {
                $table->decimal('sale_price', 15, 2)->change();
            });
        }

        // purchase_order_items
        if (Schema::hasTable('purchase_order_items')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                $table->decimal('unit_price', 15, 4)->change();
                $table->decimal('total_price', 15, 2)->change();
            });
        }

        // paper_order_items
        if (Schema::hasTable('paper_order_items')) {
            Schema::table('paper_order_items', function (Blueprint $table) {
                $table->decimal('unit_price', 15, 2)->change();
                $table->decimal('total_price', 15, 2)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // simple_items
        if (Schema::hasTable('simple_items')) {
            Schema::table('simple_items', function (Blueprint $table) {
                $table->decimal('design_value', 10, 2)->default(0.00)->change();
                $table->decimal('transport_value', 10, 2)->default(0.00)->change();
                $table->decimal('rifle_value', 10, 2)->default(0.00)->change();
                $table->decimal('paper_cost', 10, 2)->default(0.00)->change();
                $table->decimal('printing_cost', 10, 2)->default(0.00)->change();
                $table->decimal('cutting_cost', 10, 2)->default(0.00)->change();
                $table->decimal('mounting_cost', 10, 2)->default(0.00)->change();
                $table->decimal('total_cost', 10, 2)->default(0.00)->change();
                $table->decimal('final_price', 10, 2)->default(0.00)->change();
            });
        }

        // document_items
        if (Schema::hasTable('document_items')) {
            Schema::table('document_items', function (Blueprint $table) {
                $table->decimal('paper_cost', 10, 2)->default(0.00)->change();
                $table->decimal('printing_cost', 10, 2)->default(0.00)->change();
                $table->decimal('cutting_cost', 10, 2)->default(0.00)->change();
                $table->decimal('design_cost', 10, 2)->default(0.00)->change();
                $table->decimal('transport_cost', 10, 2)->default(0.00)->change();
                $table->decimal('other_costs', 10, 2)->default(0.00)->change();
                $table->decimal('unit_price', 10, 2)->default(0.00)->change();
                $table->decimal('total_price', 12, 2)->default(0.00)->change();
            });
        }

        // talonario_items
        if (Schema::hasTable('talonario_items')) {
            Schema::table('talonario_items', function (Blueprint $table) {
                $table->decimal('sheets_total_cost', 10, 2)->default(0)->change();
                $table->decimal('finishing_cost', 10, 2)->default(0)->change();
                $table->decimal('design_value', 10, 2)->default(0)->change();
                $table->decimal('transport_value', 10, 2)->default(0)->change();
                $table->decimal('total_cost', 10, 2)->default(0)->change();
                $table->decimal('final_price', 10, 2)->default(0)->change();
            });
        }

        // magazine_items
        if (Schema::hasTable('magazine_items')) {
            Schema::table('magazine_items', function (Blueprint $table) {
                $table->decimal('binding_cost', 10, 2)->default(0)->change();
                $table->decimal('assembly_cost', 10, 2)->default(0)->change();
                $table->decimal('finishing_cost', 10, 2)->default(0)->change();
                $table->decimal('transport_value', 10, 2)->default(0)->change();
                $table->decimal('design_value', 10, 2)->default(0)->change();
                $table->decimal('pages_total_cost', 10, 2)->default(0)->change();
                $table->decimal('total_cost', 10, 2)->default(0)->change();
                $table->decimal('final_price', 10, 2)->default(0)->change();
            });
        }

        // digital_items
        if (Schema::hasTable('digital_items')) {
            Schema::table('digital_items', function (Blueprint $table) {
                $table->decimal('sale_price', 10, 2)->change();
            });
        }

        // purchase_order_items
        if (Schema::hasTable('purchase_order_items')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                $table->decimal('unit_price', 10, 4)->change();
                $table->decimal('total_price', 12, 2)->change();
            });
        }

        // paper_order_items
        if (Schema::hasTable('paper_order_items')) {
            Schema::table('paper_order_items', function (Blueprint $table) {
                $table->decimal('unit_price', 10, 2)->change();
                $table->decimal('total_price', 12, 2)->change();
            });
        }
    }
};
