<?php

namespace App\Http\Controllers;

use App\Enums\ClientStatus;
use App\Enums\UserStatus;
use App\Models\Client;
use App\Models\User;
use App\Models\UserClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Polyfill\Uuid\Uuid;

class AccountsController extends Controller
{
    public function new_supplier(Request $request)
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'rc_number'      => ['required', 'string', 'max:100'],
            'phone_number'   => ['required', 'string', 'max:20'],
            'telephone'      => ['required', 'string', 'max:20'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email'          => ['nullable', 'email', 'max:255'],
            'address'        => ['nullable', 'string', 'max:500'],
        ]);

        // Current business
        $owner = auth()->user()->client;

        if (!$owner) {
            return response()->json([
                'success' => false,
                'message' => 'Business account not found.'
            ], 403);
        }

        // Prevent duplicate client
        $exists = Client::where('phone_number', $data['phone_number'])
            ->orWhere('telephone', $data['telephone'])
            ->first();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier already exists in Core-X.'
            ], 422);
        }

        // Prevent creating someone that already has a Core-X user
        $userExists = User::where('phone_number', $data['phone_number'])
            ->orWhere('telephone', $data['telephone'])
            ->exists();

        if ($userExists) {
            return response()->json([
                'success' => false,
                'message' => 'This phone number already belongs to a Core-X account.'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $safe_name = generate_tx_ref();

            $claim_user = User::create([
                'name'                      => $data['contact_person'],
                'username'                  => $safe_name,
                'email'                     => $safe_name . '@' . 'claim' . '.almax',
                'password'                  => Hash::make(generate_tx_ref()),
                'phone_number'              => $data['phone_number'],
                'client_id'                 => 1,
                'visibility'                => 'private',
                'address'                   => $data['address'],
                'status'                    => UserStatus::DEACTIVATED->value,
            ]);

            $supplier = Client::create([
                'status'        => ClientStatus::SETUP->value,
                'user_id'       => $claim_user->id,
                'category_id'   => 15,
                'name'           => $data['name'],
                'rc_number'      => $data['rc_number'],
                'phone_number'   => $data['phone_number'],
                'telephone'      => $data['telephone'],
                // 'contact_person' => $data['contact_person'],
                'email'          => $data['email'],
                'address'        => $data['address'],
            ]);

            $claim_user->client_id = $supplier->id;

            UserClient::create([
                'user_id'           => $claim_user->id,
                'is_staff'          => true,
                'client_id'         => $supplier->id,
                'staff_number'      => rand(0, 9999),
            ]);

            // Create supplier relationship
            $owner->suppliers()->attach($supplier->id, [
                'relationships' => json_encode(['supply']),
                'sign_by_id'    => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'success'  => true,
                'message'  => 'Supplier created successfully.',
                'supplier' => $supplier
            ]);
        } catch (\Throwable $e) {

            Log::error('Unable to create supplier', [$e]);
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Unable to create supplier.',
            ], 500);
        }
    }
    public function auth_user()
    {
        $user = [];
        $user['has_pin'] = auth()->user()->pin != null;
        $user['name'] = auth()->user()->name;
        $user['email'] = auth()->user()->email;
        $user['uuid'] = make_uuid(auth()->user()->id, 'user');
        $user['permissions'] = user_access();
        return response()->json($user);
    }
    public function list()
    {
        // Note: auth()->user() already returns the User model, 
        // no need to find it again.
        $user = auth()->user();

        // Use get() to transform the Relation into a Collection
        $clients = $user->clients()
            ->withPivot('id') // Ensure the pivot ID is available
            ->get()
            ->map(function ($client) {
                return [
                    'name'             => $client->name,
                    // Accessing the ID from the pivot table (user_clients)
                    '_uuid' => make_uuid($client->id, 'client'),
                    // 'users_client_uuid' => $client,
                    'is_current' => auth()->user()->client->id == $client->id ? 'checked' : '',
                ];
            });

        return response()->json($clients);
    }
    public function switch(Request $request)
    {
        if ($request->userId != 'current') {
            $request->validate([
                'userId' => 'required|exists:users,id',
            ]);
        }
        $request->validate([
            'pin' => 'required|string',
        ]);
        if ($request->userId == 'current' && !auth()->check()) {
            return redirect()->route('app');
        }
        $user = $request->userId == 'current' ? User::find(auth()->user()->id) : User::find($request->userId);

        // If you're storing hashed PINs (you should)
        if (!Hash::check($request->pin, $user->pin)) {
            return response()->json([
                'message' => 'Invalid PIN',
            ], 401);
        }

        last_seen(['from-' . auth()?->user()?->id, 'to-' . $user->id, 'switch-account', $request->only('ip', 'user_agent')]);
        // ✅ Switch authenticated user
        // Auth::login($user);

        return response()->json([
            'user' => $user,
        ]);
    }
    public function switch_user_client(Request $request, $uuid)
    {
        // log::alert($uuid);
        try {
            if (!$uuid) {
                return response()->json([
                    'status' => '419',
                    'message' => 'This Action is not allowed!',
                ]);
            }
            $client = Client::find(get_id($uuid, 'client'));
            if (!$client) {
                return response()->json([
                    'status' => '419',
                    'message' => 'This Action is not allowed please try again!',
                    'data' => get_id($uuid, 'client'),
                ]);
            }
            $user_client = UserClient::where('client_id', $client->id)->where('user_id', auth()->user()->id)->first();
            if (!$user_client) {
                return response()->json([
                    'status' => '419',
                    'message' => 'This Action is not allowed Account Does not Exist!',
                ]);
            }
            $user = User::find(auth()->user()->id);
            $user->client_id = $client->id;
            $user->save();
            return response()->json([
                'status' => 'success',
                'message' => 'success',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => '500',
                'message' => 'Something when wrong!',
            ]);
        }
    }
    public function activation(Request $request)
    {
        try {
            if (($request->header('X-App-Source')) == 'Core-X New Account') {
                $validator = Validator::make(
                    $request->all(),
                    [
                        'fullName'                              => ['required', 'string', 'min:2', 'max:120'],
                        // 'pin'                                   => ['nullable', 'string', 'max:4', 'min:4'],
                        'phone'                                 => ['nullable', 'string', 'max:30'],
                        'username'                              => ['required', 'string', 'min:3', 'max:60', 'alpha_dash'],
                        'language'                              => ['required', 'string', 'size:2'],
                        'timezone'                              => ['required', 'string', 'max:50'],
                        'accountType'                           => ['required', 'string', 'in:personal,business'],
                        'country'                               => ['required', 'string', 'size:2', 'exists:countries,iso2'],
                        'state'                                 => ['required', 'string', 'max:100'],
                        'city'                                  => ['required', 'string', 'max:100'],
                        'street'                                => ['required', 'string', 'max:255'],
                        'postalCode'                            => ['nullable', 'string', 'max:20'],
                        'businessName'                          => ['nullable', 'string', 'max:150'],
                        'businessEmail'                         => ['nullable', 'email', 'max:150'],
                        'businessAddress'                       => ['nullable', 'string', 'max:255'],
                        'isRegisteredBusiness'                  => ['nullable', 'boolean'],
                        'nin'                                   => ['nullable', 'string', 'max:50'],
                        'tin'                                   => ['nullable', 'string', 'max:50'],
                        'cac'                                   => ['nullable', 'string', 'max:50'],
                    ]
                );
            } else if (read($request->header('X-App-Source')) == 'Core-X Setup') {
                $validator = Validator::make(
                    $request->all(),
                    [

                        'country'                               => ['required', 'string', 'size:2', 'exists:countries,iso2'],
                        'state'                                 => ['required', 'string', 'max:100'],
                        'city'                                  => ['required', 'string', 'max:100'],
                        'street'                                => ['required', 'string', 'max:255'],
                        'postalCode'                            => ['nullable', 'string', 'max:20'],

                        'businessName'                          => ['required', 'string', 'max:150'],
                        'businessEmail'                         => ['required', 'email', 'max:150'],
                        'businessAddress'                       => ['required', 'string', 'max:255'],
                        'isRegisteredBusiness'                  => ['required', 'boolean'],

                        'nin'                                   => ['nullable', 'string', 'max:50'],
                        'tin'                                   => ['nullable', 'string', 'max:50'],
                        'cac'                                   => ['nullable', 'string', 'max:50'],
                    ]
                );
            } else {
                abort(401, 'Unauthorized access.');
            }
            if ($validator->fails()) {
                return response()->json(
                    [
                        'status'                                => 'validation_error',
                        'errors'                                => $validator->errors(),
                    ],
                    422
                );
            }

            $data = $validator->validated();
            if ($data['accountType'] === 'personal') {
                $data['businessName']                   = null;
                $data['businessEmail']                  = null;
                $data['businessAddress']                = null;
                $data['isRegisteredBusiness']           = false;
                $data['nin']                            = null;
                $data['tin']                            = null;
                $data['cac'] = null;

                $user  = User::find(auth()->user()->id);
                $user->name = $data['fullName'];
                $user->phone_number = isset($data['phone']) ? $data['phone'] : '';
                // $user->pin = Hash::make(trim($data['pin']));
                $user->username = $data['username'];
                $user->language = in_array($data['language'], ['English', 'Arabic', 'Chinese', 'French']) ? $data['language'] : 'English';
                $user->timezone = $data['timezone'];
                $user->status = UserStatus::PENDING_VERIFICATION->value;
                $user->save();
                return response()->json([
                    'status' => 'ok',
                    'message' => 'Account setup completed successfully',
                ], 201)->header('Cache-Control', 'no-store');
            }

            if ($data['accountType'] === 'business') {

                $user  = User::find(auth()->user()->id);
                $user->name = $data['fullName'];
                // $user->pin = $data['pin'] ? Hash::make(trim($data['pin'])) : null;
                $user->username = $data['username'];
                $user->language = in_array($data['language'], ['English', 'Arabic', 'Chinese', 'French']) ? $data['language'] : 'English';
                $user->timezone = $data['timezone'];
                $user->status = UserStatus::PENDING_VERIFICATION->value;
                $user->save();

                // $data['pin'] = $data['pin'] ? Hash::make($data['pin']) : null;
                $data['businessEmail'] = $data['businessEmail'] ? strtolower($data['businessEmail']) : null;
                $data['username'] = strtolower($data['username']);
                $data['isRegisteredBusiness'] = (bool) $data['isRegisteredBusiness'];
                if ($data['isRegisteredBusiness'] === false) {
                    $data['nin']                    = null;
                    $data['tin']                    = null;
                    $data['cac']                    = null;
                }
                if (Client::where('email', $data['businessEmail'])->exists()) {
                    return response()->json(
                        [
                            'status'                => 'error',
                            'message'               => 'The business email has already been taken.',
                        ],
                        409
                    );
                }
                if (Client::where('name', $data['businessName'])->exists()) {
                    return response()->json(
                        [
                            'status'                => 'error',
                            'message'               => 'The business name has already been taken.',
                        ],
                        409
                    );
                }
                $new_client = Client::create(
                    [
                        'user_id'                   => auth()->user()->id,
                        'name'                      => $data['businessName'],
                        'email'                     => $data['businessEmail'],
                        'address'                   => $data['businessAddress'],
                        'is_registered'             => $data['isRegisteredBusiness'] ? true : false,
                        'category_id'               => 15, // Default to 'General' category
                    ]
                );
                $user->client_id = $new_client->id;
                $user->save();
                UserClient::create([
                    'user_id' => $user->id,
                    'is_staff' => true,
                    'client_id' => $new_client->id,
                    // 'level_id',
                    // 'staff_number',
                    // 'department_id',
                    // 'department_unit_id',
                ]);
            }
            Log::alert('Account setup payload', $data);

            return response()->json([
                'status' => 'ok',
                'message' => 'Account setup completed successfully',
            ], 201)->header('Cache-Control', 'no-store');
        } catch (\Throwable $th) {
            Log::error('lol', [$th]);
            throw $th;
        }
    }
}
