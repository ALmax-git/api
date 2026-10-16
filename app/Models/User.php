<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\AddressType;
use App\Enums\DepartmentStatus;
use App\Enums\DepartmentTypeStatus;
use App\Enums\OrderStatus;
use App\Enums\UserStatus;
use App\Enums\WalletType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string, date, bool, enum>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'first_name',
        'surname',
        'last_name',
        'phone_number',
        'country_id',
        'state_id',
        'city_id',
        'bio',
        'profile_photo_path',
        'client_id',
        'language',
        'visibility',
        'gender',
        'address',
        'date_of_birth',
        'last_seen_at',
        'account_type', # ['standard', 'agent', 'personal', ]
        'status',
        'preferences',
        'documents_id',
        'pictures_id',
        'videos_id',
        'music_id',
        'disk_size',
        'used_disk_size',
        'timezone',
        'pin',
        'ota_key',
        'ota_key_updated_at',
        'prev_ota_key',
        'ota_key_expiry_minutes',
        'is_ota_enabled',
        'nin',
        'bvn',
        'occupation',
        'data_of_employement',
        'office_address',
        'office_position',
        'telephone',
        'date_emploayed',
        'marital_status',
        'nationality',
        'residential_address',
        'is_rented',
        'means_of_id',
        'id_number',
        'next_of_kin_id',
        'signature_id',
        "relation_with_kin",
        'meta'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'     => 'datetime',
            'password'              => 'hashed',
            'last_seen_at'          => 'datetime',
            'date_of_birth'         => 'datetime',
            // 'client_id'             => 'integer',
            'country_id'            => 'integer',
            'state_id'              => 'integer',
            'city_id'               => 'integer',
            'meta'                  => 'array',
            'status'                => UserStatus::class,
        ];
    }


    public function exceededDiskSpaceLimit($size): int
    {
        return ($this->used_disk_size + $size) > $this->disk_size;
    }

    public function pictures(): BelongsTo
    {
        return $this->belongsTo(FileSystemItem::class, 'pictures_id');
    }

    public function videos(): BelongsTo
    {
        return $this->belongsTo(FileSystemItem::class, 'videos_id');
    }

    public function music(): BelongsTo
    {
        return $this->belongsTo(FileSystemItem::class, 'music_id');
    }

    public function signature(): BelongsTo
    {
        return $this->belongsTo(FileSystemItem::class, 'signature_id');
    }

    public function documents(): BelongsTo
    {
        return $this->belongsTo(FileSystemItem::class, 'documents_id');
    }

    public function is_online(): bool
    {
        return $this->last_seen_at?->gt(now()->subMinutes(2)) ?? false;
    }

    public function sent_messages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function leave_rosters(): HasMany
    {
        return $this->hasMany(LeaveRoster::class, 'user_id');
    }

    public function received_messages()
    {
        return $this->hasMany(Message::class, 'reciever_id');
    }

    public function last_chat($friend_id)
    {
        return $this->sent_messages()
            ->where('reciever_id', $friend_id)
            ->orWhere(function ($query) use ($friend_id) {
                $query->where('sender_id', $friend_id)
                    ->where('reciever_id', $this->id);
            })
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Todo::class)->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc');
    }

    public function calendar_events(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function requested_leave(): HasMany
    {
        return $this->hasMany(LeaveRequestTask::class, 'user_id')->where('status', '!=', 'reverted')->orderBy('created_at', 'desc');
    }

    public function reverted_requested_leave(): HasMany
    {
        return $this->hasMany(UserLeaveRequest::class, 'user_id')->where('status', 'reverted')->orderBy('created_at', 'desc');
    }

    public function leave_request(): HasMany
    {
        return $this->hasMany(LeaveRequestTask::class, 'staff_id')->where('status', 'pending');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function kin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'next_of_kin_id');
    }

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'user_clients')
            ->where(function ($query) {
                $query->where('status', 'verified')
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('status', '!=', UserStatus::BANNED->value)
                            ->where('user_clients.user_id', $this->id);
                    });
            });
    }

    public function get_all_cleints()
    {
        return $this->belongsToMany(Client::class, 'user_clients')
            ->where('user_clients.user_id', $this->id)
            ->where('clients.status', '!=', UserStatus::BANNED->value);
    }

    public function level(): HasOneThrough
    {
        return $this->hasOneThrough(
            Level::class,
            UserClient::class,
            'user_id',
            'id',
            'id',
            'level_id'
        )->where('user_clients.client_id', $this->client_id);
    }
    public function next_of_kin(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class,
            UserNextOfKin::class,
            'user_id',
            'id',
            'id',
            'kin_id'
        );
    }
    public function referee(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class,
            Referee::class,
            'user_id',
            'id',
            'id',
            '_user_id'
        );
    }
    public function _next_of_kin()
    {
        return $this->hasOne(UserNextOfKin::class, 'user_id');
    }


    public function _referees(): HasMany
    {
        return $this->hasMany(Referee::class);
    }

    public function department_unit(): HasOneThrough
    {
        return $this->hasOneThrough(
            DepartmentUnit::class,
            UserClient::class,
            'user_id',
            'id',
            'id',
            'department_unit_id'
        )->where('user_clients.client_id', $this->client_id)
            ->where('department_units.status', 'active');
    }

    public function department(): HasOneThrough
    {
        return $this->hasOneThrough(
            Department::class,
            UserClient::class,
            'user_id',
            'id',
            'id',
            'department_id'
        )->where('user_clients.client_id', $this->client_id)
            ->where('departments.status', DepartmentStatus::ACTIVE->value);
    }

    public function is_unit_head(): bool
    {
        return DepartmentUnit::where('user_id', $this->id)->where('status', 'active')->exists();
    }

    public function is_department_head(): bool
    {
        return Department::where('user_id', $this->id)->where('status', DepartmentStatus::ACTIVE->value)->exists();
    }

    public function is_department_type_head(): bool
    {
        return DepartmentType::where('user_id', $this->id)->where('status', DepartmentTypeStatus::ACTIVE->value)->exists();
    }

    public function is_staff(): bool
    {
        return UserClient::where('user_id', $this->id)
            ->where('is_staff', true)
            ->where('client_id', Auth::user()->client->id)
            ->exists();
    }

    public function is_admin(): bool
    {
        return $this->id === $this->client->owner->id;
    }

    public function is_currently_on_leave(): bool
    {
        return $this->leave_records()->where('status', 'approved')
            ->whereDate('starting_day', '<=', now())
            ->whereDate('resuming_day', '>=', now())
            ->exists();
    }

    public function current_leave()
    {
        return $this->leave_records()->where('status', 'approved')
            ->whereDate('starting_day', '<=', now())
            ->whereDate('resuming_day', '>=', now())
            ->first();
    }

    public function leave_status(): string
    {
        return $this->is_currently_on_leave() ? 'On Leave' : 'Active';
    }

    public function full_name(): string
    {
        return trim("{$this->first_name} {$this->surname} {$this->last_name}");
    }

    public function leave_records(): HasMany
    {
        return $this->hasMany(\App\Models\UserLeaveRequest::class);
    }


    public function cart_items(): HasMany
    {
        return $this->hasMany(Cart::class)->where('status', '!=', OrderStatus::CANCELLED->value);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function equity_applications(): HasMany
    {
        return $this->hasMany(\App\Models\EquityApplication::class, 'user_id');
    }

    public function selected_cart_items(): HasMany
    {
        return $this->hasMany(Cart::class)->where('is_selected', true);
    }

    public function wallets(): hasMany
    {
        return $this->hasMany(Wallet::class, 'user_id');
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'user_id')->where('is_default', true)->withDefault(function (Wallet $wallet, User $user) {
            $wallet->forceFill([
                'user_id'      => $user->id,
                'label'           => 'Personal Wallet',
                'address'         => generate_wallet_address($user->id),
                'number'          => $user->telephone ?? null,
                'balance'         => 0.00,
                'locked_balance'  => 0.00,
                'is_default'      => true,
                'private_key_encrypted' => '',
                'is_cold_storage' => false,
                'status'          => 'active',
                'created_by'      => 'system',
            ]);
            $user->wallets()->save($wallet);
        });
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

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->where('roles.status', 'active')
            ->where('user_roles.client_id', Auth::user()->client->id);
    }

    public function white_papers(): HasMany
    {
        return $this->hasMany(Empowerment::class, 'target_id')->where('empowerments.status', 'pending');
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'followers', 'target_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(ProductLike::class);
    }

    public function like(string|int $id): bool
    {
        return ProductLike::where('user_id', $this->id)->where('product_id', $id)->exists();
    }

    public function sentTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'id');
    }

    public function event_tickets(): HasMany
    {
        return $this->hasMany(EventTicket::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function department_units(): HasMany
    {
        return $this->hasMany(DepartmentUnit::class);
    }

    public function user_clients(): HasMany
    {
        return $this->hasMany(UserClient::class);
    }

    public function user_client(): HasOne
    {
        return $this->hasOne(UserClient::class, 'user_id')->where('user_clients.client_id', Auth::user()->client->id);
    }

    public function leave_route(): HasMany
    {
        return $this->hasMany(LeaveRoute::class, 'user_id');
    }

    public function get_leave_route(): array
    {
        return app('App\Services\LeaveRouteService')->get_approval_route($this);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function shipping_addresses()
    {
        return $this->addresses()->where('type', AddressType::SHIPPING->value);
    }

    public function contact_addresses()
    {
        return $this->addresses()->where('type', AddressType::CONTACT->value);
    }

    public function leave_request_addresses()
    {
        return $this->addresses()->where('type', AddressType::LEAVE_REQUEST->value);
    }

    public function blogs(): HasMany
    {
        return $this->hasMany(Blog::class, 'author_id');
    }
}
