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
        // Add project_id to documents table
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('company_id')
                  ->constrained('projects')->onDelete('set null');
            $table->index('project_id');
        });

        // Add project_id to purchase_orders table
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('company_id')
                  ->constrained('projects')->onDelete('set null');
            $table->index('project_id');
        });

        // Add project_id to production_orders table
        Schema::table('production_orders', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('company_id')
                  ->constrained('projects')->onDelete('set null');
            $table->index('project_id');
        });

        // Add project_id to collection_accounts table
        Schema::table('collection_accounts', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('company_id')
                  ->constrained('projects')->onDelete('set null');
            $table->index('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropIndex(['project_id']);
            $table->dropColumn('project_id');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropIndex(['project_id']);
            $table->dropColumn('project_id');
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropIndex(['project_id']);
            $table->dropColumn('project_id');
        });

        Schema::table('collection_accounts', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropIndex(['project_id']);
            $table->dropColumn('project_id');
        });
    }
};
