<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\Client;
use App\Models\EquityApplication;
use App\Models\FileSystemItem;
use App\Models\Referee;
use App\Models\User;
use App\Models\UserClient;
use App\Models\UserNextOfKin;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Spatie\Image\Image;

use function Illuminate\Log\log;

class RealtorController extends Controller
{

    public function upload_client_media(Request $request)
    {

        Log::info('po', [$request->all()]);
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'required|image|max:6024',
            'payload' => 'required|email'
        ]);
        if (!$request->hasFile('files')) {
            return response()->json(['message' => 'No files uploaded.'], 422);
        }
        $user = User::where('email', $request->payload);

        $client = $user->client;

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated.',
            ], 401);
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

            $base = 'https://api.almaxcloud.com';
            $equity_application = EquityApplication::where('user_id', $user->id)->first();
            if (!$equity_application) {
                return response()->json([
                    'message' => 'Ops you need to fill user Equity Application First',
                    'success' => false,
                ]);
            }
            foreach ($request->file('files') as $file) {

                DB::transaction(function () use ($file, $user, $equity_application, $client, $folder_id, $base) {

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

                    // 🔗 Attach to produc

                    $meta = $equity_application->meta;
                    if (isset($meta['documents'])) {
                        if (!is_array($meta['documents'])) {
                            $meta['documents'] = [];
                        }
                    } else {
                        $meta['documents'] = [];
                    }
                    $meta['documents'][] = ['id' => $fileSystemItem->id, 'storage_path' => $full_path, 'name' => $fileSystemItem->name ?? 'equity_application_docs'];
                    $equity_application->meta = $meta;
                    $equity_application->save();
                    // 📊 Increment immediately
                    $lockedClient->increment('used_disk_size', $finalSize);
                });
            }
            if (($request->input('redirect_url'))) {
                return redirect($request->input('redirect_url'), 302);
            }


            return response()->json([
                'success' => true,
                'message' => 'Documents uploaded Successfull.',
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

    public function save_client(Request $request)
    {

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated...',
            ], 401);
        }

        $client = get_client_namespace('com.realtor.mobile');
        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found.',
            ], 404);
        }

        // Log::info('Saving client for user: ' . $user->id . ', client: ' . $client->id, ['request_data' => $request->all()]);

        try {
            DB::beginTransaction();

            $user = User::where('bvn', $request->input('bvn'))->first();

            if (!$user) {
                $user = User::where('client_id', $client->id)->where('email', $request->input('email') ?? $request->email)->where('visibility', 'private')->first();
            }
            // error happen around here
            Log::info('realttor', [$user, $client->id, $request->input('email') ?? $request->email]);
            if (!$user) {
                $user = new User();
            }

            if (!$user->password) {
                $user->password = Hash::make(generate_tx_ref()); // Generate a random password if not set
            }
            if (!$client) {
                return response()->json([
                    'message' => 'Client namespace not found',
                    'success' => false,
                ], 404);
            }
            $user->first_name = $request->input('first_name');
            $user->name = $request->input('name');
            $user->username = $request->input('username');
            $user->email = $request->input('email') ?? $request->email;
            $user->surname = $request->input('surname');
            $user->last_name = $request->input('last_name');
            $user->phone_number = $request->input('phone_number');
            $user->country_id = $request->input('country_id');
            $user->state_id = $request->input('state_id');
            $user->city_id = $request->input('city_id');
            $user->bio = $request->input('bio');
            $user->profile_photo_path = $request->input('profile_photo_path');
            $user->client_id = $client->id;
            $user->language = $request->input('language');
            $user->visibility = 'private'; // Set visibility to private for Realtor users
            $user->gender = $request->input('gender');
            $user->address = $request->input('address');
            $user->date_of_birth = $request->input('date_of_birth');
            $user->status = UserStatus::UNDER_REVIEW->value;
            $user->data_of_employement = $request->input('data_of_employement');
            $user->date_emploayed = $request->input('data_of_employement');
            $user->office_address = $request->input('office_address');
            $user->office_position = $request->input('office_position');
            $user->telephone = $request->input('telephone');
            $user->marital_status = $request->input('marital_status');
            $user->nationality = $request->input('nationality');
            $user->residential_address = $request->input('residential_address') ?? $request->input('address');
            $user->is_rented = $request->input('is_rented');
            $user->means_of_id = $request->input('means_of_id');
            $user->relation_with_kin = $request->input('relation_with_kin');
            $user->nin = $request->input('nin');
            $user->bvn = $request->input('bvn');
            $user->id_number = $request->input('means_of_id') == 'nimc' ?  $request->input('nin') : $request->input('id_number');
            $user->save();

            // Save next of kin information
            $user_kin = User::find($user->next_of_kin_id);
            if (!$user_kin) {
                $user_kin = new User();
            }
            $user_kin->name = $request->input('next_of_kin.name');
            $user_kin->email = $request->input('next_of_kin.email');
            $user_kin->first_name = $request->input('next_of_kin.first_name');
            $user_kin->surname = $request->input('next_of_kin.surname');
            $user_kin->last_name = $request->input('next_of_kin.last_name');
            $user_kin->phone_number = $request->input('next_of_kin.phone_number');
            $user_kin->visibility = 'private';
            $user_kin->status = UserStatus::UNDER_REVIEW->value;
            $user_kin->country_id = $request->input('next_of_kin.country_id');
            $user_kin->state_id = $request->input('next_of_kin.state_id');
            $user_kin->city_id = $request->input('next_of_kin.city_id');
            $user_kin->gender = $request->input('next_of_kin.gender');
            $user_kin->address = $request->input('next_of_kin.address');
            $user_kin->telephone = $request->input('next_of_kin.telephone');
            $user_kin->nationality = $request->input('next_of_kin.nationality');
            if (!$user_kin->password) {
                $user_kin->password = Hash::make(generate_tx_ref()); // Generate a random password if not set
            }
            if (!$user_kin->username) {
                $user_kin->username = uniqid('realtor');
            }
            $user_kin->save();

            UserNextOfKin::updateOrCreate(
                [
                    'user_id' => $user->id,
                ],
                [
                    'kin_id' => $user_kin->id,
                    'relationship' => $request->input('relation_with_kin'),
                ]
            );
            $user->next_of_kin_id = $user_kin->id;
            $user->save();

            $referee = Referee::find($user->referee_id);

            //existing
            if ($user->_referees()->where('referee_number', $request->input('referee.referee_number'))->exists()) {


                $referee = Referee::where('referee_number', $request->input('referee.referee_number'))->first();
                if ($referee) {
                    $referee->name                      = $request->input('referee.name');
                    $referee->referee_number                        = $request->input('referee.referee_number');
                    $referee->known_period                      = $request->input('referee.known_period');
                    $referee->current_account_with                      = $request->input('referee.current_account_with');
                    $referee->referee_name                      = $request->input('referee.referee_name');
                    $referee->bank_name_address                         = $request->input('referee.bank_name_address');
                    $referee->referee_account_number                        = $request->input('referee.referee_account_number');
                    $referee->user_id = $user->id;
                    $referee->save();
                }
            }
            //not > 2
            if ($user->_referees()->count() >= 2) {
                // return response()->json([
                //     'message' => 'You can only add a maximum of 2 referees for this user.'
                // ], 400);
            } else {
                $referee = new Referee();
                $referee->name                      = $request->input('referee.name');
                $referee->referee_number                        = $request->input('referee.referee_number');
                $referee->known_period                      = $request->input('referee.known_period');
                $referee->current_account_with                      = $request->input('referee.current_account_with');
                $referee->referee_name                      = $request->input('referee.referee_name');
                $referee->bank_name_address                         = $request->input('referee.bank_name_address');
                $referee->referee_account_number                        = $request->input('referee.referee_account_number');
                $referee->user_id = $user->id;
                $referee->save();
            }

            UserClient::updateOrCreate(
                [
                    'user_id'                                   => $user->id,
                    'client_id'                                 => $client->id,
                ],
                [
                    'is_staff'                                  => false,
                    'staff_number'                              => uniqid(),
                    'enroll_by_id'                              => auth()->id(),
                    'is_beneficiary'                            => true,
                ]
            );

            EquityApplication::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'pfa_id' => $request->pfa_i ? get_id($request->pfa_id, 'client') : null,
                    'pen' => $request->pen,
                    'sworn_date' => now(),
                ]
            );

            $equity_application = $user?->equity_applications->first();
            if (!$equity_application) {
                $equity_application = $user?->equity_applications()->create();
            }

            $equity_application->morgage_type                         = $request->input('equity_application.morgage_type');
            $equity_application->morgage_lender_address                       = $request->input('equity_application.morgage_lender_address');
            $equity_application->morgage_lender                       = $request->input('equity_application.morgage_lender');
            $equity_application->property_address                         = $request->input('equity_application.property_address');
            $equity_application->property_type                        = $request->input('equity_application.property_type');
            $equity_application->property_value                       = $request->input('equity_application.property_value');
            $equity_application->rsa_balance                      = $request->input('equity_application.rsa_balance');
            $equity_application->rsa_holder                       = $request->input('equity_application.rsa_holder');
            $equity_application->equity_amount                        = $request->input('equity_application.equity_amount');
            $equity_application->supplementary_payment_amount                         = $request->input('equity_application.supplementary_payment_amount');
            $meta = $equity_application->meta                         ?? [];

            $meta['amount_of_loan_required']    = $request->input('equity_application.meta.amount_of_loan_required');
            $meta['purpose_of_loan_required'] = $request->input('equity_application.meta.purpose_of_loan_required');
            $meta['loan_repayment_period']      = $request->input('equity_application.meta.loan_repayment_period');
            $meta['monthly_salary_or_income'] = $request->input('equity_application.meta.monthly_salary_or_income');
            $meta['source_of_loan_repayment'] = $request->input('equity_application.meta.source_of_loan_repayment');

            $equity_application->meta = $meta;
            $equity_application->save();

            $wallet = Wallet::find($user->wallet->id);

            $wallet->account_name = $request->input('bank.account_name');
            $wallet->account_number = $request->input('bank.account_number');
            $wallet->bank_id            = $request->input('bank.bank_id');
            $wallet->save();
            DB::commit();

            return response()->json(['message' => 'Client saved successfully'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving client: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to save client'], 500);
        }
    }
}
