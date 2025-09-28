<?php

namespace App\Traits;

use App\Enums\CompanyType;

trait CompanyTypeResource
{
    /**
     * Determinar si este resource debe estar visible según el tipo de empresa
     */
    public static function shouldRegisterNavigation(): bool
    {
        // Si no hay usuario autenticado, no mostrar
        if (!auth()->check()) {
            return false;
        }

        $user = auth()->user();
        $company = $user->company;

        // Si no tiene empresa, no mostrar
        if (!$company) {
            return false;
        }

        // Obtener el nombre del resource actual
        $resourceName = static::getResourceName();

        // Verificar si la empresa puede acceder a este resource
        return $company->canAccessResource($resourceName);
    }

    /**
     * Obtener el nombre del resource basado en la clase
     */
    protected static function getResourceName(): string
    {
        $className = class_basename(static::class);

        // Remover "Resource" del final y convertir a formato kebab-case
        $name = str_replace('Resource', '', $className);

        // Convertir CamelCase a kebab-case
        $name = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $name));

        // Mapear nombres específicos
        $nameMap = [
            'simple-item' => 'simple-items',
            'digital-item' => 'digital-items',
            'talonario-item' => 'talonario-items',
            'magazine-item' => 'magazine-items',
            'printing-machine' => 'printing-machines',
            'supplier-request' => 'supplier-requests',
            'supplier-relationship' => 'supplier-relationships',
            'user' => 'users',
            'role' => 'roles',
            'paper' => 'papers',
            'product' => 'products',
            'contact' => 'contacts',
            'document' => 'documents',
            'finishing' => 'finishings',
            // Plurales específicos
            'products' => 'products',
            'papers' => 'papers',
            'contacts' => 'contacts',
            'documents' => 'documents',
            'users' => 'users',
            'roles' => 'roles',
        ];

        return $nameMap[$name] ?? $name;
    }

    /**
     * Verificar acceso durante la navegación
     */
    public static function canAccess(): bool
    {
        return static::shouldRegisterNavigation();
    }
}