<?php

/**
 * Example Job - Generate Report
 */

namespace App\Jobs;

use App\Models\User;
use App\Models\Product;
use NeoCore\System\Core\Database;

class GenerateReportJob
{
    public function handle(array $data): void
    {
        $reportType = $data['type'] ?? 'users';
        $format = $data['format'] ?? 'csv';

        logger("Generating {$reportType} report in {$format} format");

        $db = Database::connection();

        if ($reportType === 'users') {
            $userModel = new User($db);
            $users = $userModel->findAll(1000);
            $this->generateCsvReport($users, 'users_report.csv');
        } elseif ($reportType === 'products') {
            $productModel = new Product($db);
            $products = $productModel->findAll(1000);
            $this->generateCsvReport($products, 'products_report.csv');
        }

        logger("Report generated successfully");
    }

    private function generateCsvReport(array $data, string $filename): void
    {
        $filepath = STORAGE_PATH . '/reports/' . $filename;
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $fp = fopen($filepath, 'w');

        if (!empty($data)) {
            // Header
            fputcsv($fp, array_keys($data[0]));

            // Rows
            foreach ($data as $row) {
                fputcsv($fp, $row);
            }
        }

        fclose($fp);
        logger("CSV saved to {$filepath}");
    }
}
