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
            $table->integer('sobrante_papel')->default(0)->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('simple_items', function (Blueprint $table) {
            $table->dropColumn('sobrante_papel');
        });
    }
};
