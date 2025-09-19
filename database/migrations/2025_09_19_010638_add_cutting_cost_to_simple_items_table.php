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
        Schema::table('simple_items', function (Blueprint $table) {
            $table->decimal('cutting_cost', 10, 2)->default(0.00)->after('printing_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('simple_items', function (Blueprint $table) {
            $table->dropColumn('cutting_cost');
        });
    }
};
