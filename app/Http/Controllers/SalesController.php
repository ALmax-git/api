<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Label;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function Illuminate\Log\log;

class SalesController extends Controller
{
    public function load_procurement(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated.',
            ], 401);
        }

        $client = $user->client;

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client profile not found.',
            ], 404);
        }

        $stocks = \App\Models\Stock::query()
            ->where('client_id', $client->id)
            ->with([
                'entries',
                'entries.product',
                'entries.product.category',
                'supplier',
                'creator',
                'client',
            ])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stocks->map(function ($stock) {
                return [
                    'id' => make_uuid($stock->id, 'stock'),
                    'uuid' => '#' . make_uuid($stock->id, 'stock'),
                    'label' => $stock->label,
                    'title' => $stock->title,
                    'invoice_number' => $stock->invoice_number,
                    'note' => $stock->note,
                    'internal_note' => $stock->internal_note,
                    'other_fees' => (float) ($stock->other_fees ?? 0),
                    'total_amount' => (float) ($stock->total_amount ?? 0),
                    'paid_amount' => (float) ($stock->paid_amount ?? 0),
                    'order_date' => $stock->order_date,
                    'expected_date' => $stock->expected_date,
                    'products_count' => (int) ($stock->products_count ?? ($stock->entries?->count() ?? 0)),
                    'status' => $stock->status,
                    'client_id' => $stock->client_id ? make_uuid($stock->client_id, 'client') : null,
                    'supplier_id' => $stock->supplier_id ? make_uuid($stock->supplier_id, 'supplier') : null,
                    'created_by_id' => $stock->created_by_id ? make_uuid($stock->created_by_id, 'user') : null,
                    'created_at' => $stock->created_at,
                    'updated_at' => $stock->updated_at,
                    'client' => $stock->client ? [
                        'id' => make_uuid($stock->client->id, 'client'),
                        'uuid' => '#' . make_uuid($stock->client->id, 'client'),
                        'name' => $stock->client->name,
                        'email' => $stock->client->email,
                        'phone_number' => $stock->client->phone_number,
                    ] : null,
                    'supplier' => $stock->supplier ? [
                        'id' => make_uuid($stock->supplier->id, 'supplier'),
                        'uuid' => '#' . make_uuid($stock->supplier->id, 'supplier'),
                        'name' => $stock->supplier->name,
                        'email' => $stock->supplier->email,
                        'phone_number' => $stock->supplier->phone_number,
                    ] : null,
                    'created_by' => $stock->creator ? [
                        'id' => make_uuid($stock->creator->id, 'user'),
                        'uuid' => '#' . make_uuid($stock->creator->id, 'user'),
                        'name' => $stock->creator->name,
                        'email' => $stock->creator->email,
                        'username' => $stock->creator->username,
                    ] : null,
                    'entries' => $stock->entries?->map(function ($entry) {
                        return [
                            'id' => make_uuid($entry->id, 'stock_entry'),
                            'uuid' => '#' . make_uuid($entry->id, 'stock_entry'),
                            'stock_id' => $entry->stock_id ? make_uuid($entry->stock_id, 'stock') : null,
                            'product_id' => $entry->product_id ? make_uuid($entry->product_id, 'product') : null,
                            'variant_id' => $entry->variant_id ? make_uuid($entry->variant_id, 'variant') : null,
                            'client_id' => $entry->client_id ? make_uuid($entry->client_id, 'client') : null,
                            'created_by_id' => $entry->created_by_id ? make_uuid($entry->created_by_id, 'user') : null,
                            'quantity' => (float) ($entry->quantity ?? 0),
                            'quantity_recieved' => (float) ($entry->quantity_recieved ?? 0),
                            'quantity_damage' => (float) ($entry->quantity_damage ?? 0),
                            'unit_cost_price' => (float) ($entry->unit_cost_price ?? 0),
                            'sale_price' => (float) ($entry->sale_price ?? 0),
                            'status' => $entry->status,
                            'batch_number' => $entry->batch_number,
                            'expire_date' => $entry->expire_date,
                            'production_date' => $entry->production_date,
                            'note' => $entry->note,
                            'product' => $entry->product ? [
                                'id' => make_uuid($entry->product->id, 'product'),
                                'uuid' => '#' . make_uuid($entry->product->id, 'product'),
                                'name' => $entry->product->name,
                                'brand' => $entry->product->brand,
                                'sale_price' => $entry->product->sale_price,
                                'available_stock' => $entry->product->available_stock,
                                'category' => $entry->product->category ? $entry->product->category->title : null,
                            ] : null,
                            'created_at' => $entry->created_at,
                            'updated_at' => $entry->updated_at,
                        ];
                    })->values(),
                ];
            })->values(),
        ]);
    }

    public function new_procurement(Request $request)
    {

        try {
            $data = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'client_id' => ['nullable', 'string'],
                'supplier_id' => ['nullable', 'string'],
                'created_by_id' => ['nullable', 'string'],
                'invoice_number' => ['nullable', 'string', 'max:255'],
                'note' => ['nullable', 'string'],
                'internal_note' => ['nullable', 'string'],
                'other_fees' => ['nullable', 'numeric', 'min:0'],
                'total_amount' => ['required', 'numeric', 'min:0'],
                'paid_amount' => ['nullable', 'numeric', 'min:0'],
                'order_date' => ['nullable', 'date'],
                'expected_date' => ['nullable', 'date'],
                'products_count' => ['nullable', 'integer', 'min:0'],
                'entries' => ['required', 'array', 'min:1'],
                'entries.*.product_id' => ['required', 'string'],
                'entries.*.quantity' => ['required', 'numeric', 'min:0'],
                'entries.*.unit_cost_price' => ['nullable', 'numeric', 'min:0'],
                'entries.*.sale_price' => ['nullable', 'numeric', 'min:0'],
                'entries.*.status' => ['nullable', 'string', 'max:255'],
                'entries.*.batch_number' => ['nullable', 'string', 'max:255'],
                'entries.*.expire_date' => ['nullable', 'date'],
                'entries.*.production_date' => ['nullable', 'date'],
                'entries.*.note' => ['nullable', 'string'],
            ]);

            $user = $request->user();
            $createdById = $data['created_by_id'] ?? ($user?->id ?? null);
            $clientId = $data['client_id'] ?? ($user?->client_id ?? null);
            $supplierId = $data['supplier_id'] ?? null;

            DB::beginTransaction();

            $procurementPayload = [
                'title' => $data['title'],
                'label' => $data['title'],
                'created_by_id' => $user->id,
                'client_id' => $user->client->id,
                'supplier_id' => $supplierId ? get_id($supplierId, 'supplier') : null,
                'invoice_number' => $data['invoice_number'] ?? null,
                'note' => $data['note'] ?? null,
                'internal_note' => $data['internal_note'] ?? null,
                'other_fees' => $data['other_fees'] ?? 0,
                'total_amount' => $data['total_amount'],
                'paid_amount' => $data['paid_amount'] ?? 0,
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'expected_date' => $data['expected_date'] ?? null,
                'products_count' => $data['products_count'] ?? count($data['entries']),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $procurementId = null;

            if (class_exists('\App\\Models\\Stock')) {
                $procurement = new \App\Models\Stock();
                foreach ($procurementPayload as $key => $value) {
                    $procurement->{$key} = $value;
                }
                $procurement->save();
                $procurementId = $procurement->id;
            } else {
                $procurementId = DB::table('stocks')->insertGetId($procurementPayload);
            }

            foreach ($data['entries'] as $entry) {
                $entryPayload = [
                    'stock_id' => $procurementId,
                    'product_id' => $entry['product_id'] ? get_id($entry['product_id'], 'product') : null,
                    'variant_id' => !empty($entry['variant_id']) ? get_id($entry['variant_id'], 'variant') : null,
                    // 'stock_id' => !empty($entry['stock_id']) ? get_id($entry['stock_id'], 'stock') : null,
                    'quantity' => $entry['quantity'] ?? 0,
                    'quantity_recieved' => $entry['quantity_recieved'] ?? 0,
                    'quantity_damage' => $entry['quantity_damage'] ?? 0,
                    'unit_cost_price' => $entry['unit_cost_price'] ?? 0,
                    'sale_price' => $entry['sale_price'] ?? 0,
                    'status' => $entry['status'] ?? 'pending',
                    'note' => $entry['note'] ?? null,
                    'batch_number' => $entry['batch_number'] ?? null,
                    'expire_date' => $entry['expire_date'] ?? null,
                    'production_date' => $entry['production_date'] ?? null,
                    'client_id' => $user->client->id,
                    'created_by_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (class_exists('\App\\Models\\StockEntry')) {
                    $item = new \App\Models\StockEntry();
                    foreach ($entryPayload as $key => $value) {
                        $item->{$key} = $value;
                    }
                    $item->save();

                    if (!empty($entryPayload['product_id'])) {
                        $product = \App\Models\Product::find($entryPayload['product_id']);
                        if ($product) {
                            $receivedQuantity = (float) ($entry['quantity'] ?? 0);

                            if (isset($product->available_quantity) || array_key_exists('available_quantity', $product->getAttributes())) {
                                $product->available_quantity = (float) ($product->available_quantity ?? 0) + $receivedQuantity;
                            }

                            if (isset($product->available_stock) || array_key_exists('available_stock', $product->getAttributes())) {
                                $product->available_stock = (float) ($product->available_stock ?? 0) + $receivedQuantity;
                            }

                            if (isset($product->quantity) || array_key_exists('quantity', $product->getAttributes())) {
                                $product->quantity = (float) ($product->quantity ?? 0) + $receivedQuantity;
                            }

                            $product->save();
                        }
                    }
                } else {
                    DB::table('stock_entries')->insert($entryPayload);

                    if (!empty($entryPayload['product_id'])) {
                        $product = \App\Models\Product::find($entryPayload['product_id']);
                        if ($product) {
                            $receivedQuantity = (float) ($entry['quantity'] ?? 0);

                            if (isset($product->available_quantity) || array_key_exists('available_quantity', $product->getAttributes())) {
                                $product->available_quantity = (float) ($product->available_quantity ?? 0) + $receivedQuantity;
                            }

                            if (isset($product->available_stock) || array_key_exists('available_stock', $product->getAttributes())) {
                                $product->available_stock = (float) ($product->available_stock ?? 0) + $receivedQuantity;
                            }

                            if (isset($product->quantity) || array_key_exists('quantity', $product->getAttributes())) {
                                $product->quantity = (float) ($product->quantity ?? 0) + $receivedQuantity;
                            }

                            $product->save();
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Procurement saved successfully.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
            ], 422);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('procure_error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save procurement.',
            ], 500);
        }
    }
    public function store_sales(Request $request, string $uuid)
    {

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated.',
            ], 401);
        }
        $client = Client::find(get_id($uuid, 'client'));
        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Store not found.' . $uuid

            ], 404);
        }
        if ($client->id != $user->client_id) {
            return response()->json([
                'success' => false,
                'message' => 'User not autheorized.',
            ], 492);
        }


        $sales = $client->sales()->with(['product', 'product.category', 'user', 'client'])->get();

        return response()->json(
            [
                'success' => true,
                'sales' => $sales->map(
                    fn($sale) => [
                        'quantity'                  => $sale->quantity,
                        'price'                     => $sale->price,
                        'total'                     => $sale->total,
                        'product_global_key'        => $sale->product_global_key,
                        'discount'                  => $sale->discount,
                        'global_key'                => $sale->global_key,
                        'created_at'                => $sale->created_at,
                        'product' => [
                            'id'                        => make_uuid($sale->product?->id, 'product'),
                            'uuid'                      => '#' . make_uuid($sale->product?->id, 'product'),
                            'name'                      => $sale->product?->name,
                            'brand'                     => $sale->product?->brand,
                            'sub_title'                 => $sale->product?->sub_title,
                            'sold'                      => $sale->product?->sold,
                            'image_url'                 => $sale->product?->images->first()?->storage_path ?? "default.png",
                            'emoji'                     => $sale->product?->emoji ?: '📦',
                            'color'                     => $sale->product?->color ?: '#6B7280',
                            'sale_price'                => $sale->product?->sale_price,
                            'available_stock'           => $sale->product?->is_non_stock_item ? 9999 : $sale->product?->available_stock,
                            'category_id'               => $sale->product?->category ? $sale->product?->category?->id : null,
                            'category'                  => $sale->product?->category?->title,
                            'discount'                  => $sale->product?->discount,
                            'margin'                    => $sale->product?->margin,
                            'labels' => $sale->product?->labels
                                ->map(
                                    fn($label) => [
                                        'id'            => $label->id,
                                        'status'        => $label->status,
                                        'uuid'          => '#' . make_uuid($label->id, 'label'),
                                    ],
                                )
                                ->values(),
                            'sale_units' => $sale->product?->sale_units
                                ->map(
                                    fn($sale_unit) => [
                                        'quantity'      => $sale_unit->quantity,
                                        'label'         => $sale_unit->label,
                                        'price'         => $sale_unit->price,
                                        'code'          => $sale_unit->code,
                                    ]
                                )
                                ->values()
                        ],
                        'user' => [
                            'id'            => make_uuid($sale->user?->id),
                            'uuid'          => make_uuid($sale->user?->id, 'user'),
                            'name'          => $sale->user?->name,
                            'username'      => $sale->user?->username,
                            'email'         => $sale->user?->email,
                            'first_name'    => $sale->user?->first_name,
                            'surname'       => $sale->user?->surname,
                            'last_name'     => $sale->user?->last_name,
                            'phone_number'  => $sale->user?->phone_number,
                            'country'       => $sale->user?->country?->name,
                            'state'         => $sale->user?->state?->name,
                            'city'          => $sale->user?->city?->name,
                            'bio'           => $sale->user?->bio,
                            'profile_photo_url' => $sale->user?->profile_photo_url ?? 'default.png',
                            'language'      => $sale->user?->language,
                            'gender'        => $sale->user?->gender,
                            'status'        => $sale->user?->status,
                            'trust_score'   => $sale->user?->trust_score ?? 0,
                        ]
                    ]
                )
            ]
        );
    }
    public function sale_new(Request $request)
    {
        $data = $request->validate([
            'user_id'        => 'required',
            'payment_method' => 'required|string',
            'is_refund'      => 'boolean',
            'products'       => 'required|array|min:1',
            'products.*.id'        => 'required|integer|exists:products,id',
            'products.*.quantity'  => 'required|integer|min:1',
            'products.*.price'     => 'required|numeric|min:0',
            'products.*.discount'  => 'nullable|numeric|min:0',
            'subtotal'       => 'required|numeric|min:0',
            'discount_total' => 'required|numeric|min:0',
            'total'          => 'required|numeric|min:0',
        ]);

        $user = User::find(get_id($data['user_id'], 'user'));
        if (!$user) {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 404);
            }
        }
        DB::beginTransaction();
        if ($data['is_refund'] === false) {
            $pos_cart = new \App\Models\PosCart([
                'client_id' => $user->client_id,
                'user_id' => $user->id,
                'items' => $data['products'],
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
            ]);
            try {
                foreach ($data['products'] as $item) {
                    $product = Product::lockForUpdate()->find($item['id']);

                    if (!$product->is_non_stock_item) {
                        if ($product->available_stock < $item['quantity']) {
                            throw new \Exception($product->name . ' is out of stock');
                        }
                    }

                    $quantity = (int) $item['quantity'];
                    $price    = (float) $item['price'];
                    $discount = (float) ($item['discount'] ?? 0);
                    $total    = ($quantity * $price) - $discount;

                    $product->sold += $quantity;
                    if (!$product->is_non_stock_item) {
                        $product->available_stock -= $quantity;
                    }
                    $product->save();

                    Sale::create([
                        'client_id'          =>   $user->client_id,
                        'user_id'            =>  $user->id,
                        'product_id'         => $product->id,
                        'quantity'           => $quantity,
                        'price'              => $price,
                        'discount'           => $discount,
                        'total'              => $total,
                        'product_global_key' => $product->global_key,
                        'global_key'         => str_replace(['-', ' ', ':'], '', uniqid() . now()),
                    ]);
                    $pos_cart->save();
                }
                if ($request->labels) {

                    foreach ($request->labels as $l) {

                        $id = str_replace('#', '', $l['code'] ?? $l->code);

                        // optional helper if you use it
                        $labelId = get_id($id, 'label');

                        $label = Label::find($labelId);

                        if (!$label) {
                            continue;
                        }

                        $label->status = 'out_of_stock';
                        $label->save();
                    }
                }

                DB::commit();

                return response()->json(
                    [
                        'success'               => true,
                        'message'               => 'Sale recorded successfully',
                        'data'                  => [
                            'uuid'              => make_uuid($pos_cart->id, 'pos_cart'),
                        ],
                    ],
                    201
                );
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Sale failed', ['error' => $e->getMessage()]);

                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
        }
    }

    public function sales(Request $request,  $token = null)
    {
        try {
            if ($token && !$token == 'auth') {
                $token  = read($token);
            } else {
                if (auth()->user()) {
                    $token = auth()->user()->id;
                } else {

                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid or tampered auth headers.' . $token,
                        'error' => 'invalide Auth',
                    ], 401)->header('Cache-Control', 'no-store');
                }
            }
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or tampered auth headers.' . $token,
                'error' => $e,
            ], 401)->header('Cache-Control', 'no-store');
        }

        if (!is_numeric($token)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid token format.',
            ], 401)->header('Cache-Control', 'no-store');
        }

        $user = User::find($token);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.',
            ], 404)->header('Cache-Control', 'no-store');
        }
        if (!auth()->user()) {
            if (!$user->last_seen_at || $user->last_seen_at->lt(now()->subMinute())) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Token expired.',
                ], 401)->header('Cache-Control', 'no-store');
            }
        }

        $client = $user->client;

        if (!$client) {
            return response()->json([
                'status' => 'error',
                'message' => 'Client not found for user.',
            ], 404)->header('Cache-Control', 'no-store');
        }

        $sales = $client->sales()->with(['product', 'product.category', 'user', 'client'])->get();

        return response()->json([
            'status' => 'ok',
            'sales' => $sales,
            'meta' => [
                'total_sales' => $sales->sum('total'),
                'total_discount' => $sales->sum('discount'),
            ],
        ], 200)->header('Cache-Control', 'no-store');
    }

    public function suppliers(Request $request)
    {
        $user = auth()->user();

        $suppliers = Client::with('owner')
            // ->where('is_supplier', true)
            ->when($user?->client_id, fn($q) => $q->whereKeyNot($user->client_id))
            ->get();

        $mySuppliers = $user?->client
            ? $user->client->suppliers
            : collect();

        $format = fn(Client $client) => [
            'name'          => $client->name,
            'id'            => make_uuid($client->id, 'client'),
            'phone_number'  => $client->phone_number ?? $client->owner?->phone_number,
            'telephone'     => $client->telephone,
            'rc_number'     => $client->rc_number ?? '000000',
            'bn_number'     => $client->bn_number ?? '000000',
            'tax_id'        => $client->tax_id,
            'info'          => $client->description ?? $client->overview,
            'is_verified'   => true,
            'is_registered' => true,
        ];

        return response()->json([
            'status'       => 'success',
            'my_suppliers' => $mySuppliers->map($format)->values(),
            'suppliers'    => $suppliers->map($format)->values(),
        ]);
    }
}
