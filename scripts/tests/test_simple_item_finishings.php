<?php

/**
 * Script de prueba para el SISTEMA DE ACABADOS en SimpleItem
 *
 * Este script prueba:
 * 1. Agregar acabados a un SimpleItem
 * 2. Calcular el costo de acabados
 * 3. Integración con el pricing completo
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SimpleItem;
use App\Models\Paper;
use App\Models\PrintingMachine;
use App\Models\Finishing;
use App\Services\SimpleItemCalculatorService;
use App\Enums\FinishingMeasurementUnit;

echo "═══════════════════════════════════════════════════════════════\n";
echo "PRUEBA DEL SISTEMA DE ACABADOS PARA SIMPLEITEM\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Obtener datos de la BD
$paper = \App\Models\Paper::where('width', 100)->where('height', 70)->first();
$machine = \App\Models\PrintingMachine::first();

if (!$paper || !$machine) {
    echo "❌ ERROR: Faltan datos en la BD (papel o máquina)\n";
    exit(1);
}

echo "📄 Papel: {$paper->name} ({$paper->width}×{$paper->height}cm)\n";
echo "🖨️  Máquina: {$machine->name}\n\n";

// Crear SimpleItem de prueba
$item = new SimpleItem([
    'company_id' => 1,
    'description' => 'Volantes promocionales',
    'quantity' => 1000,
    'horizontal_size' => 20,
    'vertical_size' => 13,
    'sobrante_papel' => 0,
    'ink_front_count' => 4,
    'ink_back_count' => 4,
    'front_back_plate' => false,
    'profit_percentage' => 30,
    'paper_id' => $paper->id,
    'printing_machine_id' => $machine->id,
]);

$item->setRelation('paper', $paper);
$item->setRelation('printingMachine', $machine);

// Guardar el item en la BD (necesario para agregar acabados)
$item->save();

echo "═══════════════════════════════════════════════════════════════\n";
echo "DATOS DEL TRABAJO\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Descripción: {$item->description}\n";
echo "Tamaño: {$item->horizontal_size}×{$item->vertical_size}cm\n";
echo "Cantidad: {$item->quantity} unidades\n";
echo "Tintas: {$item->ink_front_count}×{$item->ink_back_count}\n";
echo "ID: {$item->id}\n\n";

// Buscar o crear acabados de prueba
echo "═══════════════════════════════════════════════════════════════\n";
echo "PASO 1: AGREGAR ACABADOS AL SIMPLEITEM\n";
echo "═══════════════════════════════════════════════════════════════\n";

// Acabado 1: Plastificado por millar
$plastificado = Finishing::firstOrCreate(
    ['name' => 'Plastificado Mate - TEST'],
    [
        'company_id' => 1,
        'code' => 'FIN-PLAS-TEST',
        'description' => 'Plastificado mate para testing',
        'unit_price' => 150000, // $150,000 por millar
        'measurement_unit' => FinishingMeasurementUnit::MILLAR,
        'is_own_provider' => true,
        'active' => true,
    ]
);

echo "✅ Acabado 1 creado/encontrado: {$plastificado->name}\n";
echo "   - Tipo: {$plastificado->measurement_unit->label()}\n";
echo "   - Precio: $" . number_format($plastificado->unit_price, 2) . "/millar\n\n";

// Acabado 2: Barniz UV por tamaño
$barnizUV = Finishing::firstOrCreate(
    ['name' => 'Barniz UV - TEST'],
    [
        'company_id' => 1,
        'code' => 'FIN-BARNIZ-TEST',
        'description' => 'Barniz UV para testing',
        'unit_price' => 150, // $150 por cm²
        'measurement_unit' => FinishingMeasurementUnit::TAMAÑO,
        'is_own_provider' => true,
        'active' => true,
    ]
);

echo "✅ Acabado 2 creado/encontrado: {$barnizUV->name}\n";
echo "   - Tipo: {$barnizUV->measurement_unit->label()}\n";
echo "   - Precio: $" . number_format($barnizUV->unit_price, 2) . "/cm²\n\n";

// Agregar acabados al item
$item->addFinishing($plastificado);
$item->addFinishing($barnizUV);

// Refrescar la relación
$item->load('finishings');

echo "✅ Acabados agregados al SimpleItem: {$item->finishings->count()}\n\n";

// Mostrar desglose de acabados
echo "═══════════════════════════════════════════════════════════════\n";
echo "PASO 2: DESGLOSE DE ACABADOS\n";
echo "═══════════════════════════════════════════════════════════════\n";

$finishingsBreakdown = $item->getFinishingsBreakdown();

foreach ($finishingsBreakdown as $index => $finishing) {
    echo "Acabado " . ($index + 1) . ": {$finishing['finishing_name']}\n";
    echo "   - Tipo: {$finishing['measurement_unit']}\n";
    echo "   - Parámetros: " . json_encode($finishing['params']) . "\n";
    echo "   - Costo: $" . number_format($finishing['cost'], 2) . "\n";
    echo "   - Por defecto: " . ($finishing['is_default'] ? 'Sí' : 'No') . "\n\n";
}

$totalFinishingsCost = $item->calculateFinishingsCost();
echo "💰 COSTO TOTAL DE ACABADOS: $" . number_format($totalFinishingsCost, 2) . "\n\n";

// Calcular pricing completo
echo "═══════════════════════════════════════════════════════════════\n";
echo "PASO 3: PRICING COMPLETO CON ACABADOS\n";
echo "═══════════════════════════════════════════════════════════════\n";

$calculator = new SimpleItemCalculatorService();
$pricingResult = $calculator->calculateFinalPricingNew($item);

if (!$pricingResult) {
    echo "❌ ERROR: No se pudo calcular el pricing\n";
    exit(1);
}

echo "✅ DESGLOSE DE COSTOS:\n\n";

$breakdown = $pricingResult->costBreakdown;

foreach ($breakdown as $key => $item_cost) {
    if ($item_cost['cost'] > 0) {
        echo "   {$item_cost['description']}: $" . number_format($item_cost['cost'], 2) . "\n";
        echo "      └─ {$item_cost['quantity']}\n";
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "TOTALES\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Subtotal: $" . number_format($pricingResult->subtotal, 2) . "\n";
echo "Ganancia ({$item->profit_percentage}%): $" . number_format($pricingResult->profitAmount, 2) . "\n";
echo "Precio Final: $" . number_format($pricingResult->finalPrice, 2) . "\n";
echo "Precio Unitario: $" . number_format($pricingResult->unitPrice, 2) . "\n\n";

// Verificar que los acabados están incluidos
echo "═══════════════════════════════════════════════════════════════\n";
echo "VERIFICACIÓN\n";
echo "═══════════════════════════════════════════════════════════════\n";

$finishingsInBreakdown = $pricingResult->costBreakdown['finishings']['cost'];
$finishingsCalculated = $totalFinishingsCost;

echo "Acabados en breakdown: $" . number_format($finishingsInBreakdown, 2) . "\n";
echo "Acabados calculados: $" . number_format($finishingsCalculated, 2) . "\n";

if (abs($finishingsInBreakdown - $finishingsCalculated) < 0.01) {
    echo "✅ Los acabados están correctamente incluidos en el pricing\n";
} else {
    echo "❌ ERROR: Los acabados NO coinciden en el pricing\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "LIMPIEZA\n";
echo "═══════════════════════════════════════════════════════════════\n";

// Limpiar datos de testing
$item->finishings()->detach(); // Remover acabados
$item->delete(); // Eliminar el SimpleItem
$plastificado->delete(); // Eliminar acabado de testing
$barnizUV->delete(); // Eliminar acabado de testing

echo "✅ Datos de testing eliminados\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "✅ PRUEBA COMPLETADA CON ÉXITO\n";
echo "═══════════════════════════════════════════════════════════════\n";
