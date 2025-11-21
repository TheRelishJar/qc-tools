<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IsoConfiguration;
use Maatwebsite\Excel\Facades\Excel;

class IsoConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = storage_path('app/QC_ISO_Tool_Clean_Data.xlsx');
        
        // Load the Excel file
        $data = Excel::toArray([], $filePath);
        
        // Get the "ISO Class Product Configuration" sheet (index 2)
        $configSheet = $data[2];
        
        // Skip header row and process configurations
        foreach (array_slice($configSheet, 1) as $row) {
            $isoClass = (string) $row[0];
            
            if (!$isoClass) {
                continue;
            }
            
            // Split ISO class to get individual components (e.g., "1.2.1" -> [1, 2, 1])
            $parts = explode('.', $isoClass);
            $particulateClass = (string) ($parts[0] ?? '-');
            $waterClass = (string) ($parts[1] ?? '-');
            $oilClass = (string) ($parts[2] ?? '-');
            
            IsoConfiguration::create([
                'iso_class' => $isoClass,
                'particulate_class' => $particulateClass,
                'water_class' => $waterClass,
                'oil_class' => $oilClass,
                'compressor' => $row[1],
                'qas1' => $row[2],
                'qas2' => $row[3],
                'qas3' => $row[4],
                'qas4' => $row[5],
                'qas5' => $row[6],
                'qas6' => $row[7],
                'qas7' => $row[8],
                'qas8' => $row[9],
                'qas9' => $row[10],
            ]);
        }
        
        $this->command->info('ISO Configurations seeded successfully!');
    }
}
