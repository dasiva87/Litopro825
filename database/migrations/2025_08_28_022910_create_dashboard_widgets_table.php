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
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('widget_type');
            $table->string('widget_key')->unique();
            $table->string('title');
            $table->json('configuration')->nullable();
            $table->enum('position_column', ['center', 'right'])->default('center');
            $table->integer('position_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            
            $table->index(['company_id', 'position_column', 'position_order'], 'widgets_company_position_idx');
            $table->index(['company_id', 'is_active', 'is_visible'], 'widgets_company_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
    }
};
