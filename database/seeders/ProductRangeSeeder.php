<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProductRange;
use Maatwebsite\Excel\Facades\Excel;

class ProductRangeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = storage_path('app/QC_ISO_Tool_Clean_Data.xlsx');
        
        // Load the Excel file
        $data = Excel::toArray([], $filePath);
        
        // Get the "Flow and Dewpoint to Product Ra" sheet (index 3)
        $rangeSheet = $data[3];
        
        // Skip header row and process product ranges
        foreach (array_slice($rangeSheet, 1) as $row) {
            $waterClass = (string) $row[0];
            $productRange = $row[1];
            $dewpoint = $row[2];
            $flowRange = $row[3];
            
            if (!$productRange || !$flowRange) {
                continue;
            }
            
            // Parse flow range (e.g., "2-11" -> min: 2, max: 11)
            $flows = explode('-', $flowRange);
            $minFlow = (float) trim($flows[0]);
            $maxFlow = (float) trim($flows[1] ?? $flows[0]);
            
            ProductRange::create([
                'water_class' => $waterClass,
                'product_range' => $productRange,
                'dewpoint' => $dewpoint,
                'min_flow' => $minFlow,
                'max_flow' => $maxFlow,
                'inlet_filters' => $row[4],
                'outlet_filters' => $row[5],
                'comment' => $row[6],
            ]);
        }
        
        $this->command->info('Product Ranges seeded successfully!');
    }
}
