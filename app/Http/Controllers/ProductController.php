<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public ?string  $search = '';
    public ?string  $category_id = '';
    public ?string  $varified_only = '';
    public ?string  $sort = '';
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

        $categories = $categories->map(function ($category) {
            if (($category->id)) {
                $category->id = make_uuid($category->id, 'product_category');
            }
            return $category;
        });
        // foreach ($products as  $product) {
        //     return $product;
        //     $product['id'] = make_uuid($product['id'], 'product');
        // }
        return response()->json(
            [
                'success' => true,
                'products' => $products->map(
                    fn($p) => [
                        'id'                        => $p->id,
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
