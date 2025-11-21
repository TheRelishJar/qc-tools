<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Industry;
use App\Models\Application;
use Maatwebsite\Excel\Facades\Excel;

class ApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = storage_path('app/QC_ISO_Tool_Clean_Data.xlsx');
        
        // Load the Excel file
        $data = Excel::toArray([], $filePath);
        
        // Get the "Industry ISO Map" sheet (index 1)
        $industrySheet = $data[1];
        
        // Skip header row and process applications
        foreach (array_slice($industrySheet, 1) as $row) {
            $industryName = $row[0];
            $applicationName = $row[1];
            $description = $row[2];
            $particulateClass = (string) $row[3];
            $waterClass = (string) $row[4];
            $oilClass = (string) $row[5];
            
            if (!$industryName || !$applicationName) {
                continue;
            }
            
            // Find the industry
            $industry = Industry::where('name', $industryName)->first();
            
            if ($industry) {
                Application::create([
                    'industry_id' => $industry->id,
                    'name' => $applicationName,
                    'description' => $description,
                    'particulate_class' => $particulateClass,
                    'water_class' => $waterClass,
                    'oil_class' => $oilClass,
                ]);
            }
        }
        
        $this->command->info('Applications seeded successfully!');
    }
}
