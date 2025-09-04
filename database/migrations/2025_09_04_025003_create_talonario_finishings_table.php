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
        Schema::create('talonario_finishings', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('talonario_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('finishing_id')->constrained()->cascadeOnDelete();
            
            // Configuración del acabado
            $table->integer('quantity')->default(1);
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->json('finishing_options')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->unique(['talonario_item_id', 'finishing_id']);
            $table->index('finishing_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talonario_finishings');
    }
};