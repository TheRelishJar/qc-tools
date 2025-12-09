<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ConfigurationService;
use App\Models\Industry;
use App\Models\Application;
use App\Models\IsoPurityLevel;
use Inertia\Inertia;

class ConfigurationController extends Controller
{
    public function __construct(
        private ConfigurationService $configService
    ) {}

    /**
     * Show the configuration tool page
     */
    public function index()
    {
        return Inertia::render('ConfigurationTool/Index', [
            'industries' => Industry::orderBy('name')->get(),
            'purityLevels' => $this->getPurityLevelsGrouped(),
        ]);
    }

    /**
     * Get applications for a specific industry
     */
    public function getApplications(Industry $industry)
    {
        return response()->json([
            'applications' => $industry->applications()->orderBy('name')->get(),
        ]);
    }

    /**
     * Generate configurations
     */
    public function generate(Request $request)
    {
        // Base validation
        $rules = [
            'mode' => 'required|in:industry,iso',
            'flow' => 'nullable|numeric|min:0',
        ];

        // Add mode-specific validation
        if ($request->mode === 'industry') {
            $rules['industry_id'] = 'required|exists:industries,id';
            $rules['application_id'] = 'required|exists:applications,id';
        } else if ($request->mode === 'iso') {
            $rules['particulate_class'] = 'required|string';
            $rules['water_class'] = 'required|string';
            $rules['oil_class'] = 'required|string';
        }

        $validated = $request->validate($rules);

        if ($validated['mode'] === 'industry') {
            $application = Application::with('industry')->findOrFail($validated['application_id']);
            
            $result = $this->configService->generateFromIndustryApplication(
                $application->industry->name,
                $application->name,
                $validated['flow'] ?? null
            );

            $result['input'] = [
                'mode' => 'industry',
                'industry' => $application->industry->name,
                'application' => $application->name,
                'flow' => $validated['flow'] ?? null,
            ];
        } else {
            $result = $this->configService->generateFromIsoClass(
                $validated['particulate_class'],
                $validated['water_class'],
                $validated['oil_class'],
                $validated['flow'] ?? null
            );

            $result['input'] = [
                'mode' => 'iso',
                'particulate_class' => $validated['particulate_class'],
                'water_class' => $validated['water_class'],
                'oil_class' => $validated['oil_class'],
                'flow' => $validated['flow'] ?? null,
            ];
        }

        return Inertia::render('ConfigurationTool/Results', [
            'result' => $result,
        ]);
    }

    /**
     * Get purity levels grouped by type
     */
    private function getPurityLevelsGrouped()
    {
        $purityLevels = IsoPurityLevel::all()->groupBy('iso_class_type');

        return [
            'particle' => $purityLevels->get('particle', collect())->map(function ($item) {
                return [
                    'level' => $item->level,
                    'description' => $item->purity_description,
                ];
            })->values(),
            'water' => $purityLevels->get('water', collect())->map(function ($item) {
                return [
                    'level' => $item->level,
                    'description' => $item->purity_description,
                ];
            })->values(),
            'oil' => $purityLevels->get('oil', collect())->map(function ($item) {
                return [
                    'level' => $item->level,
                    'description' => $item->purity_description,
                ];
            })->values(),
        ];
    }
}