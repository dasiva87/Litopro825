# Test de Integración: MountingCalculatorService

## Testing Manual con Tinker

```bash
php artisan tinker
```

### Test 1: Servicio Standalone

```php
// Test del servicio puro
$calc = new App\Services\MountingCalculatorService();

// Tarjeta de presentación: 9cm × 5cm en pliego 50cm × 70cm
$result = $calc->calculateMounting(9, 5, 50, 70, 1);

print_r($result['maximum']);
// Debería mostrar:
// [
//     'copies_per_sheet' => 78
//     'layout' => '9 × 8'
//     'orientation' => 'vertical'
// ]

// Calcular pliegos necesarios para 500 tarjetas
$sheets = $calc->calculateRequiredSheets(500, 78);
print_r($sheets);
// [
//     'sheets_needed' => 7
//     'total_copies_produced' => 546
//     'waste_copies' => 46
// ]
```

### Test 2: Integración con SimpleItem

```php
// Obtener un SimpleItem existente
$item = App\Models\SimpleItem::with(['paper', 'printingMachine'])->first();

if (!$item) {
    echo "No hay SimpleItems en la base de datos. Crear uno primero.\n";
    exit;
}

// Usar el nuevo método getPureMounting()
$mounting = $item->getPureMounting();

if ($mounting) {
    echo "✅ MONTAJE CALCULADO:\n";
    echo "Horizontal: {$mounting['horizontal']['copies_per_sheet']} copias ({$mounting['horizontal']['layout']})\n";
    echo "Vertical: {$mounting['vertical']['copies_per_sheet']} copias ({$mounting['vertical']['layout']})\n";
    echo "Mejor opción: {$mounting['maximum']['copies_per_sheet']} copias ({$mounting['maximum']['layout']}) - {$mounting['maximum']['orientation']}\n";

    if (isset($mounting['sheets_info'])) {
        echo "\n📦 PLIEGOS NECESARIOS:\n";
        echo "Pliegos: {$mounting['sheets_info']['sheets_needed']}\n";
        echo "Copias producidas: {$mounting['sheets_info']['total_copies_produced']}\n";
        echo "Desperdicio: {$mounting['sheets_info']['waste_copies']} copias\n";
    }

    if (isset($mounting['efficiency'])) {
        echo "\n📊 APROVECHAMIENTO: {$mounting['efficiency']}%\n";
    }
} else {
    echo "❌ Error: No se pudo calcular montaje\n";
}

// Comparar con método antiguo
$oldMounting = $item->getMountingOptions();
echo "\n🔄 COMPARACIÓN CON MÉTODO ANTERIOR:\n";
echo "Opciones disponibles: " . count($oldMounting) . "\n";
if (!empty($oldMounting)) {
    echo "Mejor opción antigua: {$oldMounting[0]->cutsPerSheet} cortes/pliego\n";
}
```

### Test 3: SimpleItemCalculatorService con nuevo método

```php
$item = App\Models\SimpleItem::with(['paper', 'printingMachine'])->first();
$calculator = new App\Services\SimpleItemCalculatorService();

// Usar el nuevo método calculatePureMounting()
$pureMounting = $calculator->calculatePureMounting($item);

if ($pureMounting) {
    echo "✅ SimpleItemCalculatorService::calculatePureMounting() funciona!\n";
    print_r($pureMounting['maximum']);
} else {
    echo "❌ Error en calculatePureMounting()\n";
}

// Verificar que el método antiguo sigue funcionando
$oldOptions = $calculator->calculateMountingOptions($item);
echo "Método antiguo calculateMountingOptions(): " . count($oldOptions) . " opciones\n";
```

---

## Testing Automatizado (Opcional)

