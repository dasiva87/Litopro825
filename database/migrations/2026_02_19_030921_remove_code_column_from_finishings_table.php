<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verificar si la columna code existe antes de eliminarla
        if (Schema::hasColumn('finishings', 'code')) {
            // Verificar si el índice existe antes de eliminarlo
            $indexExists = DB::select("SHOW INDEX FROM finishings WHERE Key_name = 'finishings_company_code_unique'");

            Schema::table('finishings', function (Blueprint $table) use ($indexExists) {
                if ($indexExists) {
                    $table->dropUnique('finishings_company_code_unique');
                }
                $table->dropColumn('code');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('finishings', 'code')) {
            Schema::table('finishings', function (Blueprint $table) {
                $table->string('code')->nullable()->after('supplier_id');
                $table->unique(['company_id', 'code'], 'finishings_company_code_unique');
            });
        }
    }
};
