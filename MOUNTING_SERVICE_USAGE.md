# MountingCalculatorService - Guía de Uso

## Descripción
Servicio para calcular cuántas copias de un trabajo caben en un pliego de máquina, considerando márgenes/sangría.

---

## Uso Básico

```php
use App\Services\MountingCalculatorService;

$calculator = new MountingCalculatorService();

// Ejemplo: Tarjetas de presentación
// Trabajo: 9cm × 5cm
// Máquina: 50cm × 70cm
// Márgenes: 1cm por lado (default)

$result = $calculator->calculateMounting(
    workWidth: 9.0,
    workHeight: 5.0,
    machineWidth: 50.0,
    machineHeight: 70.0,
    marginPerSide: 1.0  // Opcional, default = 1cm
);
```

## Resultado

```php
[
    'horizontal' => [
        'copies_per_sheet' => 65,     // 5 × 13
        'layout' => '5 × 13',
        'orientation' => 'horizontal',
        'rows' => 13,
        'cols' => 5,
        'work_width' => 9.0,
        'work_height' => 5.0
    ],
    'vertical' => [
        'copies_per_sheet' => 78,     // 9 × 8 (mejor opción!)
        'layout' => '9 × 8',
        'orientation' => 'vertical',
        'rows' => 8,
        'cols' => 9,
        'work_width' => 5.0,          // Rotado
        'work_height' => 9.0          // Rotado
    ],
    'maximum' => [
        'copies_per_sheet' => 78,     // Retorna la mejor opción
        'layout' => '9 × 8',
        'orientation' => 'vertical',
        'rows' => 8,
        'cols' => 9,
        'work_width' => 5.0,
        'work_height' => 9.0
    ]
]
```

---

## Métodos Adicionales

### 1. Calcular pliegos necesarios

```php
// El cliente necesita 500 tarjetas
// Caben 78 copias por pliego (del cálculo anterior)

$sheets = $calculator->calculateRequiredSheets(
    requiredCopies: 500,
    copiesPerSheet: 78
);

// Resultado:
[
    'sheets_needed' => 7,              // Necesitas 7 pliegos
    'total_copies_produced' => 546,    // 7 × 78 = 546 copias
    'waste_copies' => 46               // 546 - 500 = 46 sobrantes
]
```

### 2. Calcular aprovechamiento del pliego

```php
// ¿Qué porcentaje del pliego estamos usando?

$efficiency = $calculator->calculateEfficiency(
    workWidth: 9.0,
    workHeight: 5.0,
    copiesPerSheet: 78,          // Orientación vertical
    usableWidth: 48.0,           // 50cm - 2cm márgenes
    usableHeight: 68.0           // 70cm - 2cm márgenes
);

// Resultado: 85.66%
// Interpretación: Estamos usando el 85.66% del área útil del pliego
```

---

## Casos de Uso Reales

### Caso 1: Volantes A5
```php
$result = $calculator->calculateMounting(
    workWidth: 14.8,   // A5 ancho
    workHeight: 21.0,  // A5 alto
    machineWidth: 50.0,
    machineHeight: 70.0
);

// maximum => 9 copias por pliego (3 × 3)
```

### Caso 2: Afiches grandes
```php
$result = $calculator->calculateMounting(
    workWidth: 40.0,
    workHeight: 60.0,
    machineWidth: 50.0,
    machineHeight: 70.0
);

// maximum => 1 copia por pliego (el afiche casi llena el pliego)
```

### Caso 3: Stickers pequeños
```php
$result = $calculator->calculateMounting(
    workWidth: 3.0,
    workHeight: 3.0,
    machineWidth: 50.0,
    machineHeight: 70.0
);

// maximum => 352 copias por pliego (16 × 22)
```

### Caso 4: Trabajo NO cabe
```php
$result = $calculator->calculateMounting(
    workWidth: 80.0,    // Muy grande!
    workHeight: 100.0,  // Muy grande!
    machineWidth: 50.0,
    machineHeight: 70.0
);

// Resultado:
[
    'horizontal' => ['copies_per_sheet' => 0, ...],
    'vertical' => ['copies_per_sheet' => 0, ...],
    'maximum' => ['copies_per_sheet' => 0, ...]
]
```

---

## Integración con SimpleItem

```php
// En app/Models/SimpleItem.php o SimpleItemCalculatorService

public function calculateMountingForItem()
{
    $calculator = new MountingCalculatorService();

    $result = $calculator->calculateMounting(
        workWidth: $this->horizontal_size,
        workHeight: $this->vertical_size,
        machineWidth: $this->printingMachine->max_width ?? 50.0,
        machineHeight: $this->printingMachine->max_height ?? 70.0,
        marginPerSide: 1.0
    );

    // Guardar la mejor opción
    $this->mounting_option = $result['maximum']['orientation'];
    $this->copies_per_sheet = $result['maximum']['copies_per_sheet'];
    $this->mounting_layout = $result['maximum']['layout'];

    return $result;
}
```

---

## Diferencias con `SimpleItemCalculatorService::calculateMountingOptions()`

| Aspecto | MountingCalculatorService | SimpleItemCalculatorService |
|---------|---------------------------|----------------------------|
| **Propósito** | Solo cálculo de montaje (puro) | Cálculo + pricing + millares |
| **Inputs** | Dimensiones genéricas | Modelo SimpleItem completo |
| **Outputs** | Array con 3 orientaciones | Modifica el modelo directamente |
| **Reutilizable** | Sí (cualquier tipo de item) | No (acoplado a SimpleItem) |
| **Testing** | Fácil (función pura) | Complejo (requiere modelo) |

---

## Testing Manual

```bash
php artisan tinker

# Test rápido
$calc = new App\Services\MountingCalculatorService();
$result = $calc->calculateMounting(9, 5, 50, 70, 1);
print_r($result['maximum']);
```

---

## Próximos Pasos (Opcional)

1. **Agregar a SimpleItem**: Usar este servicio en lugar del cálculo actual
2. **Agregar a MagazineItem**: Calcular montaje para páginas de revistas
3. **UI en Filament**: Mostrar preview visual del montaje en modal
4. **Validaciones**: Alertar cuando aprovechamiento < 50%
