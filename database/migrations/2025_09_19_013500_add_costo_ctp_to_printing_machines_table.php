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
        Schema::table('printing_machines', function (Blueprint $table) {
            $table->decimal('costo_ctp', 10, 2)->default(0.00)->after('cost_per_impression')
                  ->comment('Costo de Computer-to-Plate (CTP) por plancha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('printing_machines', function (Blueprint $table) {
            $table->dropColumn('costo_ctp');
        });
    }
};