Crear archivo de test: `tests/Feature/MountingCalculatorTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\MountingCalculatorService;
use App\Models\SimpleItem;
use App\Models\PrintingMachine;

class MountingCalculatorTest extends TestCase
{
    /** @test */
    public function it_calculates_mounting_for_business_card()
    {
        $calc = new MountingCalculatorService();

        $result = $calc->calculateMounting(9, 5, 50, 70, 1);

        $this->assertEquals(78, $result['maximum']['copies_per_sheet']);
        $this->assertEquals('vertical', $result['maximum']['orientation']);
        $this->assertEquals('9 × 8', $result['maximum']['layout']);
    }

    /** @test */
    public function it_calculates_required_sheets()
    {
        $calc = new MountingCalculatorService();

        $sheets = $calc->calculateRequiredSheets(500, 78);

        $this->assertEquals(7, $sheets['sheets_needed']);
        $this->assertEquals(546, $sheets['total_copies_produced']);
        $this->assertEquals(46, $sheets['waste_copies']);
    }

    /** @test */
    public function it_calculates_efficiency()
    {
        $calc = new MountingCalculatorService();

        $efficiency = $calc->calculateEfficiency(9, 5, 78, 48, 68);

        $this->assertGreaterThan(80, $efficiency);
        $this->assertLessThanOrEqual(100, $efficiency);
    }

    /** @test */
    public function it_integrates_with_simple_item()
    {
        // Crear máquina de prueba
        $machine = PrintingMachine::factory()->create([
            'max_width' => 50,
            'max_height' => 70
        ]);

        // Crear item de prueba
        $item = SimpleItem::factory()->create([
            'horizontal_size' => 9,
            'vertical_size' => 5,
            'quantity' => 500,
            'printing_machine_id' => $machine->id
        ]);

        $mounting = $item->getPureMounting();

        $this->assertNotNull($mounting);
        $this->assertArrayHasKey('maximum', $mounting);
        $this->assertGreaterThan(0, $mounting['maximum']['copies_per_sheet']);
    }
}
```

---

## Verificación de Compatibilidad

### ✅ Checklist de Testing

- [ ] `MountingCalculatorService::calculateMounting()` funciona standalone
- [ ] `MountingCalculatorService::calculateRequiredSheets()` funciona
- [ ] `MountingCalculatorService::calculateEfficiency()` funciona
- [ ] `SimpleItemCalculatorService::calculatePureMounting()` funciona
- [ ] `SimpleItem::getPureMounting()` funciona
- [ ] `SimpleItem::getBestMounting()` funciona
- [ ] Método antiguo `calculateMountingOptions()` sigue funcionando (retrocompatibilidad)
- [ ] Cálculos de precio NO se rompieron
- [ ] `SimpleItem::calculateAll()` sigue funcionando

---

## Casos de Prueba Recomendados

### Caso 1: Tarjeta de Presentación
```php
$calc->calculateMounting(9, 5, 50, 70, 1);
// Esperado: 78 copias/pliego (9×8) vertical
```

### Caso 2: Volante A5
```php
$calc->calculateMounting(14.8, 21, 50, 70, 1);
// Esperado: 9 copias/pliego (3×3)
```

### Caso 3: Afiche Grande
```php
$calc->calculateMounting(40, 60, 50, 70, 1);
// Esperado: 1 copia/pliego
```

### Caso 4: Sticker Pequeño
```php
$calc->calculateMounting(3, 3, 50, 70, 1);
// Esperado: 352 copias/pliego (16×22)
```

### Caso 5: NO Cabe
```php
$calc->calculateMounting(80, 100, 50, 70, 1);
// Esperado: 0 copias/pliego (muy grande para la máquina)
```

---

## Output Esperado

Si todo funciona correctamente, deberías ver:

```
✅ MONTAJE CALCULADO:
Horizontal: 65 copias (5 × 13)
Vertical: 78 copias (9 × 8)
Mejor opción: 78 copias (9 × 8) - vertical

📦 PLIEGOS NECESARIOS:
Pliegos: 7
Copias producidas: 546
Desperdicio: 46 copias

📊 APROVECHAMIENTO: 85.66%

🔄 COMPARACIÓN CON MÉTODO ANTERIOR:
Opciones disponibles: 3
Mejor opción antigua: 78 cortes/pliego
```

---

## Comandos Rápidos

```bash
# Test básico
php artisan tinker
>>> $calc = new App\Services\MountingCalculatorService();
>>> print_r($calc->calculateMounting(9, 5, 50, 70, 1)['maximum']);

# Test con SimpleItem (si existe alguno)
php artisan tinker
>>> $item = App\Models\SimpleItem::first();
>>> print_r($item->getPureMounting());

# Limpiar cache si hay problemas
php artisan cache:clear
php artisan config:clear
composer dump-autoload
```
