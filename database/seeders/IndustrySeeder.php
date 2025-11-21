<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Industry;
use Maatwebsite\Excel\Facades\Excel;

class IndustrySeeder extends Seeder
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
        
        // Skip header row and extract unique industries
        $industries = [];
        foreach (array_slice($industrySheet, 1) as $row) {
            $industryName = $row[0];
            if ($industryName && !isset($industries[$industryName])) {
                $industries[$industryName] = true;
            }
        }
        
        // Insert industries into database
        foreach (array_keys($industries) as $industryName) {
            Industry::create([
                'name' => $industryName,
                'description' => null,
            ]);
        }
        
        $this->command->info('Industries seeded successfully!');
    }
}
