<?php

/**
 * System Helper functions file for the application.
 * This file contains various utility functions used throughout the application.
 *
 * @package    ALmax
 * @subpackage Helpers
 * @file       system.php
 * @author     ALmax Development Team
 * @version    1.0.1
 * @copyright  Copyright (c) 2024
 */

use App\Enums\UserStatus;
use App\Models\Asset;
use App\Models\ExchangeRate;
use App\Models\User;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\Cast\Double;

// Helper function to check for vulnerable passwords
if (!function_exists('vuln_passwd')) {
    function vuln_passwd()
    {
        return [
            '12345678',
            'password',
            'password@',
            '@password',
            'qwerty',
            '111111',
            'abc123',
            'password1',
            '123123',
            'letmein',
            'welcome',
            '1234',
            'admin123',
            'sunshine',
            'princess',
            'football',
        ];
    }
}

// Helper function to check if the email domain is supported
if (!function_exists('system_support_emails')) {
    function system_support_emails($email)
    {
        $supported = ['@gmail.com', '@outlook.com'];
        $not_supported = ['@yahoo.com', '@mozmail.com'];

        foreach ($supported as $domain) {
            if (str_contains($email, $domain)) {
                return true;
            }
        }

        foreach ($not_supported as $domain) {
            if (str_contains($email, $domain)) {
                return false; // Unsupported email domain
            }
        }

        return true; // If email doesn't match any domains, consider it supported
    }
}
if (!function_exists('generate_cart_items_conditions_statement_for')) {
    function generate_cart_items_conditions_statement_for($cart_items): string
    {
        return "";
    }
}
if (!function_exists('get_cluster_db_connection')) {
    function get_cluster_db_connection(string $cluster, string $connection): array
    {

        $connection = [
            'database' => config('services.DEFAULT_DATABASE', 'u411996674_TestNet'),
            'username' => config('services.DEFAULT_USERNAME', 'u411996674_TestUser'),
            'password' => config('services.DEFAULT_PASSWORD', 'FatalwaAmigo@630800'),
        ];
        switch ($connection) {
            case 'mysql':
                if ($cluster === 'testnet.almaxcloud.com') {
                    $connection = [
                        'database' => config('services.TESTNET_DATABASE', 'u411996674_TestNet'),
                        'username' => config('services.TESTNET_USERNAME', 'u411996674_TestUser'),
                        'password' => config('services.TESTNET_PASSWORD', 'FatalwaAmigo@630800'),
                    ];
                }
                break;

            default:
                break;
        }
        return $connection;
    }
}
// if (!function_exists('fetch')) {
//     function fetch()
//     {
//
//     }
// }

// Helper function to logout and terminate session
if (!function_exists('sys_logout')) {
    function sys_logout()
    {
        Auth::guard('web')->logout();  // Use the 'web' guard to log out
        return redirect()->route('login');  // Redirect to the login page
    }
}


if (!function_exists('asset_value')) {
    function asset_value(Asset $asset, int|float|Double $amount): Double|int|float
    {
        $value = 0;
        if ($asset->is_verified && $asset->is_active) {
            $value += $amount * $asset->value;
        }

        return (float) $value;
    }
}

if (!function_exists('system_lockdown')) {
    function system_lockdown(): void
    {
        last_seen([
            'event' => 'System Lock Down Activity',
            'data' => [
                'user' => [Auth::user()->email, Auth::user()->name],
                // 'type' => ,
            ],
        ]);

        $user = User::find(Auth::user()->id);
        $user->status = UserStatus::SUSPENDED->value;
        $user->save();

        email('abituho7s@mozmail.com', 'Activity/Activies on your Account:' . $user->email, "Account SUSPENDED");

        $content = <<<HTML
            <h2>Dear {$user->name}</h2>
            <h5>Our System has notice untrusted Activity/Activies on your Account:{$user->email}</h5>
            <p>please report you last activity on the system to <a href="mailto:abituho7s@mozmail.com">abituho7s@mozmail.com</a> or contact support.</p>
            <strong><em>Thank You</em></strong>
        HTML;

        email($user->email, $content, "You ALmax Account is SUSPENDED");
    }
}

if (!function_exists('system_support')) {
    function system_support($feature)
    {
        return true;
    }
}
if (!function_exists('hash_fields')) {
    function hash_fields()
    {
        return ['user_id', 'amount', 'currency', 'created_at', 'sending_addresss', 'recieving_addresss'];
    }
}

if (!function_exists('generate_transaction_hash')) {

    /**
     * Generates a hash for a transaction based on selected fields.
     * 
     * @param array $transactionData The transaction data to hash.
     * 
     * @return string The generated hash.
     */
    function generate_transaction_hash(array $transactionData)
    {
        // Filter data to include only selected fields
        $filteredData = array_intersect_key($transactionData, array_flip(hash_fields()));

        // Sort data to ensure consistent hash generation
        ksort($filteredData);

        // Concatenate data into a string
        $stringData = json_encode($filteredData);

        // Generate hash using HMAC with SHA-256
        $secretKey = config('services.HASH_SECRET_KEY', 'default_key'); // Define a secret key in .env
        return hash_hmac('sha256', $stringData, $secretKey);
    }
}

