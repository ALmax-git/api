<?php

namespace App\Http\Controllers;

use App\Enums\ProductType;
use App\Models\Client;
use App\Models\FileSystemItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductMedia;
use App\Models\UserClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Image\Image;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public ?string  $search = '';
    public ?string  $category_id = '';
    public ?string  $varified_only = '';
    public ?string  $sort = '';

    public function save_products(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated.',
            ], 401);
        }
        if (!$request->input('category_id')) {
            return response()->json([
                'success' => false,
                'message' => 'error.',
            ], 401);
        }

        $productId = $request->input('id');
        $product = null;

        if (!empty($productId)) {
            $product = Product::find(get_id((string) $productId, 'product'));
        }
        $is_edit = false;

        if (!$product) {
            $product = new Product();
            $is_edit = true;
        }

        $product->client_id = $this->resolveRequestId($request->input('client_id'), 'client', $user->client_id);
        $product->user_id = $this->resolveRequestId($request->input('user_id'), 'user', $user->id);
        $product->category_id = $request->input('category_id');
        $product->name = $request->input('name');
        $product->brand = $request->input('brand');
        $product->sub_title = $request->input('sub_title', '');
        $product->description = $request->input('description', '');
        $product->discount = (float) ($request->input('discount', 0) ?? 0);
        $product->stock_price = (float) ($request->input('stock_price', 0) ?? 0);
        $product->sale_price = (float) ($request->input('sale_price', 0) ?? 0);
        $product->real_price = (float) ($request->input('real_price', 0) ?? 0);
        $product->available_stock = (float) ($request->input('available_stock', 0) ?? 0);
        $product->color = $request->input('color', '#ffffff') ?: '#ffffff';
        $product->size = $request->input('size', '');
        $product->si_unit = $request->input('si_unit', 'kg');
        $product->weight = (float) ($request->input('weight', 0) ?? 0);
        $product->status = $request->input('status', 'new');
        $product->is_livestock = (bool) $request->input('is_livestock', false);
        $product->sold = (int) ($request->input('sold', 0) ?? 0);
        $product->type =  ProductType::ITEM->value; //$request->input('type', ProductType::ITEM->value);
        $product->min_stock_value = (float) ($request->input('min_stock_value', 0) ?? 0);
        $product->has_reminder = (bool) $request->input('has_reminder', false);
        $product->is_non_stock_item = (bool) $request->input('is_non_stock_item', false);
        $product->is_recipe = (bool) $request->input('is_recipe', false);
        $product->is_index_able = (bool) $request->input('is_index_able', true);

        if ($is_edit) {
            $product->global_key = str_replace('-', '', str_replace(' ', '', str_replace(':', '', uniqid() . now())));
        }

        $product->save();

        return response()->json([
            'success' => true,
            'message' => $product->wasRecentlyCreated ? 'Product Created Successfully.' : 'Product Updated Successfully.',
            'product' => [
                'id' => make_uuid($product->id, 'product'),
                'uuid' => '#' . make_uuid($product->id, 'product'),
                'name' => $product->name,
                'brand' => $product->brand,
                'sub_title' => $product->sub_title,
                'description' => $product->description,
                'sale_price' => $product->sale_price,
                'available_stock' => $product->available_stock,
                'category_id' => $product->category_id,
            ],
        ], 200);
    }

    protected function resolveRequestId(mixed $value, string $model, mixed $default = null): mixed
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        if (is_string($value) && preg_match('/^[0-9a-fA-F]+$/', $value)) {
            $decoded = get_id($value, $model);

            if ($decoded > 0) {
                return $decoded;
            }
        }

        return $default ?? $value;
    }

    public function delete_product(Request $request, string $uuid)
    {

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated.',
            ], 401);
        }
        $product = Product::find(get_id($uuid, 'product'));
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }
        if ($product->client_id != $user->client_id) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated.',
            ], 492);
        }

        $product->delete();
        return response()->json([
            'success' => true,
            'message' => 'Product Deleted Successfull.',
        ], 200);
    }

    public function upload_product_media(Request $request, string $uuid)
    {

        $request->validate([
            'files' => 'required|array',
            'files.*' => 'required|image|max:6024',
            'variant_id' => 'nullable|string',
        ]);
        $variant_id = $request->input('variant_id', null);
        if (!$request->hasFile('files')) {
            return response()->json(['message' => 'No files uploaded.'], 422);
        }
        $user = $request->user();

        $client = $user->client;

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated.',
            ], 401);
        }
        $product = Product::find(get_id($uuid, 'product'));
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }
        if ($product->client_id != $user->client_id) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated.',
            ], 492);
        }

        try {

            // 🔹 Resolve folder once
            $folder_id = fileSystemItem::where('type', 'folder')
                ->where('owner_id', $client->owner->id)
                ->where('name', 'Business')
                ->value('id');

            if (!$folder_id) {
                throw new \Exception('Business folder not found for client owner ID: ' . $client->owner->id);
            }

            // 🔹 Resolve base URL once
            $host_data = _get_host();
            /**
             *   if (url('/') === 'http://127.0.0.1') {

                $base = url('/') . '8080';
            }

            if (!empty($host_data['domain'])) {
                $domain = trim($host_data['domain'], '/');
                $parsed = parse_url(str_contains($domain, '://') ? $domain : 'https://' . $domain);

                $scheme = strtolower($parsed['scheme'] ?? 'https');
                $host = $parsed['host'] ?? $domain;
                $port = $parsed['port'] ?? null;

                if ($port === null && !empty($_SERVER['SERVER_PORT'])) {
                    $serverPort = (int) $_SERVER['SERVER_PORT'];
                    if (($scheme === 'http' && $serverPort !== 80) || ($scheme === 'https' && $serverPort !== 443)) {
                        $port = $serverPort;
                    }
                }

                $netloc = $host;
                if ($port !== null && (($scheme === 'http' && $port != 80) || ($scheme === 'https' && $port != 443))) {
                    $netloc .= ':' . $port;
                }

                $base = (url('/') === 'http://127.0.0.1' ? 'http' : $scheme) . '://' . $netloc;
            }

             */
            $base = (!empty($host_data['domain']))
                ? (str_starts_with($host_data['domain'], 'http')
                    ? $host_data['domain']
                    : 'https://' . $host_data['domain'])
                : url('/');
            if (url('/') === 'http://127.0.0.1') {

                $base = url('/') . '8080';
            }

            foreach ($request->file('files') as $file) {

                DB::transaction(function () use ($file, $user, $product, $variant_id, $client, $folder_id, $base) {

                    // 🔒 Lock client row for safe quota update
                    $lockedClient = Client::where('id', $client->id)->lockForUpdate()->first();

                    $mime         = $file->getMimeType();
                    $originalName = $file->getClientOriginalName();
                    $originalSize = $file->getSize();

                    // Generate file name & folder
                    $filename = Str::random(16);
                    $extension = str_starts_with($mime, 'image/') ? 'jpg' : $file->getClientOriginalExtension();
                    $filename .= '.' . $extension;

                    $relativePath = 'files/' . $user->id . '/' . $filename;
                    $fullStoragePath = storage_path('app/public/' . $relativePath);

                    // Ensure folder exists
                    if (!file_exists(dirname($fullStoragePath))) {
                        mkdir(dirname($fullStoragePath), 0755, true);
                    }

                    // 🖼️ Compress image if applicable and large enough
                    if (str_starts_with($mime, 'image/') && $originalSize > 300 * 1024) {
                        $tempPath = $file->getRealPath();

                        Image::load($tempPath)
                            ->quality(75)
                            ->optimize()
                            ->format('jpg')
                            ->save($fullStoragePath);

                        $finalSize = filesize($fullStoragePath);
                    } else {
                        // fallback: just store directly
                        $stored = $file->storeAs('files/' . $user->id, $filename, 'public');
                        $finalSize = Storage::disk('public')->size($relativePath);
                    }
                    // ❗ REAL quota enforcement
                    if ($lockedClient->used_disk_size + $finalSize > $lockedClient->disk_size) {
                        Storage::disk('public')->delete($relativePath); // rollback stored file
                        alert_error("Storage limit exceeded. File '{$originalName}' was not uploaded.");
                        return;
                    }
                    // 🌐 Build URL
                    $full_path = rtrim($base, '/') . '/storage/' . ltrim($relativePath, '/');

                    // 💾 Save file record
                    $fileSystemItem = FileSystemItem::create([
                        'name'         => $this->getUniqueFileName($originalName, $folder_id, $client->owner->id),
                        'path'         => $folder_id,
                        'type'         => 'file',
                        'parent_id'    => $folder_id,
                        'owner_id'     => $user->id,
                        'mime_type'    => $mime,
                        'size'         => $finalSize,
                        'disk'         => 'public',
                        'storage_path' => $full_path,
                        'hash'         => hash_file('md5', $file->getRealPath()),
                    ]);

                    // 🔗 Attach to product
                    ProductMedia::create([
                        'product_id'          => $product->id,
                        'variant_id'           => $variant_id,
                        'file_system_item_id' => $fileSystemItem->id,
                    ]);

                    // 📊 Increment immediately
                    $lockedClient->increment('used_disk_size', $finalSize);
                });
            }
            if (($request->input('redirect_url'))) {
                return redirect($request->input('redirect_url'), 302);
            }


            return response()->json([
                'success' => true,
                'message' => 'Product uploaded Successfull.',
            ], 200);
        } catch (\Throwable $e) {

            report($e);

            if (($request->input('redirect_url'))) {
                return redirect($request->input('redirect_url'), 500);
            }
            return response()->json([
                'success' => false,
                'message' => 'Somethings whent wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Add this helper method inside your Explorer class:
    private function getUniqueFileName($originalName, $folder_id, $owner_id = null)
    {
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $name = $originalName;
        $counter = 1;

        while (FileSystemItem::where('parent_id', $folder_id)
            ->where('owner_id', $owner_id ?? auth()->id())
            ->where('type', 'file')
            ->where('name', $name)
            ->exists()
        ) {
            $name = $baseName . " ($counter)" . ($extension ? ".$extension" : '');
            $counter++;
        }

        return $name;
    }
    public function find_product(Request $request, string $uuid)
    {

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated.',
            ], 401);
        }
        $product = Product::find(get_id($uuid, 'product'));
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.' . $uuid

            ], 404);
        }
        if ($product->client_id != $user->client_id) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated.',
            ], 492);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product Found Successfull.',
            'product' => [
                'id'                        => make_uuid($product->id, 'product'),
                'uuid'                      => '#' . make_uuid($product->id, 'product'),
                'name'                      => $product->name,
                'brand'                     => $product->brand,
                'sub_title'                 => $product->sub_title,
                'sold'                      => $product->sold,
                'image_url'                 => $product->images->first()?->storage_path,
                'emoji'                     => $product->emoji ?: '📦',
                'color'                     => $product->color ?: '#6B7280',
                'sale_price'                => $product->sale_price,
                'available_stock'           => $product->is_non_stock_item ? 9999 : $product->available_stock,
                'category_id'               => $product->category ? $product->category?->id : null,
                'category'                  => $product->category?->title,
                'discount'                  => $product->discount,
                'margin'                    => $product->margin,

                'labels' => $product->labels
                    ->map(
                        fn($label) => [
                            'id'            => $label->id,
                            'status'        => $label->status,
                            'uuid'          => '#' . make_uuid($label->id, 'label'),
                        ],
                    )
                    ->values(),
                'sale_units' => $product->sale_units
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

        ], 200);
    }
    public function get_my_products(Request $request, string $uuid)
    {

        $client = Client::where('id', get_id($uuid, 'client'))->first();
        $user = $request->user();

        if (!$user || !$client) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated.',
            ], 401);
        }
        if (
            !(UserClient::where('user_id', $user->id)->where('client_id', $client->id)->where('is_staff', true)->exists())
        ) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated by Client.',
            ], 401);
        }
        $this->search = $request->search ?? '';
        $products = Product::query()

            ->where(function ($query) {
                $query->where('name',           'like', "%{$this->search}%")
                    ->orWhere('brand',          'like', "%{$this->search}%")
                    ->orWhere('sub_title',      'like', "%{$this->search}%")
                    ->orWhere('description',    'like', "%{$this->search}%");
            })
            ->where('client_id', $user->client_id)

            ->when($this->category_id ? read($this->category_id) : $this->category_id,      fn($query) => $query->where('category_id', $this->category_id ? read($this->category_id) : $this->category_id))
            ->when($this->sort === 'latest',    fn($query) => $query->latest())
            ->when($this->sort === 'oldest',    fn($query) => $query->oldest())
            ->when($this->sort === 'low',       fn($query) => $query->orderBy('sale_price', 'asc'))
            ->when($this->sort === 'high',      fn($query) => $query->orderBy('sale_price', 'desc'))
            ->get();

        $categories = ProductCategory::where('client_id', $user->client_id)
            ->when($user->client->use_generic_product_categories || $user->client_id == 1, function ($query) {
                $query->orWhere('client_id', null);
            })->where('status', 'active')->orderBy('title')->get();
        // $categories = $categories->map(function ($category) {

        return response()->json(
            [
                'success' => true,
                'products' => $products->map(
                    fn($p) => [
                        'id'                        => make_uuid($p->id, 'product'),
                        'uuid'                      => '#' . make_uuid($p->id, 'product'),
                        'name'                      => $p->name,
                        'brand'                     => $p->brand,
                        'sub_title'                 => $p->sub_title,
                        'sold'                      => $p->sold,
                        'image_url'                 => $p->images->first()?->storage_path,
                        'emoji'                     => $p->emoji ?: '📦',
                        'color'                     => $p->color ?: '#6B7280',
                        'sale_price'                => $p->sale_price,
                        'available_stock'           => $p->is_non_stock_item ? 9999 : $p->available_stock,
                        'category'                  => $p->category?->title,
                        'category_id'               => $p->category ? $p->category?->id : null,
                        'discount'                  => $p->discount,
                        'margin'                    => $p->margin,

                        'labels' => $p->labels
                            ->map(
                                fn($label) => [
                                    'id'            => $label->id,
                                    'status'        => $label->status,
                                    'uuid'          => '#' . make_uuid($label->id, 'label'),
                                ],
                            )
                            ->values(),
                        'sale_units' => $p->sale_units
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
                ),
                'categories' => $categories,
            ]
        );
    }
    public function get_all_products()
    {

        $this->search = $request->search ?? '';
        $products = Product::query()

            ->whereHas('images')
            ->where(function ($query) {
                $query->where('name',           'like', "%{$this->search}%")
                    ->orWhere('brand',          'like', "%{$this->search}%")
                    ->orWhere('sub_title',      'like', "%{$this->search}%")
                    ->orWhere('description',    'like', "%{$this->search}%");
            })
            ->when(
                $this->sort === 'recommended',
                fn($q) =>
                $q->whereHas('likes', fn($like) => $like->where('user_id', auth()?->user()?->id))
            )

            // ->when(_get_host()['save_as_gateway'] == false, fn($query) => $query->where('client_id', get_id(_get_host()['uuid'], 'client')))
            // ->when(_get_host()['save_as_gateway'] == true, fn($query) => $query->whereHas('client',    fn($q) => $q->where('use_almax_gateways', true)->orWhere('client_id', get_id(_get_host()['uuid'], 'client'))))
            ->when($this->category_id ? read($this->category_id) : $this->category_id,      fn($query) => $query->where('category_id', $this->category_id ? read($this->category_id) : $this->category_id))
            ->when($this->varified_only,        fn($query) => $query->whereHas('client',    fn($q) => $q->where('is_verified', true)))
            ->when($this->sort === 'latest',    fn($query) => $query->latest())
            ->when($this->sort === 'oldest',    fn($query) => $query->oldest())
            ->when($this->sort === 'low',       fn($query) => $query->orderBy('sale_price', 'asc'))
            ->when($this->sort === 'high',      fn($query) => $query->orderBy('sale_price', 'desc'))
            ->get();

        $categories = ProductCategory::where('status', 'active')->orderBy('title')->get();

        // $categories = $categories->map(function ($category) {
        //     if (($category->id)) {
        //         $category->id = make_uuid($category->id, 'product_category');
        //     }
        //     return $category;
        // });
        // foreach ($products as  $product) {
        //     return $product;
        //     $product['id'] = make_uuid($product['id'], 'product');
        // }
        return response()->json(
            [
                'success' => true,
                'products' => $products->map(
                    fn($p) => [
                        'id'                        => make_uuid($p->id, 'product'),
                        'uuid'                      => '#' . make_uuid($p->id, 'product'),
                        'name'                      => $p->name,
                        'brand'                     => $p->brand,
                        'sub_title'                 => $p->sub_title,
                        'sold'                      => $p->sold,
                        'image_url'                 => $p->images->first()?->storage_path,
                        'emoji'                     => $p->emoji ?: '📦',
                        'color'                     => $p->color ?: '#6B7280',
                        'sale_price'                => $p->sale_price,
                        'available_stock'           => $p->is_non_stock_item ? 9999 : $p->available_stock,
                        'category'                  => $p->category?->title,
                        'category_id'               => $p->category ? $p->category?->id : null,
                        'discount'                  => $p->discount,
                        'margin'                    => $p->margin,

                        // IMPORTANT
                        'labels' => $p->labels
                            ->map(
                                fn($label) => [
                                    'id'            => $label->id,
                                    'status'        => $label->status,
                                    'uuid'          => '#' . make_uuid($label->id, 'label'),
                                ],
                            )
                            ->values(),
                        'sale_units' => $p->sale_units
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
                ),
                'categories' => $categories,
            ]
        );
    }
    public function label_printer(Request $request, $list, $theme, $grid_size, $page_size)
    {
        // auth required
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthorized');
        }

        // validate inputs manually (since route params)
        $allowedThemes = ['dark', 'light', 'mica', 'nature', 'coffee', 'aquarium', 'orange', 'blue'];
        $allowedGrids = ['2x8', '4x8', '5x12'];
        $allowedPages = ['A4'];

        if (!in_array($theme, $allowedThemes)) {
            abort(400, 'Invalid theme');
        }

        if (!in_array($grid_size, $allowedGrids)) {
            abort(400, 'Invalid grid size');
        }

        if (!in_array($page_size, $allowedPages)) {
            abort(400, 'Invalid page size');
        }

        // parse list (uuid|all|comma-separated ids)
        $productIds = $this->parseLabelList($list);

        if (empty($productIds)) {
            abort(400, 'Empty product list');
        }

        // load products with labels
        $products = \App\Models\Product::with(['labels'])
            ->whereIn('id', $productIds)
            ->get();

        // flatten labels for printing engine
        $labels = $products->flatMap(function ($product) {
            return $product->labels->map(function ($label) use ($product) {

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'qr_key' =>
                    'https://almaxcloud.com/xp?' .
                        'p=' . make_uuid($product->id, 'product') . ($product->client->use_strict_sales_via_qr_code ? ('&l=' . ($label ? make_uuid($label->id, 'label') : '')) : ''),
                    'serial_number' => _app('call') . ':' . $product->client->telephone,
                    'status' => $label->status,
                    'logo' => $product->client->logo ?? null,
                    'client' => $product->client->name ?? 'Core-X',

                ];
            });
        });

        return view('labelprinter', [

            'theme' => $theme,
            'grid_size' => $grid_size,
            'page_size' => $page_size,
            'labels' => $labels,
        ]);
    }
    private function parseLabelList($list)
    {
        if ($list === 'all') {
            return \App\Models\Product::pluck('id')->toArray();
        } else {
            return \App\Models\Product::where('id', get_id($list))->pluck('id')->toArray();
        }
    }
}
