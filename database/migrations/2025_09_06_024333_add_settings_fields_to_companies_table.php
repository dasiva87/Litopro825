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
        Schema::table('companies', function (Blueprint $table) {
            // Configuración fiscal
            $table->string('fiscal_regime')->nullable()->after('tax_id');
            $table->boolean('invoice_auto_numbering')->default(true)->after('fiscal_regime');
            
            // Configuración de negocio
            $table->decimal('default_profit_margin', 5, 2)->default(30.00)->after('invoice_auto_numbering');
            $table->decimal('default_tax_rate', 5, 2)->default(19.00)->after('default_profit_margin');
            $table->integer('quote_validity_days')->default(30)->after('default_tax_rate');
            
            // Configuración de producción
            $table->integer('production_lead_time')->default(3)->after('quote_validity_days');
            $table->boolean('stock_alerts_enabled')->default(true)->after('production_lead_time');
            $table->integer('low_stock_threshold')->default(10)->after('stock_alerts_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'fiscal_regime',
                'invoice_auto_numbering',
                'default_profit_margin',
                'default_tax_rate', 
                'quote_validity_days',
                'production_lead_time',
                'stock_alerts_enabled',
                'low_stock_threshold',
            ]);
        });
    }
};