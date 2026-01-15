<?php

namespace App\Services;

use App\Models\Application;
use App\Models\IsoConfiguration;
use App\Models\ProductRange;
use Illuminate\Support\Collection;

class ConfigurationService
{
    /**
     * Generate configurations from Industry/Application/Flow input
     * Flow is optional - if null, returns all product ranges
     */
    public function generateFromIndustryApplication(
        string $industryName,
        string $applicationName,
        ?float $flow = null
    ): array {
        // Step 1: Find the application and get its ISO class
        $application = Application::whereHas('industry', function ($query) use ($industryName) {
            $query->where('name', $industryName);
        })
        ->where('name', $applicationName)
        ->first();

        if (!$application) {
            return [
                'success' => false,
                'message' => "Application not found: {$industryName} / {$applicationName}",
                'configurations' => [],
            ];
        }

        // Step 2: Build ISO class string
        $isoClass = "{$application->particulate_class}.{$application->water_class}.{$application->oil_class}";

        // Step 3: Generate configurations with flow filtering
        return $this->generateFromIsoClass(
            $application->particulate_class,
            $application->water_class,
            $application->oil_class,
            $flow
        );
    }

    /**
     * Generate configurations from direct ISO class input
     */
    public function generateFromIsoClass(
        string $particulateClass,
        string $waterClass,
        string $oilClass,
        ?float $flow = null
    ): array {
        // Step 1: Build ISO class string
        $isoClass = "{$particulateClass}.{$waterClass}.{$oilClass}";

        // Step 2: Find the base configuration
        $isoConfig = IsoConfiguration::where('iso_class', $isoClass)->first();

        if (!$isoConfig) {
            return [
                'success' => false,
                'message' => "ISO configuration not found: {$isoClass}",
                'configurations' => [],
            ];
        }

        // Step 3: Find which QAS component contains the dryers
        $dryerInfo = $this->findDryerComponent($isoConfig);

        if (!$dryerInfo) {
            // No dryer specified - return the base configuration as-is
            return [
                'success' => true,
                'message' => "Configuration found (no dryer specified)",
                'iso_class' => $isoClass,
                'flow' => $flow,
                'configurations' => [
                    [
                        'dryer_type' => 'N/A',
                        'dewpoint' => null,
                        'configuration_name' => 'No Dryer Required',
                        'flow_options' => [
                            [
                                'product_range' => 'No dryer required',
                                'flow_range' => 'N/A',
                                'min_flow' => null,
                                'max_flow' => null,
                            ]
                        ],
                        'component_configurations' => [
                            [
                                'components' => $this->buildBaseComponentArray($isoConfig),
                                'product_range_name' => null,
                            ]
                        ],
                        'compressor' => $isoConfig->compressor,
                        'iso_class' => $isoClass,
                    ]
                ],
            ];
        }

        // Step 4: Parse dryer options (split by /)
        $dryerOptions = array_map('trim', explode('/', $dryerInfo['raw']));

        // Step 5: Extract dewpoint from dryer string
        $dewpoint = $this->extractDewpoint($dryerInfo['raw']);

        // Step 6: Group product ranges by dryer type
        $configurations = [];

        foreach ($dryerOptions as $dryerOption) {
            $dryerType = $this->extractDryerType($dryerOption);

            // Find ALL product ranges for this dryer type (don't filter by flow yet)
            $productRanges = $this->findProductRanges($waterClass, $dryerType, $dewpoint, null);

            if ($productRanges->isEmpty()) {
                continue;
            }

            // Sort by min_flow ascending
            $productRanges = $productRanges->sortBy('min_flow');

            // Build flow_options array
            $flowOptions = [];
            foreach ($productRanges as $range) {
                // If user specified a flow, only include ranges that match
                if ($flow !== null) {
                    if ($flow < $range->min_flow || $flow > $range->max_flow) {
                        continue;
                    }
                }

                $flowOptions[] = [
                    'product_range' => $range->product_range,
                    'flow_range' => "{$range->min_flow}-{$range->max_flow}",
                    'min_flow' => $range->min_flow,
                    'max_flow' => $range->max_flow,
                ];
            }

            // Skip this dryer type if no flow options match
            if (empty($flowOptions)) {
                continue;
            }

            // Build components array for EACH flow option (they may have different product names)
            $configurationsWithComponents = [];
            foreach ($flowOptions as $option) {
                $range = $productRanges->firstWhere('product_range', $option['product_range']);
                $components = $this->buildComponentArray($isoConfig, $dryerInfo['position'], $range, $dewpoint);
                $configurationsWithComponents[] = [
                    'components' => $components,
                    'product_range_name' => $range->product_range, // Full product name for description lookup
                ];
            }

            // Create one configuration per dryer type
            $configurations[] = [
                'dryer_type' => $dryerType,
                'dewpoint' => $dewpoint,
                'configuration_name' => $dewpoint ? "{$dryerType} ({$dewpoint})" : $dryerType,
                'flow_options' => $flowOptions,
                'component_configurations' => $configurationsWithComponents, // Array of component arrays
                'compressor' => $isoConfig->compressor,
                'iso_class' => $isoClass,
            ];
        }

        return [
            'success' => true,
            'message' => count($configurations) > 0 
                ? "Found " . count($configurations) . " configuration(s)"
                : "No compatible configurations found for this flow",
            'iso_class' => $isoClass,
            'flow' => $flow,
            'configurations' => $configurations,
        ];
    }

