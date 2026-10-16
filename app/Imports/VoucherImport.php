<?php

namespace App\Imports;

use App\Models\Label;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithValidation;

class VoucherImport implements ToModel
{
    protected $errors = [];

    public $product_id;
    public $price;
    public $variant_id;
    public $stock_entry;
    public $product;

    public function __construct(
        $product_id,
        $variant_id = null,
        $title = "",
        $price = 0
    ) {
        $this->product_id = ($product_id);
        $this->variant_id = $variant_id;
        $this->price = $price;

        $this->product = Product::findOrFail($this->product_id);
        $this->product->sale_price = $this->price;
        $this->product->save();
        /*
        |--------------------------------------------------------------------------
        | Stock
        |--------------------------------------------------------------------------
        */

        $stock = new Stock();

        $stock->title = $title;
        $stock->client_id = auth()->user()->client_id;
        $stock->created_by_id = auth()->id();

        $stock->label = $title;
        $stock->note = $title;

        $stock->invoice_number = generate_tx_ref();

        $stock->supplier_id = null;
        $stock->other_fees = 0;

        $stock->total_amount = 0;
        $stock->paid_amount = 0;

        $stock->products_count = 0;

        $stock->status = 'Complete';
        $stock->payment_status = 'Paid';

        $stock->internal_note = 'Voucher: ' . now()->format('M-D-Y');

        $stock->order_date = now();
        $stock->expected_date = now();

        $stock->save();

        /*
        |--------------------------------------------------------------------------
        | Stock Entry
        |--------------------------------------------------------------------------
        */

        $this->stock_entry = new StockEntry();

        $this->stock_entry->last_stock_entry_id = null;

        $this->stock_entry->client_id = auth()->user()->client_id;

        $this->stock_entry->created_by_id = auth()->id();
        $this->stock_entry->confirmed_by_id = auth()->id();

        $this->stock_entry->stock_id = $stock->id;

        $this->stock_entry->product_id = $this->product_id;
        $this->stock_entry->variant_id = $this->variant_id;

        $this->stock_entry->quantity = 0;

        $this->stock_entry->available_stock_before =
            $this->product->available_stock;

        $this->stock_entry->available_stock_after =
            $this->product->available_stock;

        // $this->stock_entry->status = 'new';

        $this->stock_entry->note =
            'Voucher: ' . now()->format('M-D-Y');

        // $this->stock_entry->confirmed_at = now();

        // $this->stock_entry->expire_date = null;

        // $this->stock_entry->production_date = now();

        // $this->stock_entry->best_before = null;

        $this->stock_entry->batch_number =
            $stock->invoice_number;

        $this->stock_entry->unit_cost_price = 50;

        // Ensure sale_price is a valid numeric value (avoid inserting empty string into decimal column)
        $this->stock_entry->sale_price = is_numeric($this->price) ? (float) $this->price : 0.0;

        $this->stock_entry->quantity_recieved = 0;
        $this->stock_entry->quantity_damage = 0;

        $this->stock_entry->save();
    }

    /**
     * Import one voucher.
     */
    public function model(array $row): ?Model
    {
        /*
        |--------------------------------------------------------------------------
        | Update stock entry
        |--------------------------------------------------------------------------
        */

        $this->stock_entry->quantity += 1;

        $this->stock_entry->quantity_recieved += 1;

        $this->stock_entry->available_stock_after += 1;

        $this->stock_entry->save();
        $this->product->available_stock += 1;
        $this->product->save();

        /*
        |--------------------------------------------------------------------------
        | Create Voucher
        |--------------------------------------------------------------------------
        */
        // dd($row[0], $row[1]);
        return new Label([
            'product_id' => $this->product_id,

            'variant_id' => $this->variant_id,

            'stock_entry_id' => $this->stock_entry->id,

            // PIN / password
            'qr_key' => $row[1],

            // Username / serial
            'serial_number' =>
            $row[0],

            'status' => 'new',
        ]);
    }

    /**
     * Log import errors.
     *
     * @param string $message
     * @param array $row
     */
    private function logError($message, $row)
    {
        $errorDetails = [
            'error' => $message,
            'row_data' => $row,
        ];

        // Log the error
        Log::error('staff Import Error', $errorDetails);

        // Store error message for later retrieval (optional)
        $this->errors[] = $errorDetails;
    }

    /**
     * Get import errors (useful for debugging).
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
