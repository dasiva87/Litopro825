<?php

namespace App\Filament\Resources\Documents\RelationManagers\Handlers;

use Filament\Forms\Components;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard\Step;

abstract class AbstractItemHandler
{
    abstract public function getEditForm($record): array;
    
    abstract public function fillForm($record): array;
    
    abstract public function handleUpdate($record, array $data): void;
    
    abstract public function getWizardStep(): Step;
    
    protected function makeSection(string $title, string $description = ''): Section
    {
        $section = Section::make($title);
        
        if ($description) {
            $section->description($description);
        }
        
        return $section;
    }
    
    protected function makeGrid(int $columns = 2): Grid
    {
        return Grid::make($columns);
    }
    
    protected function makeTextInput(string $name, string $label): Components\TextInput
    {
        return Components\TextInput::make($name)
            ->label($label)
            ->numeric()
            ->required();
    }
    
    protected function makeSelect(string $name, string $label, array $options): Components\Select
    {
        return Components\Select::make($name)
            ->label($label)
            ->options($options)
            ->required()
            ->searchable();
    }
}