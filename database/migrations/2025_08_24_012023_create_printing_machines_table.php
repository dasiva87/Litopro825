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
        Schema::create('printing_machines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Offset 4 colores, Digital HP, etc.
            $table->string('type'); // offset, digital, gran_formato
            $table->decimal('max_width', 8, 2)->nullable(); // Ancho máximo de impresión
            $table->decimal('max_height', 8, 2)->nullable(); // Alto máximo de impresión
            $table->integer('max_colors')->default(4); // Máximo de colores
            $table->decimal('cost_per_impression', 10, 4)->default(0); // Costo por impresión
            $table->decimal('setup_cost', 10, 2)->default(0); // Costo de montaje
            $table->boolean('is_own')->default(true); // Propia o de proveedor
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('printing_machines');
    }
};
