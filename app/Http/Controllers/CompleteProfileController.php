<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CompleteProfileController extends Controller
{
    /**
     * Show the complete profile form
     */
    public function show()
    {
        $user = Auth::user();
        $company = $user->company;

        // Redirigir si el perfil ya está completo
        if ($company->status !== 'incomplete') {
            return redirect()->route('filament.admin.pages.dashboard')
                ->with('info', 'Tu perfil ya está completo.');
        }

        $countries = Country::all();
        $states = $company->country_id ? State::where('country_id', $company->country_id)->get() : collect();
        $cities = $company->state_id ? City::where('state_id', $company->state_id)->get() : collect();

        return view('complete-profile', compact('company', 'countries', 'states', 'cities'));
    }

    /**
     * Update the company profile
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $company = $user->company;

        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
            'country_id' => ['required', 'exists:countries,id'],
            'state_id' => ['required', 'exists:states,id'],
            'city_id' => ['required', 'exists:cities,id'],
        ]);

        try {
            DB::beginTransaction();

            // Actualizar información de la empresa
            $company->update([
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'country_id' => $request->country_id,
                'state_id' => $request->state_id,
                'city_id' => $request->city_id,
                'status' => 'active', // Cambiar status a active cuando se complete
            ]);

            DB::commit();

            return redirect()->route('filament.admin.pages.dashboard')
                ->with('success', '¡Perfil completado exitosamente! Ahora puedes acceder a todas las funcionalidades de GrafiRed.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->withErrors([
                'general' => 'Error al actualizar el perfil: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Skip profile completion (allow later)
     */
    public function skip()
    {
        return redirect()->route('filament.admin.pages.dashboard')
            ->with('info', 'Puedes completar tu perfil más tarde desde la configuración.');
    }

    /**
     * AJAX endpoint to get states by country
     */
    public function getStates(Request $request)
    {
        $request->validate([
            'country_id' => 'required|exists:countries,id',
        ]);

        $states = State::where('country_id', $request->country_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($states);
    }

    /**
     * AJAX endpoint to get cities by state
     */
    public function getCities(Request $request)
    {
        $request->validate([
            'state_id' => 'required|exists:states,id',
        ]);

        $cities = City::where('state_id', $request->state_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($cities);
    }
}
