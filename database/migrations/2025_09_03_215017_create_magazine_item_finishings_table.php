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
        Schema::create('magazine_item_finishings', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('magazine_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('finishing_id')->constrained()->cascadeOnDelete();
            
            // Datos específicos del acabado
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->json('finishing_options')->nullable(); // Opciones específicas del acabado
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Evitar duplicados
            $table->unique(['magazine_item_id', 'finishing_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('magazine_item_finishings');
    }
};
