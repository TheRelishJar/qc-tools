<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ConfigurationService;
use App\Models\Industry;
use App\Models\IsoPurityLevel;
use App\Models\Product;
use App\Helpers\IsoHelper;
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
            'productDescriptions' => $this->getProductDescriptions(),
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
     * Generate configurations - ISO mode only
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'particulate_class' => 'required|string',
            'water_class' => 'required|string',
            'oil_class' => 'required|string',
            'flow' => 'nullable|numeric|min:0',
        ]);

        $result = $this->configService->generateFromIsoClass(
            $validated['particulate_class'],
            $validated['water_class'],
            $validated['oil_class'],
            $validated['flow'] ?? null
        );

        $result['input'] = [
            'particulate_class' => $validated['particulate_class'],
            'water_class' => $validated['water_class'],
            'oil_class' => $validated['oil_class'],
            'flow' => $validated['flow'] ?? null,
            'iso_class_display' => IsoHelper::formatIsoClass($result['iso_class']),
        ];

        // Return JSON for AJAX requests, Inertia for normal requests
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'props' => [
                    'result' => $result,
                ],
            ]);
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

    /**
     * Get product descriptions with smart flow range matching
     */
    private function getProductDescriptions()
    {
        $products = Product::all();
        $lookup = [];

        foreach ($products as $product) {
            // Build lookup key based on whether product has a flow_range
            if ($product->flow_range) {
                // For products with flow ranges (like QCMD), use "CODE RANGE" as key
                // e.g., "QCMD 4-11", "QCMD 12-64"
                $key = $product->code . ' ' . $product->flow_range;
            } else {
                // For products without flow ranges, just use the code
                // e.g., "QHD", "QWS", "Wet tank"
                $key = $product->code;
            }

            $lookup[$key] = [
                'description' => $product->description,
                'image_path' => $product->image_path,
                'refrigerant_dryer_note' => $product->refrigerant_dryer_note,
                'desiccant_dryer_note' => $product->desiccant_dryer_note,
                'qaf_note' => $product->qaf_note,
            ];
        }

        return $lookup;
    }
}