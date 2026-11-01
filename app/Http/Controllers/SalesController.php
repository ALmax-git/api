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
