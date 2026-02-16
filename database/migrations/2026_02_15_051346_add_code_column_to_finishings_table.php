<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Agregar columna code si no existe
        if (!Schema::hasColumn('finishings', 'code')) {
            Schema::table('finishings', function (Blueprint $table) {
                $table->string('code')->nullable()->after('supplier_id');
            });

            // Generar códigos para registros existentes
            $finishings = DB::table('finishings')
                ->whereNull('code')
                ->orderBy('company_id')
                ->orderBy('id')
                ->get();

            $counters = [];
            foreach ($finishings as $finishing) {
                $companyId = $finishing->company_id;
                if (!isset($counters[$companyId])) {
                    $counters[$companyId] = 1;
                }

                $code = 'ACB' . str_pad($counters[$companyId], 4, '0', STR_PAD_LEFT);

                DB::table('finishings')
                    ->where('id', $finishing->id)
                    ->update(['code' => $code]);

                $counters[$companyId]++;
            }

            // Agregar índice único después de llenar los códigos
            Schema::table('finishings', function (Blueprint $table) {
                $table->unique(['company_id', 'code'], 'finishings_company_code_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('finishings', 'code')) {
            Schema::table('finishings', function (Blueprint $table) {
                $table->dropUnique('finishings_company_code_unique');
                $table->dropColumn('code');
            });
        }
    }
};
