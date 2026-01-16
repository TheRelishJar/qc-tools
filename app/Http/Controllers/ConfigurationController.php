<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ConfigurationService;
use App\Models\Industry;
use App\Models\IsoPurityLevel;
use App\Models\Product;
use App\Helpers\IsoHelper;
use Inertia\Inertia;
use Spatie\LaravelPdf\Facades\Pdf;

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

    /**
     * Export configuration as PDF
     */
    public function exportPdf(Request $request)
    {
        $validated = $request->validate([
            'particulate_class' => 'required|string',
            'water_class' => 'required|string',
            'oil_class' => 'required|string',
            'flow' => 'nullable|numeric',
            'config_index' => 'required|integer',
            'flow_index' => 'required|integer',
            'preset' => 'nullable|string',
        ]);

        // Generate the same configuration data
        $result = $this->configService->generateFromIsoClass(
            $validated['particulate_class'],
            $validated['water_class'],
            $validated['oil_class'],
            $validated['flow'] ?? null
        );

        // Get the selected configuration
        $configIndex = $validated['config_index'];
        $flowIndex = $validated['flow_index'];
        
        if (!isset($result['configurations'][$configIndex])) {
            abort(404, 'Configuration not found');
        }

        $config = $result['configurations'][$configIndex];
        $selectedFlowOption = $config['flow_options'][$flowIndex] ?? null;
        $selectedComponentConfig = $config['component_configurations'][$flowIndex] ?? null;

        if (!$selectedFlowOption || !$selectedComponentConfig) {
            abort(404, 'Flow configuration not found');
        }

        // Get purity descriptions
        $purityLevels = $this->getPurityLevelsGrouped();
        $purityDescriptions = [
            'particulate' => $this->getPurityDescriptionText('particle', $validated['particulate_class'], $purityLevels),
            'water' => $this->getPurityDescriptionText('water', $validated['water_class'], $purityLevels),
            'oil' => $this->getPurityDescriptionText('oil', $validated['oil_class'], $purityLevels),
        ];

        // Get all product data for detailed parts list
        $productDescriptions = $this->getProductDescriptions();
        
        // Build compressor data
        $compressorInfo = $this->getProductInfo($config['compressor'], $productDescriptions);
        
        // Build components data with all details
        $components = [];
        foreach ($selectedComponentConfig['components'] as $componentName) {
            $productInfo = $this->getProductInfo($componentName, $productDescriptions);
            
            // Check if this is the dryer (matches the product_range base code)
            $productRangeBase = explode(' ', $selectedFlowOption['product_range'])[0];
            $isDryer = strpos($componentName, $productRangeBase) !== false;
            
            $components[] = [
                'name' => $componentName,
                'image' => $productInfo['image_path'] ?? null,
                'description' => $productInfo['description'] ?? null,
                'refrigerant_dryer_note' => $productInfo['refrigerant_dryer_note'] ?? null,
                'desiccant_dryer_note' => $productInfo['desiccant_dryer_note'] ?? null,
                'qaf_note' => $productInfo['qaf_note'] ?? null,
                'is_dryer' => $isDryer,
                'flow_range' => $isDryer ? $selectedFlowOption['flow_range'] : null,
                'dewpoint' => $isDryer ? ($config['dewpoint'] ?? null) : null,
            ];
        }

        // Prepare data for PDF
        $pdfData = [
            'input' => [
                'particulate_class' => $validated['particulate_class'],
                'water_class' => $validated['water_class'],
                'oil_class' => $validated['oil_class'],
                'flow' => $validated['flow'] ?? null,
                'preset' => $validated['preset'] ?? null,
                'iso_class_display' => IsoHelper::formatIsoClass($result['iso_class']),
            ],
            'purityDescriptions' => $purityDescriptions,
            'configuration' => [
                'product_range' => $selectedFlowOption['product_range'],
                'dewpoint' => $config['dewpoint'] ?? null,
                'flow_range' => $selectedFlowOption['flow_range'],
                'compressor' => $config['compressor'],
                'compressor_image' => $compressorInfo['image_path'] ?? null,
                'compressor_description' => $compressorInfo['description'] ?? null,
                'components' => $components,
            ],
        ];

        // Generate PDF
       $pdf = Pdf::view('pdf.configuration', $pdfData)
            ->withBrowsershot(function ($browsershot) {
                $browsershot->setChromePath('/var/www/.cache/puppeteer/chrome/linux-143.0.7499.192/chrome-linux64/chrome')
                        ->noSandbox()
                        ->setOption('args', [
                            '--disable-dev-shm-usage',
                            '--disable-gpu',
                            '--disable-breakpad'
                        ])
                        ->setOption('env', [
                            'LD_LIBRARY_PATH' => '/usr/lib/x86_64-linux-gnu'
                        ]);
            })
            ->format('a4')
            ->name('configuration-' . date('Y-m-d-His') . '.pdf');

        return $pdf->download();
    }

    /**
     * Get purity description text for a given class
     */
    private function getPurityDescriptionText($type, $classValue, $purityLevels)
    {
        $level = collect($purityLevels[$type] ?? [])->firstWhere('level', $classValue);
        return $level['description'] ?? '';
    }

    /**
     * Get product info with smart matching (same logic as frontend)
     */
    private function getProductInfo($componentName, $productDescriptions)
    {
        // Try exact match first
        if (isset($productDescriptions[$componentName])) {
            return $productDescriptions[$componentName];
        }
        
        // Fall back to base code
        $baseCode = explode(' ', $componentName)[0];
        if (isset($productDescriptions[$baseCode])) {
            return $productDescriptions[$baseCode];
        }
        
        return [];
    }
}