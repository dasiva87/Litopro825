<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\DigitalItemCalculatorService;
use App\Models\DigitalItem;

class DigitalItemCalculatorServiceTest extends TestCase
{
    private DigitalItemCalculatorService $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new DigitalItemCalculatorService();
    }

    /** @test */
    public function it_calculates_by_unit_correctly(): void
    {
        $result = $this->calculator->calculateByUnit(10, 5000);
        $this->assertEquals(50000, $result);

        $result = $this->calculator->calculateByUnit(1, 15000);
        $this->assertEquals(15000, $result);
    }

    /** @test */
    public function it_calculates_by_size_correctly(): void
    {
        // 100cm x 200cm = 2m²
        $result = $this->calculator->calculateBySize(100, 200, 25000);
        $this->assertEquals(50000, $result); // 2m² * 25000

        // 50cm x 50cm = 0.25m²
        $result = $this->calculator->calculateBySize(50, 50, 40000);
        $this->assertEquals(10000, $result); // 0.25m² * 40000
    }

    /** @test */
    public function it_calculates_profit_margin_correctly(): void
    {
        $margin = $this->calculator->calculateProfitMargin(100, 80);
        $this->assertEquals(25, $margin); // (100-80)/80 * 100 = 25%

        $margin = $this->calculator->calculateProfitMargin(150, 50);
        $this->assertEquals(200, $margin); // (150-50)/50 * 100 = 200%

        // Sin costo de compra = 100% margen
        $margin = $this->calculator->calculateProfitMargin(100, 0);
        $this->assertEquals(100, $margin);
    }

    /** @test */
    public function it_validates_unit_parameters_correctly(): void
    {
        $mockItem = new class {
            public $pricing_type = 'unit';
        };

        // Parámetros válidos
        $errors = $this->calculator->validateParameters($mockItem, ['quantity' => 5]);
        $this->assertEmpty($errors);

        // Cantidad inválida
        $errors = $this->calculator->validateParameters($mockItem, ['quantity' => 0]);
        $this->assertContains('La cantidad debe ser mayor a 0 para items por unidad', $errors);

        $errors = $this->calculator->validateParameters($mockItem, []);
        $this->assertContains('La cantidad debe ser mayor a 0 para items por unidad', $errors);
    }

    /** @test */
    public function it_validates_size_parameters_correctly(): void
    {
        $mockItem = new class {
            public $pricing_type = 'size';
        };

        // Parámetros válidos
        $errors = $this->calculator->validateParameters($mockItem, [
            'width' => 100,
            'height' => 200,
            'quantity' => 1
        ]);
        $this->assertEmpty($errors);

        // Dimensiones inválidas
        $errors = $this->calculator->validateParameters($mockItem, [
            'width' => 0,
            'height' => 200,
            'quantity' => 1
        ]);
        $this->assertContains('El ancho debe ser mayor a 0 para items por tamaño', $errors);

        // Dimensiones demasiado grandes
        $errors = $this->calculator->validateParameters($mockItem, [
            'width' => 600,
            'height' => 200,
            'quantity' => 1
        ]);
        $this->assertContains('El ancho no puede ser mayor a 500 cm', $errors);
    }

    /** @test */
    public function it_estimates_production_time_correctly(): void
    {
        $mockItemUnit = new class {
            public $pricing_type = 'unit';
        };

        $mockItemSize = new class {
            public $pricing_type = 'size';
        };

        // Item por unidad - cantidad baja
        $time = $this->calculator->estimateProductionTime($mockItemUnit, ['quantity' => 5]);
        $this->assertEquals(24, $time); // Tiempo base

        // Item por unidad - cantidad alta
        $time = $this->calculator->estimateProductionTime($mockItemUnit, ['quantity' => 25]);
        $this->assertEquals(28, $time); // Base + 4 horas extra

        // Item por tamaño - área grande
        $time = $this->calculator->estimateProductionTime($mockItemSize, [
            'width' => 200, // 2m
            'height' => 300, // 3m = 6m²
            'quantity' => 1
        ]);
        $this->assertEquals(30, $time); // Base + 6 horas por área
    }

    /** @test */
    public function it_converts_centimeters_to_meters_correctly(): void
    {
        // Usar reflexión para probar método privado
        $reflection = new \ReflectionClass($this->calculator);
        $method = $reflection->getMethod('convertToMeters');
        $method->setAccessible(true);

        $this->assertEquals(1.0, $method->invoke($this->calculator, 100));
        $this->assertEquals(0.5, $method->invoke($this->calculator, 50));
        $this->assertEquals(2.0, $method->invoke($this->calculator, 200));
    }
}