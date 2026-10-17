<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BotStatus;
use App\Enums\BranchStatus;
use App\Enums\FacilityStatus;
use App\Enums\FacilityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\Bot;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Domain;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{

    public function boot_xapp(Request $request, string $xnamespace): JsonResponse
    {

        return response()->json([
            'success' => true,
            'message' => 'App authenticated.',
            'xnamespace' => $xnamespace,
        ], 401);
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id'            => make_uuid($user->id),
                'uuid'          => make_uuid($user->id, 'user'),
                'name'          => $user->name,
                'username'      => $user->username,
                'email'         => $user->email,
                'first_name'    => $user->first_name,
                'surname'       => $user->surname,
                'last_name'     => $user->last_name,
                'phone_number'  => $user->phone_number,
                'country'       => $user->country?->name,
                'state'         => $user->state?->name,
                'city'          => $user->city?->name,
                'bio'           => $user->bio,
                'profile_photo_url' => $user->profile_photo_url ?? 'default.png',
                'language'      => $user->language,
                'gender'        => $user->gender,
                'address'       => $user->address,
                'date_of_birth' => $user->date_of_birth,
                'status'        => $user->status,
                'preferences'   => $user->preferences,
                'timezone'      => $user->timezone,
                'wallet'        => [
                    'uuid'              => make_uuid($user->wallet?->id, 'wallet'),
                    'label'             => $user->wallet?->label ?? null,
                    'icon_url'          => $user->wallet?->icon_url ?? null,
                    'address'           => $user->wallet?->address ?? null,
                    'number'            => $user->wallet?->number ?? null,
                    'locked_balance'    => $user->wallet?->locked_balance ?? 0,
                    'balance'           => $user->wallet?->balance ?? 0,
                    'available_balance' => $user->wallet?->available_balance ?? 0,
                    'private_key_encrypted' => $user->wallet?->private_key_encrypted ?? null,
                    'created_by'        => $user->wallet?->created_by ?? null,
                    'provider'          => $user->wallet?->provider ?? null,
                    'tag'               => $user->wallet?->tag ?? null,
                    'metadata'          => $user->wallet?->metadata ?? null,
                    'is_default'        => $user->wallet?->is_default ?? null,
                    'is_active'         => $user->wallet?->is_active ?? null,
                    'is_cold_storage'   => $user->wallet?->is_cold_storage ?? null,
                    'status'            => $user->wallet?->status ?? null,
                    'last_balance_sync' => $user->wallet?->last_balance_sync ?? null,
                    'activated_at'      => $user->wallet?->activated_at ?? null,
                    'deactivated_at'    => $user->wallet?->deactivated_at ?? null,
                    'pin'               => $user->wallet?->pin ?? null,
                    'account_number'    => $user->wallet?->account_number ?? null,
                    'account_name'      => $user->wallet?->account_name ?? null,
                    'bank_id'           => $user->wallet?->bank_id ?? null,
                    'bank_name'         => $user->wallet?->bank?->name ?? null,
                ]
            ],
        ], 200);
    }
    public function clientConfig(Request $request): JsonResponse
    {
        $bot = Bot::where('device_key', $request->device_key)->first();
        return response()->json(
            [
                ''
            ]
        );
    }
    public function resolve(Request $request)
    {
        $namespace = $request->header('X-Namespace');
        $nodeId = $request->header('X-Node-Id');

        $domain = Domain::where('name_space', $namespace)
            ->where('status', true)
            ->where('is_live', true)
            ->first();

        if (!$domain) {
            return response()->json([
                'valid' => false,
                'message' => 'Namespace not found or inactive.',
            ], 404);
        }

        // Replace this with your actual node authorization logic.
        // $nodeAuthorized = $this->nodeBelongsToDomain(
        //     $nodeId,
        //     $domain
        // );

        if ($domain->status->value  != \App\Enums\DomainStatus::ACTIVE->value) {
            return response()->json([
                'valid' => false,
                'message' => 'Node is not authorized or inactive.',
            ], 403);
        }

        return response()->json([
            'valid' => true,
            'namespace' => $domain->name_space,
            'domain' => $domain->url,
            'node_id' => $nodeId,
        ]);
    }
    public function device_activation(Request $request): JsonResponse
    {

        $bot  = Bot::where('device_key', read($request->terminal_token))->first();
        if (!$bot) return response()->json(['status' => 419, 'success' => false, 'errow' => '98761']);
        Log::alert('TEST', [$bot]);
        if ($bot?->status == BotStatus::ACTIVE->value) {
            $client                         = Client::find($bot->client_id);
            return response()->json([
                'success'                   => true,
                'status'                    => $bot->status,
                'staffs'                    => $this->get_client_staff($client),
                'key'                       => write($bot->device_key),
                'terminal_name'             => $bot->name,
                'client'                    => $client,
                'client_name'               => $client->name,
                'stores'                    => $this->get_client_stores($client),
                'branches'                  => $this->get_client_branches($client),
            ], 200);
        }
        if ($bot?->status == BotStatus::PAIRING->value) {
            return response()->json([
                'success' => false,
                'status' => $bot->status,
            ], 200);
        }
        if ($bot) {
            return response()->json([
                'success' => false,
                'status' => $bot->status,
            ], 500);
        }

        return response()->json([
            'success' => false,
        ], 419);
    }
    public function register_device(Request $request): JsonResponse
    {
        $code = trim((string) $request->input('code', ''));

        if ($code === '') {
            return response()->json([
                'success' => false,
                'message' => 'Device code is required.',
            ], 422);
        }

        $bot = Bot::where('device_key', $code)->first();

        if (! $bot) {
            return response()->json([
                'success' => false,
                'message' => 'Device code not found.',
            ], 404);
        }

        $inactiveStatus = BotStatus::INACTIVE->value ?? 'inactive';
        if ($bot->status == $inactiveStatus || $bot->status == 'inactive') {
            $bot->status = BotStatus::PAIRING->value;
            $bot->save();

            return response()->json([
                'success' => true,
                'status' => $bot->status,
                'key' => write($bot->device_key),
            ], 200);
        }

        return response()->json([
            'success' => false,
            'status' => $bot->status,
            'message' => 'Device is already paired or not inactive.',
        ], 409);
    }
    /**
     * Authenticate user & issue a device-bound API token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        $passwordHash = $user?->password ?? $user?->pin ?? null;
        $isValidPassword = $user
            && (
                ($passwordHash && Hash::check($request->password, $passwordHash))
                || ($user->pin && $user->pin === $request->password)
            );

        if (! $user || ! $isValidPassword) {
            throw ValidationException::withMessages([
                'email' => ['An Invalid credentials provided for Core-X authentication.'],
            ]);
        }

        // if (isset($user->status) && $user->status !== 'active') {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Account is inactive. Contact system administrator.',
        //     ], 403);
        // }

        // Default abilities for POS terminals / frontend clients if none provided
        $abilities = $request->input('abilities', [
            'pos:access',
            'pos:transactions',
            'pos:offline-sync',
        ]);

        // Issue token tied to device name
        $bot = Bot::where('device_key', read($request->terminal_token))->first();
        $token = $user->createToken($bot->name, $abilities);

        return response()->json([
            'success' => true,
            'message' => 'Authentication successful.',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'abilities' => $token->accessToken->abilities,
                'issued_at' => now()->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Fetch current authenticated user context.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($request->user()),
                'current_device' => [
                    'id' => $request->user()->currentAccessToken()->id,
                    'name' => $request->user()->currentAccessToken()->name,
                    'abilities' => $request->user()->currentAccessToken()->abilities,
                    'last_used_at' => $request->user()->currentAccessToken()->last_used_at?->toIso8601String(),
                ],
            ],
        ], 200);
    }

    /**
     * Fast authentication verification ping for offline-first clients (e.g., X-POS).
     */
    public function verifyToken(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'status' => 'authenticated',
            'timestamp' => now()->toIso8601String(),
            'device_id' => $request->user()->currentAccessToken()->id,
        ], 200);
    }

    /**
     * Logout from current device (Revoke active token).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out from current device.',
        ], 200);
    }

    /**
     * Revoke all active sessions across all devices for this user.
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'All active device tokens have been revoked.',
        ], 200);
    }

    /**
     * List all active devices/terminals logged into this account.
     */
    public function activeDevices(Request $request): JsonResponse
    {
        $devices = $request->user()->tokens()->get(['id', 'name', 'abilities', 'last_used_at', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => $devices,
        ], 200);
    }

    /**
     * Revoke a specific device token by token ID.
     */
    public function revokeDevice(Request $request, string $tokenId): JsonResponse
    {
        $deleted = $request->user()->tokens()->where('id', $tokenId)->delete();

        if (! $deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Device session not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => "Device token [{$tokenId}] revoked successfully.",
        ], 200);
    }

    /**
     * Fetch all products for the authenticated user.
     */
    public function getProducts(Request $request): JsonResponse
    {
        $user                                       = $request->user();
        $client                                     = $user->client;
        if (! $client) {
            return response()->json([
                'success'                           => false,
                'message'                           => 'No client associated with the authenticated user.',
            ], 404);
        }
        if (! $client->products()->exists()) {
            return response()->json([
                'success'                           => false,
                'message'                           => 'No products found for the associated client.',
            ], 404);
        }
        if (can_access('products')) {
            return response()->json([
                'success' => true,
                'data' => $client->products->map(
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
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access products.',
            ], 403);
        }
    }

    /**
     * Fetch all staff members for the authenticated user.
     */
    public function getStaffs(Request $request): JsonResponse
    {
        $user = $request->user();
        $client = $user->client;
        if (! $client) {
            return response()->json([
                'success' => false,
                'message' => 'No client associated with the authenticated user.',
            ], 404);
        }
        if (! $client->staffs()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No staff members found for the associated client.',
            ], 404);
        }
        return response()->json([
            'success'                   => true,
            'data'                      => $this->get_client_staff($client),
        ], 200);
    }
    private function get_client_branches(Client $client)
    {
        $branches = Branch::where(
            'client_id',
            $client->id
        )
            ->where('status', '!=', BranchStatus::SUSPENDED->value)
            ->where('status', '!=', BranchStatus::TERMINATED->value)
            ->get()
            ->map(
                fn($branche) => [
                    'id'                => $branche->id,
                    'manager'           => $branche->manager->name,
                    'address'           => $branche->address->country->flag .  $branche->address->country->name . ' ' . $branche->address->state->name . ' ' . $branche->address->city->name . ' ' .  $branche->address->address_line_1 ?? $branche->address->street . ' ' . $branche->address->phone_number,
                    'name'              => $branche->name,
                    'code'              => $branche->code,
                    'email'             => $branche->email,
                    'phone_number'      => $branche->phone_number,
                    'status'            => $branche->status,
                ]
            )
            ->values();
        return $branches;
    }
    private function get_client_stores(Client $client)
    {
        $stores = Facility::where(
            'client_id',
            $client->id
        )
            ->where('type', FacilityType::STORE->value)
            ->where('status', '!=', FacilityStatus::SETUP->value)
            ->get();
        return $stores;
    }
    private function get_client_staff(Client $client)
    {

        $staffs                         = $client->staffs()->map(
            fn($user) => [
                'id'                    => make_uuid($user->id, 'user'),
                'name'                  => $user->name,
                'username'              => $user->username,
                'email'                 => $user->email,
                'first_name'            => $user->first_name,
                'surname'               => $user->surname,
                'last_name'             => $user->last_name,
                'phone_number'          => $user->phone_number,
                'country'               => $user->country?->name,
                'state'                 => $user->state?->name,
                'city'                  => $user->city?->name,
                'bio'                   => $user->bio,
                'profile_photo_path'    => $user->profile_photo_path,
                'language'              => $user->language,
                'gender'                => $user->gender,
                'address'               => $user->address,
                'date_of_birth'         => $user->date_of_birth,
                'status'                => $user->status,
                'preferences'           => $user->preferences,
                'timezone'              => $user->timezone,
            ]
        )
            ->values();
        return $staffs;
    }
}
