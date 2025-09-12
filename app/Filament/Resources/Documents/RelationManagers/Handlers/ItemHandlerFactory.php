<?php

namespace App\Filament\Resources\Documents\RelationManagers\Handlers;

use App\Models\SimpleItem;
use App\Models\MagazineItem;
use App\Models\Product;
use App\Models\DigitalItem;
use App\Models\TalonarioItem;
use App\Models\CustomItem;

class ItemHandlerFactory
{
    private static array $handlers = [];
    
    public static function make(string $itemableType): ?AbstractItemHandler
    {
        if (!isset(self::$handlers[$itemableType])) {
            self::$handlers[$itemableType] = self::createHandler($itemableType);
        }
        
        return self::$handlers[$itemableType];
    }
    
    private static function createHandler(string $itemableType): ?AbstractItemHandler
    {
        return match($itemableType) {
            'App\\Models\\SimpleItem' => new SimpleItemHandler(),
            'App\\Models\\MagazineItem' => new MagazineItemHandler(),
            'App\\Models\\Product' => new ProductHandler(),
            'App\\Models\\DigitalItem' => new DigitalItemHandler(),
            'App\\Models\\TalonarioItem' => new TalonarioItemHandler(),
            'App\\Models\\CustomItem' => new CustomItemHandler(),
            default => null,
        };
    }
    
    public static function getWizardSteps(): array
    {
        $handlers = [
            new SimpleItemHandler(),
            new MagazineItemHandler(),
            new TalonarioItemHandler(),
            new DigitalItemHandler(),
        ];
        
        return collect($handlers)
            ->map(fn($handler) => $handler->getWizardStep())
            ->toArray();
    }
}