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
                        'product_range' => 'No dryer required',
                        'flow_range' => 'N/A',
                        'min_flow' => null,
                        'max_flow' => null,
                        'compressor' => $isoConfig->compressor,
                        'components' => $this->buildBaseComponentArray($isoConfig),
                        'iso_class' => $isoClass,
                    ]
                ],
            ];
        }

        // Step 4: Parse dryer options (split by /)
        $dryerOptions = array_map('trim', explode('/', $dryerInfo['raw']));

        // Step 5: Extract dewpoint from dryer string
        $dewpoint = $this->extractDewpoint($dryerInfo['raw']);

        // Step 6: For each dryer option, find compatible product ranges
        $configurations = [];

        foreach ($dryerOptions as $dryerOption) {
            $dryerType = $this->extractDryerType($dryerOption);

            // Find product ranges for this dryer type
            $productRanges = $this->findProductRanges($waterClass, $dryerType, $dewpoint, $flow);

            foreach ($productRanges as $range) {
                // Build the configuration with this specific product range
                $components = $this->buildComponentArray($isoConfig, $dryerInfo['position'], $range, $dewpoint);

                $configurations[] = [
                    'dryer_type' => $dryerType,
                    'product_range' => $range->product_range,
                    'flow_range' => "{$range->min_flow}-{$range->max_flow}",
                    'min_flow' => $range->min_flow,
                    'max_flow' => $range->max_flow,
                    'compressor' => $isoConfig->compressor,
                    'components' => $components,
                    'iso_class' => $isoClass,
                ];
            }
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
                // Replace with specific product range
                $value = $range->product_range;
                if ($dewpoint) {
                    $value .= " ({$dewpoint})";
                }
                $components[] = $value;
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