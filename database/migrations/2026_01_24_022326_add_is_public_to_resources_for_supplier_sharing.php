<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega campo is_public a recursos compartibles entre litografías.
 *
 * Cuando is_public = true, las litografías clientes (que tienen una
 * SupplierRelationship activa) pueden ver y usar ese recurso.
 *
 * Recursos afectados:
 * - printing_machines (Máquinas de impresión)
 * - finishings (Acabados)
 * - digital_items (Items de impresión digital)
 * - papers (Papeles)
 * - products (Productos)
 */
return new class extends Migration
{
    public function up(): void
    {
        // printing_machines ya tiene is_public, no se agrega

        Schema::table('finishings', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('active')
                ->comment('Si es true, litografías clientes pueden ver este recurso');
        });

        Schema::table('digital_items', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('active')
                ->comment('Si es true, litografías clientes pueden ver este recurso');
        });

        Schema::table('papers', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('is_active')
                ->comment('Si es true, litografías clientes pueden ver este recurso');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('active')
                ->comment('Si es true, litografías clientes pueden ver este recurso');
        });
    }

    public function down(): void
    {
        // printing_machines mantiene su is_public original

        Schema::table('finishings', function (Blueprint $table) {
            $table->dropColumn('is_public');
        });

        Schema::table('digital_items', function (Blueprint $table) {
            $table->dropColumn('is_public');
        });

        Schema::table('papers', function (Blueprint $table) {
            $table->dropColumn('is_public');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_public');
        });
    }
};
