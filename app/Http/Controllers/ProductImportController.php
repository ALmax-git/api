<?php

namespace App\Http\Controllers;

use App\Enums\ProductType;
use App\Exports\ProductsExport;
use App\Exports\ProductTemplateExport;
use App\Imports\ProductsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ProductImportController extends Controller
{
    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240',
            ],
            'type' => [
                'required',
                'in:' . implode(',', array_column(ProductType::cases(), 'value')),
            ],
            'category_id' => [
                'required',
                'exists:product_categories,id',
            ],
        ]);

        try {
            $import = new ProductsImport(
                $validated['type'],
                $validated['category_id']
            );

            Excel::import(
                $import,
                $request->file('file')
            );

            $rowCount = $import->getRowCount();
            $messages = $import->getMessages();

            return response()->json([
                'success' => true,
                'message' => "Successfully imported  products.",
                'imported_count' => $rowCount,
                'messages' => $messages,
            ]);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {

            $errors = collect($e->failures())
                ->map(fn($failure) => [
                    'row' => $failure->row(),
                    'column' => $failure->attribute(),
                    'errors' => $failure->errors(),
                    'values' => $failure->values(),
                ])
                ->values()
                ->all();

            return response()->json([
                'success' => false,
                'message' => 'Some rows could not be imported.',
                'errors' => $errors,
            ], 422);
        } catch (\Throwable $e) {

            Log::error('Product import failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to import products. Please try again.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }

    public function downloadTemplate(string $type)
    {
        abort_unless(
            in_array($type, array_column(ProductType::cases(), 'value')),
            404
        );

        $fileName = "Core-X_{$type}_Template.xlsx";

        $sampleData = $this->getSampleData($type);

        return Excel::download(
            new ProductTemplateExport($sampleData),
            $fileName
        );
    }
    public function export()
    {
        $client = Auth::user()->client;

        $fileName = $client->name . '[' . now()->format('Y-m-d') . '].xlsx';

        return Excel::download(
            new ProductsExport($client->id),
            $fileName
        );
    }
    private function getSampleData(string $type): array
    {
        $baseColumns = [
            [
                'name' => 'Product Name',
                'brand' => 'Brand',
                'sub_title' => 'Sub Title',
            ],
        ];

        if ($type === ProductType::ITEM->value) {
            $baseColumns[0] += [
                'description' => 'Description',
                'stock_price' => '100.00',
                'sale_price' => '120.00',
                'discount' => '10',
                'available_stock' => '50',
                'color' => '#ff0000',
                'size' => 'M',
                'si_unit' => 'g',
                'weight' => '500',
            ];
        }

        if ($type === ProductType::LIVESTOCK->value) {
            $baseColumns[0] += [
                'scientific_name' => 'Scientific Name',
                'breed' => 'Breed',
                'sale_price' => '500.00',
                'available_stock' => '10',
            ];
        }

        return $baseColumns;
    }
}
