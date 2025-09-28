<?php

namespace Tests\Unit\Filament\RelationManagers\Handlers;

use App\Filament\Resources\Documents\RelationManagers\Handlers\CustomItemQuickHandler;
use App\Filament\Resources\Documents\RelationManagers\Handlers\DigitalItemQuickHandler;
use App\Filament\Resources\Documents\RelationManagers\Handlers\PaperQuickHandler;
use App\Filament\Resources\Documents\RelationManagers\Handlers\ProductQuickHandler;
use App\Filament\Resources\Documents\RelationManagers\Handlers\SimpleItemQuickHandler;
use Tests\TestCase;

class QuickHandlerBasicTest extends TestCase
{
    /**
     * Test que todos los handlers implementan la interface correcta
     */
    public function test_all_handlers_implement_interface()
    {
        $handlers = [
            new CustomItemQuickHandler(),
            new DigitalItemQuickHandler(),
            new PaperQuickHandler(),
            new ProductQuickHandler(),
            new SimpleItemQuickHandler(),
        ];

        foreach ($handlers as $handler) {
            $this->assertInstanceOf(
                \App\Filament\Resources\Documents\RelationManagers\Contracts\QuickActionHandlerInterface::class,
                $handler,
                get_class($handler) . ' debe implementar QuickActionHandlerInterface'
            );
        }
    }

    /**
     * Test que todos los handlers retornan metadata válida
     */
    public function test_all_handlers_return_valid_metadata()
    {
        $handlers = [
            'CustomItemQuickHandler' => new CustomItemQuickHandler(),
            'DigitalItemQuickHandler' => new DigitalItemQuickHandler(),
            'PaperQuickHandler' => new PaperQuickHandler(),
            'ProductQuickHandler' => new ProductQuickHandler(),
            'SimpleItemQuickHandler' => new SimpleItemQuickHandler(),
        ];

        foreach ($handlers as $name => $handler) {
            // Test que retorna string no vacío para label
            $this->assertIsString($handler->getLabel());
            $this->assertNotEmpty($handler->getLabel(), "{$name}::getLabel() no debe estar vacío");

            // Test que retorna string no vacío para icon
            $this->assertIsString($handler->getIcon());
            $this->assertNotEmpty($handler->getIcon(), "{$name}::getIcon() no debe estar vacío");

            // Test que retorna string no vacío para color
            $this->assertIsString($handler->getColor());
            $this->assertNotEmpty($handler->getColor(), "{$name}::getColor() no debe estar vacío");

            // Test que retorna string no vacío para modal width
            $this->assertIsString($handler->getModalWidth());
            $this->assertNotEmpty($handler->getModalWidth(), "{$name}::getModalWidth() no debe estar vacío");

            // Test que retorna string no vacío para success notification
            $this->assertIsString($handler->getSuccessNotificationTitle());
            $this->assertNotEmpty($handler->getSuccessNotificationTitle(),
                "{$name}::getSuccessNotificationTitle() no debe estar vacío");

            // Test que isVisible retorna boolean
            $this->assertIsBool($handler->isVisible(), "{$name}::isVisible() debe retornar boolean");
        }
    }

    /**
     * Test que todos los handlers retornan form schema válido
     */
    public function test_all_handlers_return_valid_form_schema()
    {
        $handlers = [
            'CustomItemQuickHandler' => new CustomItemQuickHandler(),
            'DigitalItemQuickHandler' => new DigitalItemQuickHandler(),
            'PaperQuickHandler' => new PaperQuickHandler(),
            'ProductQuickHandler' => new ProductQuickHandler(),
            'SimpleItemQuickHandler' => new SimpleItemQuickHandler(),
        ];

        foreach ($handlers as $name => $handler) {
            $schema = $handler->getFormSchema();

            $this->assertIsArray($schema, "{$name}::getFormSchema() debe retornar array");
            $this->assertNotEmpty($schema, "{$name}::getFormSchema() no debe estar vacío");
        }
    }

    /**
     * Test de metadata específica de cada handler
     */
    public function test_custom_item_handler_metadata()
    {
        $handler = new CustomItemQuickHandler();

        $this->assertEquals('Item Personalizado Rápido', $handler->getLabel());
        $this->assertEquals('heroicon-o-pencil-square', $handler->getIcon());
        $this->assertEquals('secondary', $handler->getColor());
        $this->assertEquals('4xl', $handler->getModalWidth());
        $this->assertEquals('Item personalizado creado correctamente', $handler->getSuccessNotificationTitle());
    }