    /**
     * Find which QAS component contains the dryers
     */
    private function findDryerComponent(IsoConfiguration $config): ?array
    {
        $qasComponents = [
            'qas1', 'qas2', 'qas3', 'qas4', 'qas5',
            'qas6', 'qas7', 'qas8', 'qas9'
        ];

        $dryerProducts = ['QCMD', 'QHD', 'QHP', 'QBP', 'QED', 'QPVS', 'COOL', 'QPNC', 'QMD'];

        foreach ($qasComponents as $index => $component) {
            $value = $config->$component;
            if (!$value) continue;

            // Check if this component contains dryer products and has / or (-
            if ((str_contains($value, '/') || str_contains($value, '(-'))) {
                foreach ($dryerProducts as $dryer) {
                    if (str_contains($value, $dryer)) {
                        return [
                            'raw' => $value,
                            'position' => $index + 1, // QAS1 = position 1
                            'component' => $component,
                        ];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Extract dewpoint from dryer string
     */
    private function extractDewpoint(string $dryerString): ?string
    {
        if (str_contains($dryerString, '(-100F)') || str_contains($dryerString, '(-100)')) {
            return '-100F';
        }
        if (str_contains($dryerString, '(-40F)') || str_contains($dryerString, '(-40)')) {
            return '-40F';
        }
        if (str_contains($dryerString, '(-5F)') || str_contains($dryerString, '(-5)')) {
            return '-5F';
        }
        return null;
    }

    /**
     * Extract dryer type from dryer option string
     */
    private function extractDryerType(string $dryerOption): string
    {
        // Split on space or parenthesis to get the base type
        $parts = preg_split('/[\s(]/', $dryerOption);
        return trim($parts[0]);
    }

    /**
     * Find compatible product ranges
     */
    private function findProductRanges(
        string $waterClass,
        string $dryerType,
        ?string $dewpoint,
        ?float $flow
    ): Collection {
        // Water class 5 uses the same product ranges as water class 4
        $lookupWaterClass = ($waterClass === '5') ? '4' : $waterClass;
        
        $query = ProductRange::where('water_class', $lookupWaterClass)
            ->where('product_range', 'like', "{$dryerType}%");

        // Filter by dewpoint if provided
        if ($dewpoint !== null) {
            $query->where('dewpoint', $dewpoint);
        }

        // Filter by flow if provided
        if ($flow !== null) {
            $query->where('min_flow', '<=', $flow)
                  ->where('max_flow', '>=', $flow);
        }

        return $query->get();
    }

    /**
     * Build the component array for a configuration
     */
    private function buildComponentArray(
        IsoConfiguration $config,
        int $dryerPosition,
        ProductRange $range,
        ?string $dewpoint
    ): array {
        $components = [];
        $qasComponents = ['qas1', 'qas2', 'qas3', 'qas4', 'qas5', 'qas6', 'qas7', 'qas8', 'qas9'];

        foreach ($qasComponents as $index => $component) {
            $position = $index + 1;

            if ($position === $dryerPosition) {
                // Use the full product range name (e.g., "QCMD 4-11", "QCMD >= 12")
                $components[] = $range->product_range;
            } else {
                $value = $config->$component;
                if ($value) {
                    $components[] = $value;
                }
            }
        }

        return $components;
    }

    /**
     * Build base component array without dryer substitution
     */
    private function buildBaseComponentArray(IsoConfiguration $config): array
    {
        $components = [];
        $qasComponents = ['qas1', 'qas2', 'qas3', 'qas4', 'qas5', 'qas6', 'qas7', 'qas8', 'qas9'];

        foreach ($qasComponents as $component) {
            $value = $config->$component;
            if ($value) {
                $components[] = $value;
            }
        }

        return $components;
    }
}