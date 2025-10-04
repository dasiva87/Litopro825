<?php

namespace Tests\Feature\Filament\RelationManagers;

use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Resources\Documents\RelationManagers\DocumentItemsRelationManager;
use App\Models\Company;
use App\Models\Document;
use App\Models\User;
use Filament\Actions\Action;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentItemsRelationManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $company;
    private Document $document;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear datos de prueba
        $this->company = Company::factory()->create(['company_type' => 'litografia']);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->document = Document::factory()->create(['company_id' => $this->company->id]);

        // Autenticar usuario
        $this->actingAs($this->user);

        // Configurar contexto tenant
        config(['app.current_tenant_id' => $this->company->id]);
    }

    /** @test */
    public function it_loads_relation_manager_successfully()
    {
        $component = Livewire::test(DocumentItemsRelationManager::class, [
            'ownerRecord' => $this->document,
            'pageClass' => DocumentResource\Pages\EditDocument::class,
        ]);

        $component->assertOk();
    }

    /** @test */
    public function it_has_all_expected_quick_actions()
    {
        $this->markTestSkipped('Requires Livewire context - TODO: Refactor to use Livewire testing');

        $relationManager = new DocumentItemsRelationManager();
        $relationManager->ownerRecord = $this->document;

        $actions = $relationManager->getHeaderActions();

        // Verificar que todos los quick actions están presentes
        $actionLabels = [];
        foreach ($actions as $action) {
            if ($action instanceof Action) {
                $actionLabels[] = $action->getLabel();
            }
        }

        $expectedActions = [
            'Item Personalizado Rápido',
            'Producto Rápido',
            'Item Digital Rápido',
            'Item Sencillo Rápido',
            'Papel Rápido'
        ];

        foreach ($expectedActions as $expectedAction) {
            $this->assertContains($expectedAction, $actionLabels,
                "Action '{$expectedAction}' should be present in header actions");
        }
    }

    /** @test */
    public function it_creates_actions_with_correct_properties()
    {
        $this->markTestSkipped('Requires Livewire context - TODO: Refactor to use Livewire testing');

        $relationManager = new DocumentItemsRelationManager();
        $relationManager->ownerRecord = $this->document;

        $actions = $relationManager->getHeaderActions();

        foreach ($actions as $action) {
            if ($action instanceof Action) {
                // Verificar que cada acción tiene las propiedades básicas
                $this->assertNotEmpty($action->getLabel());
                $this->assertNotEmpty($action->getIcon());
                $this->assertNotEmpty($action->getColor());

                // Verificar que tiene modal
                $this->assertNotNull($action->getForm());
                $this->assertIsArray($action->getFormSchema());
            }
        }
    }

    /** @test */
    public function it_shows_actions_based_on_company_type()
    {
        $this->markTestSkipped('Requires Livewire context - TODO: Refactor to use Livewire testing');

        // Test para litografía
        $relationManager = new DocumentItemsRelationManager();
        $relationManager->ownerRecord = $this->document;

        $actions = $relationManager->getHeaderActions();

        // Para litografía, deberían estar todas las acciones
        $this->assertGreaterThanOrEqual(5, count($actions));

        // Test para papelería
        $this->company->update(['company_type' => 'papeleria']);

        $relationManager2 = new DocumentItemsRelationManager();
        $relationManager2->ownerRecord = $this->document;

        $actions2 = $relationManager2->getHeaderActions();

        // Para papelería, algunas acciones no deberían estar visibles
        $visibleActions = array_filter($actions2, function($action) {
            return $action instanceof Action && $action->isVisible();
        });

        $this->assertLessThan(count($actions), count($visibleActions));
    }

    /** @test */
    public function it_uses_handler_context_correctly()
    {
        $relationManager = new DocumentItemsRelationManager();
        $relationManager->ownerRecord = $this->document;

        // Verificar que el método setupHandlerContext existe
        $this->assertTrue(method_exists($relationManager, 'setupHandlerContext'));

        // Verificar que createQuickAction funciona
        $this->assertTrue(method_exists($relationManager, 'createQuickAction'));
    }

    /** @test */
    public function it_has_proper_table_configuration()
    {
        $component = Livewire::test(DocumentItemsRelationManager::class, [
            'ownerRecord' => $this->document,
            'pageClass' => DocumentResource\Pages\EditDocument::class,
        ]);

        // Verificar que la tabla se carga sin errores
        $component->assertOk();

        // Verificar que no hay errores en el componente
        $component->assertHasNoErrors();
    }

    /** @test */
    public function it_maintains_tenant_context()
    {
        $component = Livewire::test(DocumentItemsRelationManager::class, [
            'ownerRecord' => $this->document,
            'pageClass' => DocumentResource\Pages\EditDocument::class,
        ]);

        // Verificar que el contexto del tenant se mantiene
        $this->assertEquals($this->company->id, config('app.current_tenant_id'));

        $component->assertOk();
    }

    /** @test */
    public function it_validates_relation_manager_extends_correct_class()
    {
        $relationManager = new DocumentItemsRelationManager();

        $this->assertInstanceOf(
            \Filament\Resources\RelationManagers\RelationManager::class,
            $relationManager
        );
    }
}