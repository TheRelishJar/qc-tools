<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IsoPurityLevel;
use Maatwebsite\Excel\Facades\Excel;

class IsoPurityLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = storage_path('app/QC_ISO_Tool_Clean_Data.xlsx');
        
        // Load the Excel file
        $data = Excel::toArray([], $filePath);
        
        // Get the "ISO Purity Map" sheet (index 0)
        $puritySheet = $data[0];
        
        // Skip header row and process purity levels
        foreach (array_slice($puritySheet, 1) as $row) {
            $isoClassType = $row[0];
            $level = (string) $row[1];
            $purityDescription = $row[2];
            
            if (!$isoClassType || $level === null || $level === '') {
                continue;
            }
            
            IsoPurityLevel::create([
                'iso_class_type' => $isoClassType,
                'level' => $level,
                'purity_description' => $purityDescription,
            ]);
        }
        
        $this->command->info('ISO Purity Levels seeded successfully!');
    }
}
