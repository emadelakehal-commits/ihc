<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessExcelRequest;
use App\Services\ExcelImportService;
use Illuminate\Http\JsonResponse;

class ExcelImportController extends Controller
{
    public function __construct(
        private ExcelImportService $excelImportService
    ) {}

    /**
     * Process Excel file and create/update products and product items
     */
    public function processExcel(ProcessExcelRequest $request): JsonResponse
    {
        try {
            $fileName = $request->input('file');
            $language = $request->input('lang');

            // Check if file exists
            $filePath = storage_path('app/public/excel/' . $fileName);
            if (!file_exists($filePath)) {
                $altFilePath = storage_path('app/public/' . $fileName);
                if (file_exists($altFilePath)) {
                    $filePath = $altFilePath;
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Excel file not found. Please place the file in storage/app/public/excel/ or storage/app/public/ directory',
                        'searched_paths' => [
                            'storage/app/public/excel/' . $fileName,
                            'storage/app/public/' . $fileName
                        ]
                    ], 404);
                }
            }

            $result = $this->excelImportService->processExcelFile($filePath, $language);

            return response()->json([
                'success' => true,
                'message' => 'Excel file processed successfully',
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process Excel file',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
