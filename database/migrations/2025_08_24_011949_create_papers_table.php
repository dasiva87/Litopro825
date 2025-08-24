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
        Schema::create('papers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Bond 20, Couche 150, etc.
            $table->string('type')->nullable(); // Bond, Couche, Cartulina, etc.
            $table->integer('weight')->nullable(); // Gramaje en gr/m²
            $table->decimal('width', 8, 2)->nullable(); // Ancho del pliego
            $table->decimal('height', 8, 2)->nullable(); // Alto del pliego
            $table->decimal('cost_per_sheet', 10, 4)->default(0); // Costo por pliego
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
        Schema::dropIfExists('papers');
    }
};