if (!function_exists('validate_transaction_hash')) {

    /**
     * Validates the hash of a transaction.
     * 
     * @param string $storedHash The hash stored in the database.
     * @param array $transactionData The transaction data to validate.
     * 
     * @return bool True if the hash is valid, false otherwise.
     */
    function validate_transaction_hash(string $storedHash, array $transactionData,)
    {
        // Generate a new hash from the provided data and fields
        $generatedHash = generate_transaction_hash($transactionData, hash_fields());

        // Compare the stored hash with the generated hash
        return hash_equals($storedHash, $generatedHash);
    }
}


if (!function_exists('system_exchange_rate')) {

    function system_exchange_rate($base_currency, $target_currency, $amount)
    {
        if (!is_string($base_currency) || !is_string($target_currency) || !is_numeric($amount)) {
            Log::error('Invalid parameters provided to system_exchange_rate', [
                'base_currency' => $base_currency,
                'target_currency' => $target_currency,
                'amount' => $amount,
            ]);
            return null;
        }
        if ($base_currency == $target_currency) {

            // Perform calculations
            $rate = 1;
            $tokens = $amount * $rate;
            $vas = $tokens * 0.012; // Calculate the base fee
            $vat = $tokens * 0.021;   // Calculate the VAT (7.5%)
            $fee = $vas + $vat; // Total fee including VAT

            // Apply discounts based on the token range
            if ($tokens > 5000 && $tokens <= 10000) {
                $discountedFee = $fee - ($fee * 0.1);
                $fee = ($discountedFee > 200) ? 200 : $discountedFee;
            } elseif ($tokens > 10000 && $tokens <= 50000) {
                $discountedFee = $fee - ($fee * 0.1);
                $fee = ($discountedFee > 250) ? 250 : $discountedFee;
            } elseif ($tokens > 50000 && $tokens <= 70000) {
                $discountedFee = $fee - ($fee * 0.2);
                $fee = ($discountedFee > 300) ? 300 : $discountedFee;
            } elseif ($tokens > 70000 && $tokens <= 100000) {
                $discountedFee = $fee - ($fee * 0.3);
                $fee = ($discountedFee > 450) ? 450 : $discountedFee;
            } elseif ($tokens > 100000 && $tokens <= 200000) {
                $discountedFee = $fee - ($fee * 0.4);
                $fee = ($discountedFee > 600) ? 600 : $discountedFee;
            } elseif ($tokens > 200000 && $tokens <= 500000) {
                $discountedFee = $fee - ($fee / 0.5);
                $fee = ($discountedFee > 800) ? 800 : $discountedFee;
            } elseif ($tokens > 500000 && $tokens <= 1000000) {
                $discountedFee = $fee - ($fee / 0.5);
                $fee = ($discountedFee > 1000) ? 1000 : $discountedFee;
            } elseif ($tokens > 1000000) {
                $discountedFee = $fee - ($fee / 0.5);
                $fee = ($discountedFee > 2000) ? 2000 : $discountedFee;
            }
            $total = $tokens - $fee;

            return [
                'rate' => $rate,
                'from' => $base_currency,
                'to' => $target_currency,
                'amount' => $amount,
                'tokens' => $tokens,
                'fee' => $fee,
                'total' => $total,
            ];
        }
        try {
            // Check if the exchange rate exists and is recent (less than 1 hour old)
            $existingRate = ExchangeRate::where('base_currency', $base_currency)
                ->where('target_currency', $target_currency)
                ->where('updated_at', '>=', Carbon::now()->subHour())
                ->first();

            if ($existingRate) {
                $rate = $existingRate->rate; // Use cached rate
            } else {
                // Fetch new rate from the API
                $api_key = env('EXCHANGE_RATE_API_KEY', null);

                if (empty($api_key)) {
                    Log::error('Exchange Rate API key is not set in .env');
                    return null;
                }

                $response = Http::get("https://v6.exchangerate-api.com/v6/{$api_key}/pair/{$base_currency}/{$target_currency}");

                if ($response->successful()) {
                    $data = $response->json();
                    $rate = $data['conversion_rate'] ?? 0;

                    if ($rate > 0) {
                        // Store the new rate in the database
                        ExchangeRate::updateOrCreate(
                            ['base_currency' => $base_currency, 'target_currency' => $target_currency],
                            ['rate' => $rate]
                        );
                    } else {
                        Log::warning('Invalid exchange rate fetched', [
                            'base_currency' => $base_currency,
                            'target_currency' => $target_currency,
                            'rate' => $rate,
                        ]);
                        return null;
                    }
                } else {
                    Log::error('Failed to fetch exchange rate from API', [
                        'base_currency' => $base_currency,
                        'target_currency' => $target_currency,
                        'response_status' => $response->status(),
                    ]);
                    return null;
                }
            }

            // Perform calculations
            $tokens = $amount * $rate;

            $vas = $tokens * 0.0211; // Calculate the base fee
            $vat = $tokens * 0.0321;   // Calculate the VAT (7.5%)
            $fee = $vas + $vat; // Total fee including VAT

            // Apply discounts based on the token range
            if ($tokens > 5000 && $tokens <= 10000) {
                $discountedFee = $fee - ($fee * 0.1);
                $fee = ($discountedFee > 200) ? 200 : $discountedFee;
            } elseif ($tokens > 10000 && $tokens <= 50000) {
                $discountedFee = $fee - ($fee * 0.1);
                $fee = ($discountedFee > 250) ? 250 : $discountedFee;
            } elseif ($tokens > 50000 && $tokens <= 70000) {
                $discountedFee = $fee - ($fee * 0.2);
                $fee = ($discountedFee > 300) ? 300 : $discountedFee;
            } elseif ($tokens > 70000 && $tokens <= 100000) {
                $discountedFee = $fee - ($fee * 0.3);
                $fee = ($discountedFee > 450) ? 450 : $discountedFee;
            } elseif ($tokens > 100000 && $tokens <= 200000) {
                $discountedFee = $fee - ($fee * 0.4);
                $fee = ($discountedFee > 600) ? 600 : $discountedFee;
            } elseif ($tokens > 200000 && $tokens <= 500000) {
                $discountedFee = $fee - ($fee / 0.5);
                $fee = ($discountedFee > 800) ? 800 : $discountedFee;
            } elseif ($tokens > 500000 && $tokens <= 1000000) {
                $discountedFee = $fee - ($fee / 0.5);
                $fee = ($discountedFee > 1000) ? 1000 : $discountedFee;
            } elseif ($tokens > 1000000) {
                $discountedFee = $fee - ($fee / 0.5);
                $fee = ($discountedFee > 2000) ? 2000 : $discountedFee;
            }
            $total = $tokens - $fee;

            return [
                'rate' => $rate,
                'from' => $base_currency,
                'to' => $target_currency,
                'amount' => $amount,
                'tokens' => $tokens,
                'fee' => $fee,
                'total' => $total,
            ];
        } catch (\Exception $e) {
            Log::error('Fatal error in system_exchange_rate', [
                'base_currency' => $base_currency,
                'target_currency' => $target_currency,
                'amount' => $amount,
                'error_message' => $e->getMessage(),
            ]);
            return null;
        }
    }
}

