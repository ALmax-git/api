<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\WalletAssetStatus;
use App\Models\Asset;
use App\Models\Transaction;
use App\Models\TransactionHistory;
use App\Models\Wallet;
use App\Models\WalletAsset;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FundController extends Controller
{
    private string                              $opay_secret_key;
    private string                              $opay_merchant_id;
    private string                              $opay_status_url;

    public function __construct()
    {
        $this->opay_merchant_id                         = config('services.opay.merchant_id');
        $this->opay_secret_key                          = config('services.opay.secret_key');
        $this->opay_status_url                          = config('services.opay.status_url', 'https://testapi.opaycheckout.com/api/v1/international/cashier/status');
    }

    public function opay_verify(string $tx): Mixed
    {
        $tx                                             = rtrim($tx, '/');
        $data                                           = [
            'country'                                   => 'NG',
            'reference'                                 => 'trx-' . $tx
        ];

        $payload_string                                 = (string) json_encode($data, JSON_UNESCAPED_SLASHES);
        $auth_signature                                 = $this->auth($payload_string);
        $tx_ref                                         = $data['reference'];

        try {
            $response = Http::withHeaders(
                [
                    'Content-Type'                      => 'application/json',
                    'Authorization'                     => 'Bearer ' . $auth_signature,
                    'MerchantId'                        => $this->opay_merchant_id,
                ]
            )->timeout(60)->post($this->opay_status_url, $data);

            if ($response->failed()) {
                Log::error("OPay external status API connection failure for tx_ref: {$tx_ref}");
                return redirect()->route('app')->with('error', 'Unable to verify payment with gateway.');
            }

            $result                                     = json_decode($response->body());
        } catch (Exception $e) {
            Log::error("OPay Status Endpoint Request Exception: " . $e->getMessage());
            return redirect()->route('app')->with('error', 'Payment processing gateway error.');
        }

        $code                                           = $result->code ?? null;
        $status                                         = $result->data->status ?? null;

        if ($code !== '00000') {
            Log::error("OPay error response returned", (array) $result);
            return redirect()->route('app')->with('error', 'Invalid tracking code from payment terminal.');
        }

        $history                                        = TransactionHistory::whereRaw('UPPER(tx_ref) = ?', [strtoupper($tx_ref)])->first();
        if (!$history) {
            Log::error("Transaction history missing reference lookups: {$tx_ref}");
            return redirect()->route('app')->with('error', 'Transaction record missing. Please contact support.');
        }

        switch ($status) {
            case 'SUCCESS':
                return $this->process_successful_opay_payment($history, $result->data);
            case 'FAIL':
                $history->update(
                    [
                        'status'                        => TransactionStatus::FAILED->value
                    ]
                );
                Log::warning("OPay payment terminal reported failure status", (array) $result);
                return redirect()->route('app')->with('error', 'Payment processing failed.');

            default:
                Log::info("OPay transaction processing state pending", (array) $result);
                return redirect()->route('app')->with('info', 'Payment status processing.');
        }
    }

    protected function process_successful_opay_payment(TransactionHistory $history, Mixed $payment_data): Mixed
    {
        $wallet_id = $history->wallet_id;

        if (in_array($history->status, ['completed', 'successful', TransactionStatus::SUCCESS->value])) {

            return redirect()->route('app')->with('info', 'This transaction has already been processed.');
        }

        $wallet = Wallet::with(
            [
                'assets' => function ($query) {
                    $query->whereHas('asset', fn($q) => $q->where('code', 'NGN'));
                }
            ]
        )->findOrFail($wallet_id);

        DB::beginTransaction();
        try {
            $exchangeRateData                               = system_exchange_rate('NGN', 'NGN', $payment_data->amount->total);
            $fee                                            = $exchangeRateData['fee'] ?? 0;
            $history->status                                = TransactionStatus::SUCCESS->value;
            $history->save();
            $_wallet = Wallet::with(
                [
                    'assets' => function ($query) {
                        $query->whereHas('asset', fn($q) => $q->where('code', 'NGN'));
                    }
                ]
            )->findOrFail(1);

            $_wallet_asset                                  = $this->getOrCreateWalletAsset($_wallet, 'NGN');
            $_wallet_asset                                  = WalletAsset::where('id', $_wallet_asset->id)->lockForUpdate()->first();

            $_wallet_asset->balance                         += $payment_data->amount->total;
            $_wallet_asset->save();

            $payload = [
                'amount'                                    => $payment_data->amount->total,
                'tx_ref'                                    => $history->tx_ref,
                'status'                                    => TransactionStatus::SUCCESS->value,
                'type'                                      => TransactionType::DEPOSIT->value,
                'description'                               => "Credit to wallet Address: {$wallet->address}",
            ];

            $new_hash                                       = Transaction::NewHash($payload);

            // Fixed the always-true evaluation syntax bug here
            if (Transaction::where('hash', $new_hash)->exists()) {
                $payload['tx_ref']     .= '/';
                $payload['description'] = "D. Credit to wallet Address: {$wallet->address}";
                $new_hash               = Transaction::NewHash($payload);
            }

            $this->credit_wallet($wallet, $payment_data->amount->total, 'NGN', $_wallet, $fee);

            DB::commit();
            return redirect()->route('app')->with('success', 'Wallet funded successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error processing OPay ledger balancing sequence:', ['error' => $e->getMessage(), 'tx_ref' => $history->tx_ref]);
            return redirect()->route('app')->with('error', 'An error occurred updating your ledger records.');
        }
    }

    public function auth(Mixed $data): string
    {
        return hash_hmac('sha512', $data, $this->opay_secret_key);
    }

    public function opay_fund_wallet(Request $request): Mixed
    {
        $request->validate(
            [
                'amount'                        => 'required|numeric|min:1',
                'wallet_id'                     => 'required|string',
            ]
        );

        $user                                   = auth()->user();
        $wallet                                 = Wallet::find(read($request->wallet_id));

        if (!$user || !$wallet) {
            return redirect()->back()->with('error', 'Invalid context parameter definitions.');
        }

        $tx_ref  = generate_tx_ref();
        $payload = [
            "country"                           => "NG",
            "reference"                         => $tx_ref,
            "amount"                            => [
                "total"                         => (int) $request->amount,
                "currency"                      => "NGN"
            ],
            "returnUrl"                         => url('/') . '/' . $tx_ref,
            "callbackUrl"                       => url('/') . '/' . $tx_ref,
            "cancelUrl"                         => url('/') . '/' . $tx_ref,
            "displayName"                       => "ALmax",
            "customerVisitSource"               => "BROWSER",
            // "evokeOpay"                         => true,
            "expireAt"                          => 300,
            "product"                           => [
                "description"                   => "Core-X Payment",
                "name"                          => "ALmax Product"
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.opay.public_key'),
            'MerchantId'    => $this->opay_merchant_id,
            'Content-Type'  => 'application/json'
        ])->post('https://testapi.opaycheckout.com/api/v1/international/cashier/create', $payload);

        $data = $response->json();

        if (!$response->successful() || !isset($data['data']['cashierUrl'])) {
            return response()->json([
                'error'         => 'Payment initialization failed',
                'opay_response' => $data,
                'Authorization' => 'Bearer ' . config('services.opay.public_key'),
                'MerchantId'    => $this->opay_merchant_id,
                'payload'       => $payload,

            ], 400);
        }

        DB::transaction(function () use ($wallet, $request, $tx_ref) {
            $wallet->history()->create([
                'amount'      => $request->amount,
                'tx_ref'      => $tx_ref,
                'status'      => TransactionStatus::PENDING->value,
                'type'        => TransactionType::DEPOSIT->value,
                'description' => "Funding wallet",
            ]);
        });

        return redirect()->away($data['data']['cashierUrl']);
    }

    public function fund_wallet(Request $request)
    {
        $request->validate([
            'amount'         => 'required|numeric|min:1',
            'wallet_id'      => 'required|string',
            'payment_method' => 'required|string'
        ]);

        if ($request->payment_method === 'opay') {
            return $this->opay_fund_wallet($request);
        }

        $user   = auth()->user();
        $wallet = Wallet::find(read($request->wallet_id));

        if (!$user || !$wallet) {
            return redirect()->back()->with('error', 'Invalid target configuration state parameters.');
        }

        try {
            $controller = flutterwave_payment();
            $tx_ref     = generate_tx_ref();
            // dd($controller);

            $data = [
                'payment_method' => 'card,banktransfer',
                'amount'         => (int) $request->amount,
                'email'          => auth()->user()->email ?? 'customer@almax.com',
                'tx_ref'         => $tx_ref,
                'first_name'     => auth()->user()->first_name ?? 'Ali',
                'last_name'      => auth()->user()->last_name ?? 'Mustapha',
                'currency'       => 'NGN',
                'redirect_url'   => route('fund_wallet_callback'),
                'success_url'    => route('fund_wallet_callback'),
                'failure_url'    => route('fund_wallet_callback'),
                'customer'       => [
                    'email'        => auth()->user()->email ?? 'customer@almax.com',
                    "phone_number" => auth()->user()->phone_number ?? '08000000000',
                    "name"         => auth()->user()->name ?? 'ALmax User'
                ],
                "customizations" => [
                    "title"       => 'Wallet Funding',
                    "description" => "Fund your wallet",
                ]
            ];

            DB::transaction(function () use ($wallet, $request, $tx_ref) {
                $wallet->history()->create([
                    'amount'      => $request->amount,
                    'tx_ref'      => $tx_ref,
                    'status'      => TransactionStatus::PENDING->value,
                    'type'        => TransactionType::DEPOSIT->value,
                    'description' => "Funding wallet",
                ]);
            });

            $controller->process($data);
        } catch (\Throwable $e) {
            Log::error('Payment processing init trace exception: ' . $e->getMessage());
            abort(500, "An error occurred during the execution steps.");
        }
        // return;
    }

    public function fund_wallet_callback(Request $request): Mixed
    {
        $validated = $request->validate([
            'tx_ref' => 'required|string',
        ]);
        $tx_ref = $validated['tx_ref'];

        $history = TransactionHistory::where('tx_ref', $tx_ref)->first();

        if (!$history) {
            Log::error("Transaction lookup criteria empty for reference identifier: {$tx_ref}", $validated);
            return redirect()->route('app')->with('error', 'Transaction processing criteria unmatched.');
        }

        if ($request->status === 'cancelled') {
            $history->update(['status' => TransactionStatus::CANCELLED->value]);
            Log::error("Transaction validation cancelled by user interactively: {$tx_ref}");
            return redirect()->route('app')->with('error', 'Transaction execution context aborted.');
        }

        $verificationResponse = $this->verifyPayment($tx_ref);

        if (!$verificationResponse['success']) {
            $history->update(['status' => TransactionStatus::FAILED->value]);
            Log::error("Payment tracking validation assertion failed for reference: {$tx_ref}", $verificationResponse['data']);
            return redirect()->route('app')->with('error', $verificationResponse['message']);
        }

        return $this->processSuccessfulPayment($history, (object) $verificationResponse['data']);
    }

    protected function verifyPayment(string $transactionReference): array
    {
        // Enforcing structured verify framework utilities
        $response = varify_payment($transactionReference);

        if (isset($response->status) && $response->status === 'success') {
            return [
                'success' => true,
                'message' => 'Payment verified successfully.',
                'data'    => $response->data,
            ];
        }

        return [
            'success' => false,
            'message' => 'Payment validation failed.',
            'data'    => (array) $response,
        ];
    }

    protected function processSuccessfulPayment(TransactionHistory $history, object $paymentData)
    {
        $walletId = $history->wallet_id;

        if (in_array($history->status, ['completed', 'successful', TransactionStatus::SUCCESS->value])) {
            return redirect()->route('app')->with('info', 'This transaction has already been processed.');
        }

        $wallet = Wallet::with(['assets' => function ($query) {
            $query->whereHas('asset', fn($q) => $q->where('code', 'NGN'));
        }])->findOrFail($walletId);

        DB::beginTransaction();
        try {
            $exchangeRateData = system_exchange_rate('NGN', 'NGN', $paymentData->amount_settled);
            $fee              = $exchangeRateData['fee'] ?? 0;

            $history->status = TransactionStatus::SUCCESS->value;
            $history->save();

            $_wallet = Wallet::with(['assets' => function ($query) {
                $query->whereHas('asset', fn($q) => $q->where('code', 'NGN'));
            }])->findOrFail(1);

            $_wallet_asset = $this->getOrCreateWalletAsset($_wallet, 'NGN');
            $_wallet_asset = WalletAsset::where('id', $_wallet_asset->id)->lockForUpdate()->first();

            $_wallet_asset->balance += $paymentData->amount_settled;
            $_wallet_asset->save();

            $payload = [
                'amount'      => $paymentData->amount_settled,
                'tx_ref'      => $history->tx_ref,
                'status'      => TransactionStatus::SUCCESS->value,
                'type'        => TransactionType::DEPOSIT->value,
                'description' => "Credit to wallet Address: {$wallet->address}",
            ];

            Transaction::create([
                'wallet_id'    => $_wallet->id,
                '_wallet_id'   => $wallet->id,
                'asset_id'     => $_wallet_asset->asset_id,
                'from_address' => $wallet->address,
                'to_address'   => $_wallet->address,
                'status'       => TransactionStatus::SUCCESS->value,
                'tx_ref'       => $history->tx_ref,
                'hash'         => Transaction::NewHash($payload),
                'description'  => "Wallet DEPOSIT: {$wallet->address} via {$_wallet->address}",
                'type'         => TransactionType::DEPOSIT->value,
                'amount'       => $paymentData->amount_settled,
                'network_fee'  => $fee,
                'prev_hash'    => Transaction::last_hash(),
                'metadata'     => [
                    'status'  => 'success',
                    'gateway' => 'Flutterwave',
                    'Action'  => 'Wallet Funding',
                ],
            ]);

            $this->credit_wallet($wallet, $paymentData->amount_settled, 'NGN', $_wallet, $fee);

            DB::commit();
            return redirect()->route('app')->with('success', 'Wallet funded successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error processing balancing operations trace:', ['error' => $e->getMessage(), 'tx_ref' => $history->tx_ref]);
            return redirect()->route('app')->with('error', 'Ledger balancing allocation processing mismatch.');
        }
    }

    protected function credit_wallet(Wallet $wallet, float $amount, string $currencySign, Wallet $_wallet, float $fee)
    {
        if (!$_wallet) {
            throw new Exception('Source tracking instance node configuration undefined.');
        }

        $walletAsset      = $this->getOrCreateWalletAsset($wallet, $currencySign);
        $_walletAsset     = $this->getOrCreateWalletAsset($_wallet, $currencySign);
        $fee_wallet_asset = $this->getOrCreateWalletAsset(Wallet::find(1), 'FD');

        $walletAsset      = WalletAsset::where('id', $walletAsset->id)->lockForUpdate()->first();
        $_walletAsset     = WalletAsset::where('id', $_walletAsset->id)->lockForUpdate()->first();
        $fee_wallet_asset = WalletAsset::where('id', $fee_wallet_asset->id)->lockForUpdate()->first();

        if ($walletAsset->status->value !== WalletAssetStatus::ACTIVE->value || $_walletAsset->status->value !== WalletAssetStatus::ACTIVE->value) {
            throw new Exception('Wallet asset state configuration is inactive.');
        }

        if ($fee <= 0) {
            $asset = Asset::where('code', $currencySign)->first();
            if ($asset && $asset->network_fee !== null && $asset->network_fee > 0) {
                $fee    = $asset->network_fee;
                $amount = $amount - $fee;
            }
        } else {
            $amount = $amount - $fee;
        }

        $payload = [
            'amount'      => $amount,
            'tx_ref'      => generate_tx_ref(),
            'status'      => TransactionStatus::SUCCESS->value,
            'type'        => TransactionType::DEPOSIT->value,
            'description' => "Credit to wallet Address: {$wallet->address}",
        ];

        $_wallet->history()->create($payload);

        Transaction::create([
            'wallet_id'    => $wallet->id,
            '_wallet_id'   => $_wallet->id,
            'asset_id'     => $walletAsset->asset_id,
            'from_address' => $wallet->address,
            'to_address'   => $_wallet->address,
            'status'       => TransactionStatus::SUCCESS->value,
            'tx_ref'       => $payload['tx_ref'],
            'hash'         => Transaction::NewHash($payload),
            'description'  => "Credit to wallet ID: {$wallet->address}",
            'type'         => TransactionType::DEPOSIT->value,
            'amount'       => $amount,
            'network_fee'  => $fee,
            'prev_hash'    => Transaction::last_hash(),
        ]);

        $walletAsset->balance  += $amount;
        $walletAsset->save();

        $_walletAsset->balance -= ($amount + $fee);
        $_walletAsset->save();

        $fee_wallet_asset->locked_balance += $fee;
        $fee_wallet_asset->save();

        if ($fee > 0) {
            $fee_value = $fee / 1000000;
            $fee_asset = Asset::where('code', 'FD')->first();
            if ($fee_asset && function_exists('asset_value')) {
                $fee_asset->value += asset_value($walletAsset->asset, $fee_value);
                $fee_asset->save();
            }
        }
    }

    protected function getOrCreateWalletAsset(Wallet $wallet, string $currencySign): WalletAsset
    {
        $walletAsset = $wallet->assets->firstWhere(function ($asset) use ($currencySign) {
            return $asset->asset->code === $currencySign;
        });

        if (!$walletAsset) {
            $asset       = Asset::where('code', $currencySign)->firstOrFail();
            $walletAsset = $wallet->assets()->create([
                'asset_id'        => $asset->id,
                'balance'         => 0,
                'address'         => generate_wallet_address($wallet->user->id ?? 1),
                'locked_balance'  => 0,
                'pending_balance' => 0,
                'status'          => WalletAssetStatus::ACTIVE->value,
            ]);
        }

        return $walletAsset;
    }
}
