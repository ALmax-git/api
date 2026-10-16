<?php

namespace App\Models;

use App\Enums\ClientStatus;
use App\Enums\ProductType;
use App\Enums\WalletType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

class Client extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'logo',
        'email',
        'tagline',
        'telephone',
        'country_id',
        'state_id',
        'city_id',
        'vision',
        'mission',
        'overview',
        'description',
        'is_registered',
        'is_verified',
        'status',
        'user_id',
        'category_id',
        'domain_id',
        'use_almax_gateways',
        'save_as_gateway',
        'accept_e_payments',
        'show_pricing',
        'allow_pricing_override',
        'amigo_can_access',
        'default_product_type',
        'always_use_default_product_type',
        'disk_size',
        'used_disk_size',
        'ANDROID_SHA256',
        'theme',
        'layout',
        'short_name',
        'use_generic_product_categories',
        'use_strict_sales_via_qr_code',
        'use_fifo',
        'use_default_pricing',
        'registered_business_name',
        'trading_name',
        'phone_number',
        'rc_number',
        'bn_number',
        'tax_id',
        'legal_entity_type',
        'industry_sector',
        'is_supplier',
        'is_pfa',
        'meta',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_verified'                               => 'boolean',
            'is_registered'                             => 'boolean',
            'use_almax_gateways'                        => 'boolean',
            'save_as_gateway'                           => 'boolean',
            'accept_e_payments'                         => 'boolean',
            'show_pricing'                              => 'boolean',
            'allow_pricing_override'                    => 'boolean',
            'amigo_can_access'                          => 'boolean',
            'status'                                    => ClientStatus::class,
            'default_product_type'                      => ProductType::class,
            'always_use_default_product_type'           => 'boolean',
            'use_generic_product_categories'            => 'boolean',
            'use_strict_sales_via_qr_code'              => 'boolean',
            'use_fifo'                                  => 'boolean',
            'use_default_pricing'                       => 'boolean',
            'meta'                                      => 'array',
        ];
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(
            Client::class,
            'client_client_relations',
            'client_id',
            'related_client_id'
        )
            ->using(ClientClientRelation::class)
            ->withPivot([
                'id',
                'relationships',
                'sign_by_id',
                'acknowledged_by_id',
                'related_phone_number',
                'related_telephone',
            ]);
        // ->whereJsonContains('client_client_relations.relationships', 'supply')
        // ->where('clients.is_supplier', true);
    }
    public function consumers(): BelongsToMany
    {
        return $this->belongsToMany(
            Client::class,
            'client_client_relations',
            'related_client_id',
            'client_id'
        )
            ->using(ClientClientRelation::class)
            ->whereJsonContains('client_client_relations.relationships', 'supply');
    }
    public function product_categories(): HasMany
    {
        return $this->hasMany(ProductCategory::class);
    }
    public function xnavs()
    {
        return $this->hasMany(Xnav::class)->orderBy('order');
    }
    public function xconfigs()
    {
        return $this->hasMany(Xconfig::class);
    }
    public function exceededDiskSpaceLimit($size): int
    {
        return ($this->used_disk_size + $size) > $this->disk_size;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class, 'client_id');
    }
    public function support_messages(): HasMany
    {
        $query = $this->hasMany(SupportRequest::class, 'client_id');

        // If the authenticated user is admin (ID 1), modify the query constraints
        if (auth()->check() && auth()->id() == 1) {
            return $query->whereNull('client_id')->orWhere('client_id', 1);
        }

        return $query;
    }
    public function alive_animals(): HasMany
    {
        return $this->hasMany(Animal::class, 'client_id')->where('is_alive', true);
    }
    /**
     * Get the bots associated with the client.
     */
    public function bots(): HasMany
    {
        return $this->hasMany(Bot::class);
    }
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'domain_id');
    }

    public function logo(): string
    {
        // if ($this->logo) {
        // $logos = is_array($this->logo) ? $this->logo : json_decode($this->logo, true);
        return 'storage/' . $this->logo ?? 'default.png';
        // }
        // $file = File::where('type', 'business_logo')->where('client_id', $this->id)->first();
        // return $file ? asset('storage/' . $file->path) : asset('default.png');
    }
    public function emails(): HasMany
    {
        return $this->hasMany(Email::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(FileSystemItem::class, 'client_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ClientCategory::class, 'category_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_clients');
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'client_plans')->where('client_plans.status', '!=', 'cancelled')->where('plans.status', 'active');
    }

    public function staffs()
    {
        $staffs_ids = UserClient::where('client_id', Auth::user()?->client_id ?? $this->id)->where('is_staff', true)->pluck('user_id');
        $staffs = User::whereIn('id', $staffs_ids)->orderBy('name')->get();
        return $staffs;
    }

    public function beneficiaries()
    {
        $beneficiaries_ids = UserClient::where('client_id', Auth::user()?->client_id ?? $this->id)->where('is_beneficiary', true)->pluck('user_id');
        $beneficiaries = User::whereIn('id', $beneficiaries_ids)->orderBy('name')->get();
        return $beneficiaries;
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function leave_with_require_roster(): HasMany
    {
        return $this->hasMany(Leave::class)->where('require_roster', true);
    }

    /**
     * Relationship with the Product model (a client has many products).
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
    /**
     * Relationship with the Product model (a client has many products).
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'supplier_id');
    }

    /**
     * Calculate the total stock value for the client.
     */
    public function get_total_stock_value(): float
    {
        $total_value = 0;
        $products = $this->products()->where('available_stock', '>', 0)->get();

        foreach ($products as $product) {
            $total_value += $product->stock_price * $product->get_total_available_stock();
        }

        return $total_value;
    }
    /**
     * Relationship with the Product model (a client has many products).
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Cart::class)->orderBy('status');
    }

    /**
     * Relationship with the Product model (a client has many products).
     */
    public function parkages(): HasMany
    {
        return $this->hasMany(Cart::class, 'courier_id', 'id')->orderBy('status');
    }

    public function available_products(): HasMany
    {
        return $this->hasMany(Product::class)->where('available_stock', '>=', 1);
    }

    public function white_papers(): HasMany
    {
        return $this->hasMany(Empowerment::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    // 📅 Shared client-wide events
    public function calendar_events(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function wallets(): hasMany
    {
        return $this->hasMany(Wallet::class, 'client_id');
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'client_id')->where('is_default', true)->withDefault(function (Wallet $wallet, Client $client) {
            $wallet->forceFill([
                'client_id'      => $client->id,
                'label'           => 'Business Wallet',
                'address'         => generate_wallet_address($client->id),
                'number'          => null,
                'balance'         => 0.00,
                'locked_balance'  => 0.00,
                'is_default'      => true,
                'private_key_encrypted' => '',
                'is_cold_storage' => false,
                'status'          => 'active',
                'created_by'      => 'system',
            ]);
            $client->wallets()->save($wallet);
        });
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function levels(): HasMany
    {
        return $this->hasMany(Level::class);
    }

    public function department_types(): HasMany
    {
        return $this->hasMany(DepartmentType::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function blogs(): HasMany
    {
        return $this->hasMany(Blog::class, 'client_id');
    }
}
