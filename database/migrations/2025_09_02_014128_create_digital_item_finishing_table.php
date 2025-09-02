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
        Schema::create('digital_item_finishing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digital_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('finishing_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('width', 8, 2)->nullable(); // para measurement_unit = 'tamaño'
            $table->decimal('height', 8, 2)->nullable(); // para measurement_unit = 'tamaño'
            $table->decimal('calculated_cost', 10, 2);
            $table->timestamps();
            
            $table->unique(['digital_item_id', 'finishing_id']);
            $table->index('digital_item_id');
            $table->index('finishing_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_item_finishing');
    }
};
