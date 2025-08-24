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
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->onDelete('cascade');
            $table->enum('measurement_system', ['metric', 'imperial'])->default('metric');
            $table->integer('quote_number_start')->default(1);
            $table->integer('order_number_start')->default(1);
            $table->integer('print_order_number_start')->default(1);
            $table->decimal('profit_margin_percentage', 8, 2)->default(20.00);
            $table->decimal('waste_percentage', 8, 2)->default(5.00);
            $table->decimal('default_design_price', 10, 2)->default(0.00);
            $table->decimal('default_transport_price', 10, 2)->default(0.00);
            $table->decimal('default_cutting_price', 10, 2)->default(0.00);
            $table->decimal('tax_rate', 5, 2)->default(0.00);
            $table->string('currency', 3)->default('COP');
            $table->string('timezone')->default('America/Bogota');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
