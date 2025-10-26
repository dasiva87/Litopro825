<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Aumentar precisión de unit_price para soportar items de alto valor como talonarios
     * Antes: decimal(10, 4) - máximo 999,999.9999
     * Después: decimal(14, 4) - máximo 9,999,999,999.9999
     */
    public function up(): void
    {
        Schema::table('document_item_purchase_order', function (Blueprint $table) {
            $table->decimal('unit_price', 14, 4)->nullable()->change();
            $table->decimal('total_price', 16, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_item_purchase_order', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 4)->nullable()->change();
            $table->decimal('total_price', 12, 2)->nullable()->change();
        });
    }
};
