<?php

namespace App\Services;

use App\Models\Application;
use App\Models\IsoConfiguration;
use App\Models\ProductRange;
use Illuminate\Support\Collection;

class ConfigurationService
{
    public function generateFromIndustryApplication(
        string $industryName,
        string $applicationName,
        ?float $flow = null
    ): array {
        // Find application's ISO classes
        $application = Application::whereHas(
            'industry', 
            function ($query) use ($industryName) {
                $query->where('name', $industryName);
            }
        )->where ('name', $applicationName)->first();

        if (!$application) {
            return [
                'success' => false,
                'message' => "Application not found: {$industryName} / {$applicationName}",
                'configurations' => [],
            ];
        }

        // Build ISO class string
        $isoClass = "{$application->particulate_class}.{$application->water_class}.{$application->oil_class}";

        // Generate configurations
        return $this->generateFromIsoClass(
            $application->particulate_class,
            $application->water_class,
            $application->oil_class,
            $flow
        );
    }

    // Generate congigurations from ISO class input
    public function generateFromIsoClass(
        string $particulateClass,
        string $waterClass,
        string $oilClass,
        ?float $flow = null
    ): array {
        $isoClass = "{$particulateClass}.{$waterClass}.{$oilClass}";

        $isoConfig = IsoConfiguration::where('iso_class', $isoClass)->first();

        if (!$isoConfig) {
            return [
                'success' => false,
                'message' => "ISO configuration not found: {$isoClass}",
                'configurations' => [],
            ];
        }

        $dryerInfo = $this->findDryerComponent($isoConfig);

        if (!$dryerInfo) {
            return [
                'success' => false,
                'message' => "No dryer component found in configuration",
                'configurations' => [],
            ];
        }

        // Parse dryer options
        $dryerOptions = array_map('trim', explode('/', $dryerInfo['raw']));

        $dewpoint = $this->extractDewpoint($dryerInfo['raw']);

        $configurations = [];

        foreach ($dryerOptions as $dryerOption) {
            $dryerType = $this->extractDryerType($dryerOption);
            
            $productRanges = $this->findProductRanges($waterClass, $dryerType, $dewpoint, $flow);
            
            foreach ($productRanges as $range) {
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

    // Find which QAS component contans the dryers
    private function findDryerComponent(IsoConfiguration $config): ?array {
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
                            'position' => $index +1,
                            'component' => $component,
                        ];
                    }
                }
            }
        }

        return null;

    }

    // Helper to extract dewpoint from dryer string
    private function extractDewpoint(string $dryerString): ?string {
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

    // Helper to extract dryer type from dryer option string
    private function extractDryerType(string $dryerOption): string {
        $parts = preg_split('/[\s(]/', $dryerOption);
        return trim($parts[0]);
    }

    // Find compatible product ranges
    private function findProductRanges(
        string $waterClass,
        string $dryerType,
        ?string $dewpoint,
        ?float $flow
    ): Collection {
        $query = ProductRange::where('water_class', $waterClass)
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


    // Build array of components for each configuration
    private function buildComponentArray (
        IsoConfiguration $config,
        int $dryerPosition,
        ProductRange $range,
        ?string $dewpoint
    ): array {
        $components = [];
        $qasComponents = ['qas1', 'qas2', 'qas3', 'qas4', 'qas5', 'qas6', 'qas7', 'qas8', 'qas9'];

        foreach ($qasComponents as $index => $component) {
            $position = $index +1;

            if ($position === $dryerPosition) {
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



}