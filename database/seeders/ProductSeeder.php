<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = storage_path('app/QC_ISO_Tool_Clean_Data.xlsx');
        
        if (!file_exists($filePath)) {
            $this->command->error("Excel file not found at: {$filePath}");
            return;
        }
        
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheetByName('Product Descriptions');
        
        if (!$sheet) {
            $this->command->error("Sheet 'Product Descriptions' not found");
            return;
        }
        
        $rows = $sheet->toArray();
        
        // Skip header row
        array_shift($rows);
        
        foreach ($rows as $cells) {
            // Column A (index 0) = Product Code
            // Column B (index 1) = Flow Range
            // Column C (index 2) = Description
            // Column D (index 3) = Refrigerant Dryer Note
            // Column E (index 4) = Desiccant Dryer Note
            // Column F (index 5) = QAF Note
            // Column G (index 6) = Image Link
            
            $productCode = trim($cells[0] ?? '');
            
            // Skip empty rows
            if (empty($productCode)) {
                continue;
            }
            
            DB::table('products')->insert([
                'code' => $productCode,
                'flow_range' => $cells[1] ?? null, // Column B - Flow Range
                'name' => $productCode,
                'description' => $cells[2] ?? null, // Column C
                'refrigerant_dryer_note' => $cells[3] ?? null, // Column D
                'desiccant_dryer_note' => $cells[4] ?? null, // Column E
                'qaf_note' => $cells[5] ?? null, // Column F
                'image_path' => $cells[6] ?? null, // Column G - Image Link
                'category' => $this->guessCategory($productCode),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        $count = DB::table('products')->count();
        $this->command->info("Seeded {$count} products");
    }
    
    private function guessCategory(string $code): string
    {
        if (str_contains($code, 'tank')) return 'tank';
        if (in_array($code, ['QWS'])) return 'separator';
        if (in_array($code, ['QMF', 'QCF', 'QAF', 'QSF', 'QDF'])) return 'filter';
        if (in_array($code, ['COOL', 'QPNC', 'QED', 'QPVS', 'QMD', 'QHD', 'QHP', 'QBP', 'QCMD'])) return 'dryer';
        return 'other';
    }
}