    public function test_product_handler_metadata()
    {
        $handler = new ProductQuickHandler();

        $this->assertEquals('Producto Rápido', $handler->getLabel());
        $this->assertEquals('heroicon-o-cube', $handler->getIcon());
        $this->assertEquals('purple', $handler->getColor());
        $this->assertEquals('5xl', $handler->getModalWidth());
        $this->assertEquals('Producto agregado correctamente', $handler->getSuccessNotificationTitle());
    }

    public function test_digital_item_handler_metadata()
    {
        $handler = new DigitalItemQuickHandler();

        $this->assertEquals('Item Digital Rápido', $handler->getLabel());
        $this->assertEquals('heroicon-o-computer-desktop', $handler->getIcon());
        $this->assertEquals('primary', $handler->getColor());
        $this->assertEquals('5xl', $handler->getModalWidth());
        $this->assertEquals('Item digital agregado correctamente', $handler->getSuccessNotificationTitle());
    }

    public function test_simple_item_handler_metadata()
    {
        $handler = new SimpleItemQuickHandler();

        $this->assertEquals('Item Sencillo Rápido', $handler->getLabel());
        $this->assertEquals('heroicon-o-bolt', $handler->getIcon());
        $this->assertEquals('success', $handler->getColor());
        $this->assertEquals('7xl', $handler->getModalWidth());
        $this->assertEquals('Item sencillo agregado correctamente', $handler->getSuccessNotificationTitle());
    }

    public function test_paper_handler_metadata()
    {
        $handler = new PaperQuickHandler();

        $this->assertEquals('Papel Rápido', $handler->getLabel());
        $this->assertEquals('heroicon-o-document-text', $handler->getIcon());
        $this->assertEquals('green', $handler->getColor());
        $this->assertEquals('5xl', $handler->getModalWidth());
        $this->assertEquals('Papel agregado correctamente', $handler->getSuccessNotificationTitle());
    }

    /**
     * Test que los handlers con setCalculationContext funcionan correctamente
     */
    public function test_handlers_with_calculation_context()
    {
        $handlersWithContext = [
            new ProductQuickHandler(),
            new DigitalItemQuickHandler(),
            new SimpleItemQuickHandler(),
        ];

        foreach ($handlersWithContext as $handler) {
            $this->assertTrue(
                method_exists($handler, 'setCalculationContext'),
                get_class($handler) . ' debe tener método setCalculationContext'
            );

            // Test que el método se puede llamar sin errores
            $mockContext = $this->createMock(\stdClass::class);
            $handler->setCalculationContext($mockContext);

            // Si llegamos aquí, el método funciona correctamente
            $this->assertTrue(true);
        }
    }

    /**
     * Test que los traits se cargan correctamente
     */
    public function test_traits_are_loaded()
    {
        // Test CalculatesFinishings trait
        $digitalHandler = new DigitalItemQuickHandler();
        $simpleHandler = new SimpleItemQuickHandler();

        $this->assertTrue(
            method_exists($digitalHandler, 'shouldShowSizeFields'),
            'DigitalItemQuickHandler debe usar CalculatesFinishings trait'
        );

        $this->assertTrue(
            method_exists($simpleHandler, 'shouldShowSizeFields'),
            'SimpleItemQuickHandler debe usar CalculatesFinishings trait'
        );

        // Test CalculatesProducts trait
        $productHandler = new ProductQuickHandler();

        $this->assertTrue(
            method_exists($productHandler, 'calculateProductTotal'),
            'ProductQuickHandler debe usar CalculatesProducts trait'
        );
    }

    /**
     * Test que la interface está correctamente definida
     */
    public function test_interface_methods_are_defined()
    {
        $reflection = new \ReflectionClass(
            \App\Filament\Resources\Documents\RelationManagers\Contracts\QuickActionHandlerInterface::class
        );

        $requiredMethods = [
            'getFormSchema',
            'handleCreate',
            'getLabel',
            'getIcon',
            'getColor',
            'getModalWidth',
            'getSuccessNotificationTitle',
            'isVisible'
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "QuickActionHandlerInterface debe tener método {$method}"
            );
        }
    }
}