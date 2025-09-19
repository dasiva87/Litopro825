<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Esta migración ya no es necesaria porque las foreign keys
        // se crean en la migración 2025_08_23_030539_add_company_id_to_users_table.php
        // usando foreignId()->constrained()

        // No hacer nada - las foreign keys ya existen
    }

    public function down(): void
    {
        // No hacer nada - las foreign keys se eliminan en la migración
        // 2025_08_23_030539_add_company_id_to_users_table.php
    }
};