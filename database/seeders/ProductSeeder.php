<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use Maatwebsite\Excel\Facades\Excel;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = storage_path('app/QC_ISO_Tool_Clean_Data.xlsx');
        
        // Load the Excel file
        $data = Excel::toArray([], $filePath);
        
        // Get the "Product Descriptions" sheet (index 4)
        $productSheet = $data[4];
        
        // Skip header row and process products
        foreach (array_slice($productSheet, 1) as $row) {
            $code = $row[0];
            $description = $row[1];
            
            if (!$code) {
                continue;
            }
            
            // Determine category based on product code
            $category = $this->determineCategory($code);
            
            Product::create([
                'code' => $code,
                'name' => $code, // Using code as name for now
                'description' => $description,
                'category' => $category,
                'image_path' => null,
            ]);
        }
        
        $this->command->info('Products seeded successfully!');
    }
    
    /**
     * Determine product category based on code
     */
    private function determineCategory(string $code): string
    {
        // Dryers
        if (in_array($code, ['QCMD', 'QMD', 'QHD', 'QHP', 'QBP', 'QED', 'QPVS', 'COOL', 'QPNC'])) {
            return 'dryer';
        }
        
        // Filters
        if (in_array($code, ['QMF', 'QCF', 'QAF', 'QSF', 'QDF'])) {
            return 'filter';
        }
        
        // Separators
        if (in_array($code, ['QWS'])) {
            return 'separator';
        }
        
        // Tanks
        if (in_array($code, ['Wet tank', 'Dry tank'])) {
            return 'tank';
        }
        
        return 'other';
    }
}
