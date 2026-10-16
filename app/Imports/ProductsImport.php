<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;

use App\Models\LiveStock;
use App\Models\File;
use App\Enums\ProductType;
use App\Enums\ProductStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Illuminate\Validation\Rule;

class ProductsImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading, WithBatchInserts
{
    protected $clientId;
    protected $userId;
    protected $categoryId;
    protected $type;
    protected $_messages     = [];

    public function __construct($type, $categoryId)
    {
        $this->clientId = Auth::user()->client->id;
        $this->userId = Auth::user()->id;
        $this->categoryId = $categoryId;
        $this->type = $type;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Check for duplicate product
        $duplicateExists = Product::where('client_id', $this->clientId)
            ->where('name', $row['name'])
            ->where('brand', $row['brand'])
            ->exists();

        if ($duplicateExists) {
            $this->_messages[] = [
                'row' => $row['__row'] ?? '',
                'errors' => ['Duplicate product skipped: ' . $row['name'] . ' - ' . $row['brand']],
            ];
            return null; // Skip duplicate
        }

        // Calculate real price
        $realPrice = $this->calculateRealPrice(
            $row['sale_price'] ?? 0,
            $row['discount'] ?? 0
        );

        // Create product
        $product = new Product();
        $product->fill(
            [
                'global_key' => str_replace('-', '', str_replace(' ', '', str_replace(':', '', uniqid() . now()))),
                'name' => $row['name'],
                'brand' => $row['brand'],
                'sub_title' => $row['sub_title'] ?? '',
                'description' => $row['description'] ?? 'Description Not Available',
                'category_id' => $this->categoryId,
                'discount' => $row['discount'] ?? 0,
                'stock_price' => $row['stock_price'] > 0 ? $row['stock_price'] : ($row['sale_price'] ?? 0),
                'sale_price' => $row['sale_price'] ?? 0,
                'real_price' => $realPrice,
                'available_stock' => $row['available_stock'] ?? 0,
                'color' => $row['color'] ?? '#fff',
                'size' => $row['size'] ?? '',
                'si_unit' => $row['si_unit'] ?? 'g',
                'weight' => $row['weight'] ?? 0,
                'status' => ProductStatus::NEW->value,
                'is_livestock' => $this->type == ProductType::LIVESTOCK->value,
                'type' => $this->type,
                'user_id' => $this->userId,
                'client_id' => $this->clientId,
            ]
        );
        // dd($product);
        // Save the product first to get an ID
        // $product->save();

        // Handle livestock if applicable
        if ($this->type == ProductType::LIVESTOCK->value) {
            $livestock = new LiveStock([
                'scientific_name' => $row['scientific_name'] ?? '',
                'breed' => $row['breed'] ?? '',
                'product_id' => $product->id,
                'client_id' => $this->clientId,
            ]);
            $livestock->save();

            // Update product with livestock_id
            $product->livestock_id = $livestock->id;
            $product->save();
        }

        return $product;
    }

    private function calculateRealPrice($salePrice, $discount)
    {
        if ($discount > 0) {
            return $salePrice - ($salePrice / 100 * $discount);
        }
        return $salePrice;
    }

    /**
     * Validation rules
     */
    public function rules(): array
    {
        $baseRules = [
            'name' => 'required|string|max:100',
            'brand' => 'required|string|max:100',
            'sale_price' => 'required|numeric|min:0',
            'available_stock' => 'required|numeric|min:0',
        ];

        if ($this->type == ProductType::ITEM->value) {
            $baseRules['description'] = 'nullable|string';
            $baseRules['discount'] = 'nullable|numeric|min:0|max:100';
            $baseRules['stock_price'] = 'nullable|numeric|min:0';
            $baseRules['color'] = 'nullable|string';
            $baseRules['size'] = 'nullable|string';
            $baseRules['si_unit'] = 'nullable|string';
            $baseRules['weight'] = 'nullable|numeric|min:0';
        }

        if ($this->type == ProductType::LIVESTOCK->value) {
            $baseRules['scientific_name'] = 'nullable|string';
            $baseRules['breed'] = 'nullable|string';
        }

        return $baseRules;
    }

    /**
     * Custom validation messages
     */
    public function customValidationMessages()
    {
        return [
            'name.required' => 'Product name is required',
            'brand.required' => 'Brand name is required',
            'sale_price.required' => 'Sale price is required',
            'available_stock.required' => 'Available stock is required',
        ];
    }

    /**
     * Chunk reading for performance
     */
    public function chunkSize(): int
    {
        return 1000;
    }

    /**
     * Batch inserts for performance
     */
    public function batchSize(): int
    {
        return 1000;
    }

    public function getMessages()
    {
        return $this->_messages;
    }
    public function getRowCount()
    {
        return Product::where('client_id', $this->clientId)
            ->where('user_id', $this->userId)
            ->count();
    }
}
