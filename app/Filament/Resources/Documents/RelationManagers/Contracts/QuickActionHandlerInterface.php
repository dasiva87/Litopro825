<?php

namespace App\Filament\Resources\Documents\RelationManagers\Contracts;

use App\Models\Document;

interface QuickActionHandlerInterface
{
    /**
     * Get the form schema for the quick action
     */
    public function getFormSchema(): array;

    /**
     * Handle the creation of the item
     */
    public function handleCreate(array $data, Document $document): void;

    /**
     * Get the action label
     */
    public function getLabel(): string;

    /**
     * Get the action icon
     */
    public function getIcon(): string;

    /**
     * Get the action color
     */
    public function getColor(): string;

    /**
     * Get the modal width
     */
    public function getModalWidth(): string;

    /**
     * Get the success notification title
     */
    public function getSuccessNotificationTitle(): string;

    /**
     * Check if this action is visible for the current company
     */
    public function isVisible(): bool;
}