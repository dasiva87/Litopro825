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
        Schema::table('document_item_production_order', function (Blueprint $table) {
            $table->enum('process_type', ['printing', 'finishing'])
                ->default('printing')
                ->after('document_item_id');

            $table->string('finishing_name')->nullable()->after('process_type');
            $table->string('process_description')->nullable()->after('finishing_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_item_production_order', function (Blueprint $table) {
            $table->dropColumn(['process_type', 'finishing_name', 'process_description']);
        });
    }
};
