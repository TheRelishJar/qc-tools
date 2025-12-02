<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ConfigurationService;

class TestConfiguration extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'config:test {mode} {param1?} {param2?} {param3?}';

    /**
     * The console command description.
     */
    protected $description = 'Test the configuration service';

    /**
     * Execute the console command.
     */
    public function handle(ConfigurationService $service)
    {
        $mode = $this->argument('mode');

        if ($mode === 'iso') {
            return $this->testIsoMode($service);
        } elseif ($mode === 'industry') {
            return $this->testIndustryMode($service);
        } else {
            $this->error('Invalid mode. Use "iso" or "industry"');
            $this->info('');
            $this->info('Examples:');
            $this->info('  php artisan config:test iso 1 1 1');
            $this->info('  php artisan config:test iso 1 2 1 250');
            $this->info('  php artisan config:test industry "Carwash" "Touchless Wash Systems" 2110');
            return 1;
        }
    }

    /**
     * Test ISO class mode
     */
    private function testIsoMode(ConfigurationService $service)
    {
        $particulate = $this->argument('param1');
        $water = $this->argument('param2');
        $oil = $this->argument('param3');

        if (!$particulate || !$water || !$oil) {
            $this->error('Missing parameters for ISO mode');
            $this->info('Usage: php artisan config:test iso [particulate] [water] [oil] [flow?]');
            return 1;
        }

        $flow = $this->ask('Enter flow (CFM) or press Enter to see all ranges', null);
        $flow = $flow ? (float) $flow : null;

        $this->info("Testing ISO Class: {$particulate}.{$water}.{$oil}");
        if ($flow) {
            $this->info("Flow: {$flow} CFM");
        } else {
            $this->info("Flow: ALL (showing all product ranges)");
        }
        $this->info('');

        $result = $service->generateFromIsoClass($particulate, $water, $oil, $flow);

        $this->displayResults($result);
    }

    /**
     * Test industry/application mode
     */
    private function testIndustryMode(ConfigurationService $service)
    {
        $industry = $this->argument('param1') ?? $this->ask('Enter industry name');
        $application = $this->argument('param2') ?? $this->ask('Enter application name');
        
        if ($this->argument('param3')) {
            $flow = (float) $this->argument('param3');
        } else {
            $flowInput = $this->ask('Enter flow (CFM) or press Enter to see all ranges', null);
            $flow = $flowInput ? (float) $flowInput : null;
        }

        $this->info("Testing Industry/Application Mode");
        $this->info("Industry: {$industry}");
        $this->info("Application: {$application}");
        if ($flow) {
            $this->info("Flow: {$flow} CFM");
        } else {
            $this->info("Flow: ALL (showing all product ranges)");
        }
        $this->info('');

        $result = $service->generateFromIndustryApplication($industry, $application, $flow);

        $this->displayResults($result);
    }

    /**
     * Display results
     */
    private function displayResults(array $result)
    {
        if (!$result['success']) {
            $this->error($result['message']);
            return;
        }

        $this->info($result['message']);
        $this->info('ISO Class: ' . $result['iso_class']);
        $this->info('');

        if (empty($result['configurations'])) {
            $this->warn('No compatible configurations found!');
            if (isset($result['flow'])) {
                $this->info("The flow of {$result['flow']} CFM does not match any available product ranges.");
            }
            return;
        }

        $this->info("Found " . count($result['configurations']) . " configuration(s):");
        $this->info('');

        foreach ($result['configurations'] as $index => $config) {
            $configNum = $index + 1;
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("Configuration #{$configNum}: {$config['product_range']}");
            $this->line("Flow Range: {$config['flow_range']} CFM");
            $this->line("Dryer Type: {$config['dryer_type']}");
            $this->line("Compressor: {$config['compressor']}");
            $this->line('');
            $this->line('Components:');
            $this->line("  {$config['compressor']} → " . implode(' → ', $config['components']));
            $this->info('');
        }
    }
}