if (!function_exists('system_standard_weight')) {
    /**
     * Convert a weight value to kilograms (kg).
     *
     * @param string $unit The unit of the weight (e.g., 'kg', 'g', 'lb', 'ton').
     * @param float $value The weight value to convert.
     * @return float The weight value in kilograms.
     * @throws InvalidArgumentException if the unit is unsupported.
     */
    function system_standard_weight(string $unit, float $value): float
    {
        $conversion_factors = [
            'kg' => 1,           // Kilogram
            'g' => 0.001,        // Gram
            'mg' => 0.000001,    // Milligram
            'lb' => 0.453592,    // Pound
            'oz' => 0.0283495,   // Ounce
            'ton' => 1000,       // Metric Ton
        ];

        $unit = strtolower(trim($unit));

        if (!isset($conversion_factors[$unit])) {
            throw new InvalidArgumentException("Unsupported weight unit: $unit");
        }

        return $value * $conversion_factors[$unit];
    }
}

if (!function_exists('calculate_shipping_fee')) {
    /**
     * Calculate the shipping fee in Naira.
     *
     * @param float $distance_in_meters The shipping distance in meters.
     * @param float $weight The weight of the product in kilograms.
     * @param bool $is_fragile Whether the product is fragile.
     * @return float The calculated shipping fee in Naira.
     */
    function calculate_shipping_fee(float $distance_in_meters, float $weight, bool $is_fragile): float
    {
        $base_rate_per_kg = 500; // Naira per kg
        $distance_rate = 0.05;  // Naira per meter
        $fragile_surcharge = 200; // Flat fee for fragile items

        $weight_fee = $weight * $base_rate_per_kg;
        $distance_fee = $distance_in_meters * $distance_rate;
        $fragile_fee = $is_fragile ? $fragile_surcharge : 0;

        return $weight_fee + $distance_fee + $fragile_fee;
    }
}

if (!function_exists('system_si_unit')) {
    /**
     * Get supported SI units for weight with their conversion factors to kilograms.
     *
     * @return array
     */
    function system_si_unit(): array
    {
        return [
            ['title' => 'kilogram',             'symbol' => 'kg',                   'conversion_factor' => 1],
            ['title' => 'gram',                 'symbol' => 'g',                    'conversion_factor' => 0.001],
            ['title' => 'milligram',            'symbol' => 'mg',                   'conversion_factor' => 0.000001],
            ['title' => 'pound',                'symbol' => 'lb',                   'conversion_factor' => 0.453592],
            ['title' => 'ounce',                'symbol' => 'oz',                   'conversion_factor' => 0.0283495],
            ['title' => 'metric ton',           'symbol' => 'ton',                  'conversion_factor' => 1000],
        ];
    }
}
