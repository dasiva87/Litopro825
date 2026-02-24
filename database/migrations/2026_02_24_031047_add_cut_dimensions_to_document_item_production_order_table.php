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
        Schema::table('document_item_production_order', function (Blueprint $table) {
            $table->decimal('cut_width', 10, 2)->nullable()->after('vertical_size')->comment('Ancho de corte de la HOJA (según mounting_type)');
            $table->decimal('cut_height', 10, 2)->nullable()->after('cut_width')->comment('Alto de corte de la HOJA (según mounting_type)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_item_production_order', function (Blueprint $table) {
            $table->dropColumn(['cut_width', 'cut_height']);
        });
    }
};
