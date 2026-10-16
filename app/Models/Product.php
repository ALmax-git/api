<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PhpOffice\PhpSpreadsheet\Calculation\Statistical\Distributions\F;

class Product extends Model
{

    // Define fillable fields
    protected $fillable = [
        'id',
        'client_id',
        'user_id',              // User who create the record
        'category_id',          //
        'livestock_id',         // If Is_Livestock livestock()
        'name',
        'brand',
        'sub_title',
        'description',
        'discount',
        'stock_price',
        'sale_price',
        'real_price',
        'available_stock',
        'color',
        'size',
        'si_unit',
        'weight',
        'status',
        'is_livestock',
        'sold',
        'type',
        'global_key',
        'min_stock_value',
        'has_reminder',

        'is_non_stock_item',
        'is_recipe',
        'is_index_able',
        'meta',
    ];

    protected $casts = [
        'is_livestock'          => 'boolean',
        'discount'              => 'float',
        'stock_price'           => 'float',
        'sale_price'            => 'float',
        'real_price'            => 'float',
        'available_stock'       => 'float',
        'status'                => ProductStatus::class,
        'type'                  => ProductType::class,
    ];

    /**
     * Relationship with the Client model (a product belongs to a client).
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relationship with the LiveStock model (a product may belong to a livestock if it's a livestock product).
     */
    public function livestock()
    {
        return $this->belongsTo(LiveStock::class);
    }

    /**
     * Relationship with the ProductCategory model (a product belongs to a Category).
     */

    /**
     * Relationship with the ProductCategory model (a product belongs to a Category).
     */
    public function category()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    /**
     * Relationship with the User model (a product belongs to a user).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship with the ProductVariant model (a product can have many variants).
     */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
    /**
     * Relationship with the ProductVariant model (a product can have many variants).
     */
    public function sale_units()
    {
        return $this->hasMany(SaleUnit::class, 'product_id');
    }

    /**
     * Relationship with the ProductLike model (a product can have many Like).
     */
    public function likes()
    {
        return $this->hasMany(ProductLike::class);
    }

    public function images()
    {
        return $this->belongsToMany(
            FileSystemItem::class,
            'product_media',
            'product_id',
            'file_system_item_id'
        );
    }
    // Addons where this product is the base
    public function addons()
    {
        return $this->hasMany(Addon::class, 'base_product_id');
    }

    // Addons where this product is used AS an addon
    public function usedAsAddon()
    {
        return $this->hasMany(Addon::class, 'addon_product_id');
    }
    // Get the total number of sold products
    public function get_total_sold(): int
    {
        $sold = $this->sold ?? 0;
        if (($this->type->value ?? '') == ProductType::ITEM->value) {
            $sold += (int) $this->variants()->sum('sold');
        }
        return $sold;
    }


    // Get the total number of available products
    public function get_total_available_stock()
    {
        return (int) $this->available_stock + $this->variants()->sum('available_stock');
    }
    // Get the total number of products
    public function getTotalProductsAttribute()
    {
        return $this->total_available + $this->total_sold;
    }
    // Get all the labels for this product
    // This includes labels for the product itself and for all its variants
    public function labels()
    {
        return $this->hasMany(Label::class)->where('status', '!=', 'archived');
    }

    //
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
    public function total_sales()
    {
        return $this->hasMany(Sale::class)->sum('total');
    }
}
