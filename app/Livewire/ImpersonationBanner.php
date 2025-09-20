<?php

namespace App\Livewire;

use Livewire\Component;

class ImpersonationBanner extends Component
{
    public function leaveImpersonation()
    {
        return redirect()->route('superadmin.leave-impersonation');
    }

    public function render()
    {
        // Solo mostrar si el usuario está siendo impersonado
        if (! auth()->check() || ! session()->has('impersonated_by')) {
            return '';
        }

        return view('livewire.impersonation-banner');
    }
}
