<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Support\Facades\DB;

class ProjectsController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id ?? config('app.current_tenant_id');

        // Obtener todos los códigos de proyecto únicos
        $projects = Document::where('company_id', $companyId)
            ->whereNotNull('reference')
            ->where('reference', '!=', '')
            ->select(
                'reference',
                DB::raw('MIN(date) as first_date'),
                DB::raw('MAX(updated_at) as last_update'),
                DB::raw('COUNT(*) as documents_count'),
                DB::raw('SUM(total) as total_amount')
            )
            ->groupBy('reference')
            ->orderBy('last_update', 'desc')
            ->get()
            ->map(function ($project) {
                return [
                    'code' => $project->reference,
                    'first_date' => $project->first_date,
                    'last_update' => $project->last_update,
                    'documents_count' => $project->documents_count,
                    'total_amount' => $project->total_amount,
                ];
            })
            ->toArray();

        return view('filament.pages.projects-simple', compact('projects'));
    }
}
