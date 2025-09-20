<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Lab404\Impersonate\Services\ImpersonateManager;

class ImpersonateController extends Controller
{
    public function __construct(
        protected ImpersonateManager $impersonateManager
    ) {}

    public function impersonate(Request $request, User $user): RedirectResponse
    {
        // Verificar que el usuario actual puede impersonar
        if (! auth()->user()->canImpersonate()) {
            abort(403, 'No tienes permisos para impersonar usuarios');
        }

        // Verificar que el usuario puede ser impersonado
        if (! $user->canBeImpersonated()) {
            abort(403, 'Este usuario no puede ser impersonado');
        }

        // Realizar la impersonación
        $this->impersonateManager->take(auth()->user(), $user);

        // Redirigir al panel de la empresa del usuario impersonado
        return redirect()->route('filament.admin.pages.dashboard')
            ->with('success', "Ahora estás impersonando a {$user->name}");
    }

    public function leaveImpersonation(Request $request): RedirectResponse
    {
        // Verificar que se está impersonando
        if (! auth()->user()->isImpersonated()) {
            return redirect()->route('filament.super-admin.pages.dashboard');
        }

        // Dejar la impersonación
        $this->impersonateManager->leave();

        // Redirigir de vuelta al super admin panel
        return redirect()->route('filament.super-admin.pages.dashboard')
            ->with('success', 'Has dejado de impersonar al usuario');
    }
}
