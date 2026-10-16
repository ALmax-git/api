<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Services\RoutingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'agent_id',
        'client_id',
        'product_id',
        'variant_id',
        'address_id',
        'courier_id',
        'branch_id',
        'quantity',
        'price',
        'total',
        'status',
        'reservation_end_time',
        'paid_amount',
        'installment_balance',
        'is_selected',
        'tx_ref',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status'        => OrderStatus::class,
        ];
    }
    /**
     * Get the user that owns the cart.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    /**
     * Get the user that owns the cart.
     */
    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
    /**
     * Get the user that owns the cart.
     */
    public function address()
    {
        return $this->belongsTo(Address::class, 'address_id');
    }
    /**
     * Get the user that owns the cart.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    /**
     * Get the user that owns the cart.
     */
    public function courier()
    {
        return $this->belongsTo(Client::class, 'courier_id');
    }
    /**
     * Get the user that owns the cart.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
    /**
     * Get the product associated with the cart.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant associated with the cart.
     */
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }


    /**
     * Calculate the remaining balance for the installment plan.
     */
    public function getRemainingBalanceAttribute()
    {
        return $this->installment_balance - $this->paid_amount;
    }

    /**
     * Scope to filter carts by status.
     */
    public function scope_pending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeAbandoned($query)
    {
        return $query->where('status', 'abandoned');
    }

    /**
     * Check if the cart is reserved and expired.
     */
    public function isReservationExpired()
    {
        return $this->reservation_end_time && $this->reservation_end_time < now();
    }

    /**
     *
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'item_id');
    }

    public function is_shipped(): bool
    {
        return $this->status->value === OrderStatus::SHIPPED->value;
    }

    /**
     *@param $x from city
     *@param $y to city
     */
    public function shipping_service($x, $y)
    {
        $routing_service = new RoutingService($x, $y);
        return $routing_service->x_to_y_cost();
    }
}
