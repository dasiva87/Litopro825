<?php

namespace Tests\Unit;

use App\Services\CuttingCalculatorService;
use PHPUnit\Framework\TestCase;

class CuttingCalculatorServiceTest extends TestCase
{
    private CuttingCalculatorService $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new CuttingCalculatorService();
    }

    /** @test */
    public function it_validates_paper_dimensions_limits()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El valor máximo para ancho y/o largo es de 125cm.');
        
        $this->calculator->calculateCuts(
            paperWidth: 130, // Excede el límite
            paperHeight: 100,
            cutWidth: 10,
            cutHeight: 15,
            desiredCuts: 100
        );
    }

    /** @test */
    public function it_validates_orientation_parameter()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Orientación no válida. Use: horizontal, vertical, maximum');
        
        $this->calculator->calculateCuts(
            paperWidth: 100,
            paperHeight: 70,
            cutWidth: 10,
            cutHeight: 15,
            desiredCuts: 100,
            orientation: 'invalid_orientation'
        );
    }

    /** @test */
    public function it_calculates_horizontal_orientation_correctly()
    {
        $result = $this->calculator->calculateCuts(
            paperWidth: 100,
            paperHeight: 70,
            cutWidth: 10,
            cutHeight: 15,
            desiredCuts: 50,
            orientation: 'horizontal'
        );

        // Verificar estructura del resultado
        $this->assertIsArray($result);
        $this->assertArrayHasKey('cutsPerSheet', $result);
        $this->assertArrayHasKey('sheetsNeeded', $result);
        $this->assertArrayHasKey('usedAreaPercentage', $result);
        $this->assertArrayHasKey('wastedAreaPercentage', $result);
        $this->assertArrayHasKey('verticalCuts', $result);
        $this->assertArrayHasKey('horizontalCuts', $result);

        // Verificar que los cálculos son lógicos
        $this->assertGreaterThan(0, $result['cutsPerSheet']);
        $this->assertGreaterThan(0, $result['sheetsNeeded']);
        $this->assertGreaterThanOrEqual(0, $result['usedAreaPercentage']);
        $this->assertLessThanOrEqual(100, $result['usedAreaPercentage']);
        $this->assertEquals(100, $result['usedAreaPercentage'] + $result['wastedAreaPercentage']);
    }

    /** @test */
    public function it_calculates_vertical_orientation_correctly()
    {
        $result = $this->calculator->calculateCuts(
            paperWidth: 100,
            paperHeight: 70,
            cutWidth: 10,
            cutHeight: 15,
            desiredCuts: 50,
            orientation: 'vertical'
        );

        $this->assertIsArray($result);
        $this->assertGreaterThan(0, $result['cutsPerSheet']);
        $this->assertGreaterThan(0, $result['sheetsNeeded']);
    }

    /** @test */
    public function it_calculates_maximum_orientation_correctly()
    {
        $result = $this->calculator->calculateCuts(
            paperWidth: 100,
            paperHeight: 70,
            cutWidth: 10,
            cutHeight: 15,
            desiredCuts: 50,
            orientation: 'maximum'
        );

        $this->assertIsArray($result);
        $this->assertGreaterThan(0, $result['cutsPerSheet']);
        $this->assertGreaterThan(0, $result['sheetsNeeded']);
    }

    /** @test */
    public function it_calculates_sheets_needed_correctly()
    {
        $result = $this->calculator->calculateCuts(
            paperWidth: 100,
            paperHeight: 70,
            cutWidth: 10,
            cutHeight: 15,
            desiredCuts: 37, // Número específico para testing
            orientation: 'horizontal'
        );

        $expectedSheets = ceil(37 / $result['cutsPerSheet']);
        $this->assertEquals($expectedSheets, $result['sheetsNeeded']);
    }

    /** @test */
    public function it_handles_perfect_fit_scenario()
    {
        // Papel 100x70, corte 50x35 = 2x2 = 4 cortes exactos
        $result = $this->calculator->calculateCuts(
            paperWidth: 100,
            paperHeight: 70,
            cutWidth: 50,
            cutHeight: 35,
            desiredCuts: 4,
            orientation: 'horizontal'
        );

        $this->assertEquals(4, $result['cutsPerSheet']);
        $this->assertEquals(1, $result['sheetsNeeded']);
        $this->assertEquals(100.0, $result['usedAreaPercentage']);
        $this->assertEquals(0.0, $result['wastedAreaPercentage']);
    }

    /** @test */
    public function it_handles_single_cut_per_sheet()
    {
        // Corte grande que solo cabe una vez
        $result = $this->calculator->calculateCuts(
            paperWidth: 100,
            paperHeight: 70,
            cutWidth: 90,
            cutHeight: 60,
            desiredCuts: 5,
            orientation: 'horizontal'
        );

        $this->assertEquals(1, $result['cutsPerSheet']);
        $this->assertEquals(5, $result['sheetsNeeded']);
    }

    /** @test */
    public function it_handles_zero_desired_cuts()
    {
        $result = $this->calculator->calculateCuts(
            paperWidth: 100,
            paperHeight: 70,
            cutWidth: 10,
            cutHeight: 15,
            desiredCuts: 0,
            orientation: 'horizontal'
        );

        $this->assertEquals(0, $result['sheetsNeeded']);
        $this->assertGreaterThan(0, $result['cutsPerSheet']);
    }

    /** @test */
    public function it_provides_consistent_results_across_orientations()
    {
        $params = [
            'paperWidth' => 100,
            'paperHeight' => 70,
            'cutWidth' => 20,
            'cutHeight' => 15,
            'desiredCuts' => 25
        ];

        $horizontal = $this->calculator->calculateCuts(...$params, orientation: 'horizontal');
        $vertical = $this->calculator->calculateCuts(...$params, orientation: 'vertical');
        $maximum = $this->calculator->calculateCuts(...$params, orientation: 'maximum');

        // Maximum should be >= other orientations
        $this->assertGreaterThanOrEqual($horizontal['cutsPerSheet'], $maximum['cutsPerSheet']);
        $this->assertGreaterThanOrEqual($vertical['cutsPerSheet'], $maximum['cutsPerSheet']);

        // All should produce valid results
        foreach ([$horizontal, $vertical, $maximum] as $result) {
            $this->assertGreaterThan(0, $result['cutsPerSheet']);
            $this->assertGreaterThan(0, $result['sheetsNeeded']);
            $this->assertLessThanOrEqual(100, $result['usedAreaPercentage']);
        }
    }

    /** @test */
    public function it_handles_small_cuts_on_large_paper()
    {
        // Muchos cortes pequeños en papel grande
        $result = $this->calculator->calculateCuts(
            paperWidth: 125,
            paperHeight: 125,
            cutWidth: 5,
            cutHeight: 5,
            desiredCuts: 1000,
            orientation: 'maximum'
        );

        // 125/5 = 25 cortes por lado, 25*25 = 625 cortes por pliego
        $this->assertEquals(625, $result['cutsPerSheet']);
        $this->assertEquals(2, $result['sheetsNeeded']); // ceil(1000/625) = 2
    }

    /** @test */
    public function it_calculates_areas_correctly()
    {
        $result = $this->calculator->calculateCuts(
            paperWidth: 60,
            paperHeight: 40,
            cutWidth: 10,
            cutHeight: 8,
            desiredCuts: 20,
            orientation: 'horizontal'
        );

        $paperArea = 60 * 40; // 2400
        $cutArea = 10 * 8; // 80
        $expectedUsedArea = $result['cutsPerSheet'] * $cutArea;
        $expectedUsedPercentage = ($expectedUsedArea * 100) / $paperArea;

        $this->assertEquals($expectedUsedPercentage, $result['usedAreaPercentage']);
        $this->assertEquals($paperArea, $result['paperArea']);
        $this->assertEquals($cutArea, $result['cutArea']);
    }

    /** @test */
    public function it_handles_floating_point_precision()
    {
        // Usar dimensiones que pueden causar problemas de precisión
        $result = $this->calculator->calculateCuts(
            paperWidth: 33.33,
            paperHeight: 66.66,
            cutWidth: 11.11,
            cutHeight: 22.22,
            desiredCuts: 15,
            orientation: 'horizontal'
        );

        // Los resultados deben ser números válidos sin errores de precisión
        $this->assertIsFloat($result['usedAreaPercentage']);
        $this->assertIsFloat($result['wastedAreaPercentage']);
        $this->assertGreaterThanOrEqual(0, $result['usedAreaPercentage']);
        $this->assertLessThanOrEqual(100, $result['usedAreaPercentage']);
    }

    /** @test */
    public function it_provides_detailed_calculation_results()
    {
        $result = $this->calculator->calculateCuts(
            paperWidth: 100,
            paperHeight: 70,
            cutWidth: 20,
            cutHeight: 14,
            desiredCuts: 30,
            orientation: 'horizontal'
        );

        // Verificar que todos los campos esperados estén presentes
        $expectedKeys = [
            'cutsPerSheet', 'sheetsNeeded', 'usedAreaPercentage', 'wastedAreaPercentage',
            'verticalCuts', 'horizontalCuts', 'paperArea', 'cutArea', 'totalCutArea',
            'orientation', 'arrangeResult', 'auxiliarResult'
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $result, "Missing key: $key");
        }
    }
}