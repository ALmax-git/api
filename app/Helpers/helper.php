<?php

/**
 * Helper functions file for the application.
 * This file contains various utility functions used throughout the application.
 *
 * @package    ALmax
 * @subpackage Helpers
 * @file       helper.php
 * @author     ALmax Development Team
 * @version    1.0.1
 * @copyright  Copyright (c) 2024
 */

use App\Models\Client;
use App\Models\PosCart;
use App\Models\Wallet;
use Flutterwave\Controller\PaymentController;
use Flutterwave\EventHandlers\ModalEventHandler as PaymentHandler;
use Flutterwave\Flutterwave;
use Flutterwave\Library\Modal;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\IpUtils;


if (!function_exists('flutterwave_payment')) {
    /**
     * Helper function to initialize and handle a Flutterwave payment.
     *
     * @return PaymentController Payment controller.
     */
    function flutterwave_payment(): PaymentController
    {
        // Initialize Flutterwave SDK
        Flutterwave::bootstrap();

        $customHandler          = new PaymentHandler();
        $client                 = new Flutterwave();
        $modalType              = Modal::POPUP;
        $controller             = new PaymentController($client, $customHandler, $modalType);

        return $controller;
    }
}
if (!function_exists('get_the_right_db')) {
    function get_the_right_db(string $name)
    {
        if ($name === 'sqlite' && Auth::class) {

            try {
                //code...
                if (url('/') == null) {
                    return database_path('databaset.sqlite');
                } elseif (url('/') == 'http//127.0.0.1') {
                    return database_path('database_test.sqlite');
                } elseif (url('/') == 'http://localhost') {
                    return database_path('database_test_0.sqlite');
                } elseif (url('/') == 'http//127.0.0.2') {
                    return database_path('database_test_4.sqlite');
                } else {
                    return  database_path('cli_db.sqlite');
                } // env('DB_DATABASE', database_path('database.sqlite'))

            } catch (\Throwable $th) {
                return  database_path('cli_db.sqlite');
                //throw $th;
            }
        } else {
            return  database_path('cli_db.sqlite');
        }
    }
}


/**
 * Example label resolver (customize for your models)
 */
function resolve_label(string $type, string $hex): ?string
{
    try {
        switch ($type) {
            case 'product':
                $p                                  = \App\Models\Product::where('id', get_id($hex, $type))->select('id', 'name')->first();
                return $p?->name;
                break;
            case 'user':
                $u                                  = \App\Models\User::where('id', get_id($hex, $type))->select('id', 'name')->first();
                return $u?->name;
                break;
            case 'blog':
                $b                                  = \App\Models\Blog::where('id', get_id($hex, $type))->select('id', 'title')->first();
                return $b?->title;
                break;
            case 'client':
                $c                                  = Client::where('id', get_id($hex, $type))->select('id', 'name')->first();
                return $c?->name;
                break;
            case 'ticket':
                $b                                  = \App\Models\Ticket::where('id', get_id($hex, $type))->select('id', 'title')->first();
                return $b?->qr_code;
                break;
            case 'journal_entry':
                $b                                  = \App\Models\JournalEntry::where('id', get_id($hex, $type))->select('id', 'label')->first();
                return  '“' . Str::limit($b?->label, 15) . '”';
                break;
            case 'stock':
                $s                                  = \App\Models\Stock::where('id', get_id($hex, $type))->select('id', 'title', 'invoice_number', 'label')->first();
                return  '“' . $s->invoice_number . Str::limit($s?->title ?? $s?->label, 15) . '”';
                break;
            case 'stock_entry':

                $s = \App\Models\StockEntry::with('product:id,name')
                    ->where('id', get_id($hex, $type))
                    // ->select('id', 'product_id', 'note')
                    ->first();

                return '“Stock Entry: ' .
                    Str::limit(
                        ($s?->product?->name ?? 'Unknown Product') .
                            ($s?->note ? (' (' . $s->note . ')') : ''),
                        15
                    ) .
                    '”';

                break;

            case 'file_share':
                $f = \App\Models\FileShare::where('id', get_id($hex, $type))->select('id', 'name')->first();
                return '“' . Str::limit($f?->item->name, 15) . '”';
                break;
            case 'project':
                $p                                  = \App\Models\Project::where('id', get_id($hex, $type))->select('id', 'title')->first();
                return $p?->title;
                break;
            case 'product_variant':
                $product_variant                    = \App\Models\ProductVariant::where('id', get_id($hex, $type))->select('id', 'label')->first();
                return $product_variant->label . '*';
                break;
            case 'task':
                $t                                  = \App\Models\Todo::where('id', get_id($hex, $type))->select('id', 'title')->first();
                return $t?->title;
                break;
            case 'pos_cart':
                $p = PosCart::where('id', get_id($hex, $type))->select('id', 'items', 'customer_name')->with('user')->first();
                return _app('purchase of ') . (count($p->items)) . ' by ' .  $p->customer_name ?? 'wall-in customer' . ' ' . _app('from') . ' ' . $p->user->name;
                break;
            default:
                return null;
                break;
        }
    } catch (\Exception $e) {
        Log::error('@helper.php resolve_label(' . $type . ' ' . $hex . ')', [$e->getMessage()]);
        return null;
    }
}

/**
 * Single purifier instance for ALmax
 *
 * Keeps interactive attributes like wire:click, onclick, and data-*
 * @return HTMLPurifier
 */
function purifier(): HTMLPurifier
{
    static $purifier = null;

    if ($purifier === null) {
        $config                             = HTMLPurifier_Config::createDefault();

        // Allowed elements (inline + block you use in blogs)
        $config->set('HTML.AllowedElements', [
            'p',
            'a',
            'ul',
            'ol',
            'li',
            'strong',
            'em',
            'blockquote',
            'code',
            'pre',
            'img',
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
            'span',
            'table',
            'tr',
            'td',
            'tbody',
            'thead',
            'hr',
            'div',
        ]);

        // Custom definition for ALmax
        $config->set('HTML.DefinitionID', 'almax-custom');
        $config->set('HTML.DefinitionRev', 1);

        if ($def = $config->maybeGetRawHTMLDefinition()) {
            // Allow global attributes we need
            $def->info_global_attr['data-*']        = new \HTMLPurifier_AttrDef_Text;
            $def->info_global_attr['wire:click']    = new \HTMLPurifier_AttrDef_Text;
            $def->info_global_attr['onclick']       = new \HTMLPurifier_AttrDef_Text;
            $def->info_global_attr['class']       = new \HTMLPurifier_AttrDef_Text;
        }

        // Build purifier once
        $purifier = new \HTMLPurifier($config);
    }

    return $purifier;
}

/**
 * Content parser that recognizes and converts special tokens into clickable spans:
 * - Products:          {#hex}(label)               ->  <span class="almax-token almax-product">label</span>
 * - Users:             {@hex}(label)               ->  <span class="almax-token almax-user">label</span>
 * - Blogs:             {&hex}(label)               ->  <span class="almax-token almax-blog">label</span>
 * - Clients:           {~hex}(label)               ->  <span class="almax-token almax-client">label</span>
 * - Tickets:           {%hex}(label)               ->  <span class="almax-token almax-ticket">label</span>
 * - Journal entries:   {!hex}(label)               ->  <span class="almax-token almax-journal_entry">label</span>
 * - Stocks:            {;hex}(label)               ->  <span class="almax-token almax-stock">label</span>
 * - Stock entries:     {:hex}(label)               ->  <span class="almax-token almax-stock_entry">label</span>
 * - File shares:       {=hex}(label)               ->  <span class="almax-token almax-file_share">label</span>
 * - Projects:          {$hex}(label)               ->  <span class="almax-token almax-project">label</span>
 * - Tasks:             {^hex}(label)               ->  <span class="almax-token almax-task">label</span>
 * The hex part is used to resolve the actual label from the database (with caching).
 * Also processes Markdown and sanitizes HT ML output.
 * 
 * @param string $content The content to parse 
 * @param array $options Parser options:
 *   - resolve: bool - Whether to resolve token labels (default: true)
 *   - cache_ttl: int - Cache TTL in seconds for resolved labels (default: 300)
 * @return string The parsed HTML content
 */
function parser(string $content, array $options = []): string
{
    $options = array_merge([
        'inline' => false, // If true, only parse inline tokens without Markdown processing
        'resolve' => true,
        'cache_ttl' => 300,
    ], $options);

    // Regex patterns
    $patterns = [
        'blog'              => '/\{&([A-Fa-f0-9]{4,8})\}(?:\(([^)]+)\))?/u',
        'client'            => '/\{~([A-Fa-f0-9]{4,8})\}(?:\(([^)]+)\))?/u',
        'product'           => '/\{#([A-Fa-f0-9]{4,8})\}(?:\(([^)]+)\))?/u',
        'product_variant'   => '/\{\*([A-Fa-f0-9]{4,8})\}(?:\(([^)]+)\))?/u',
        'user'              => '/\{@([A-Fa-f0-9]{4,8})\}(?:\(([^)]+)\))?/u',
        'pos_cart'          => '/\{R([A-Fa-f0-9]{4,8})\}(?:\(([^)]+)\))?/u',
        'ticket'            => '/\{%([A-Fa-f0-9]{4,8})\}(?:\(([^)]+)\))?/u',
        'journal_entry'     => '/\{!([A-Fa-f0-9]{4,8})\}(?:\(([^)]+)\))?/u',
        'stock'             => '/\{;([A-Fa-f0-9]{4,8})\}(?:\(([^)]+)\))?/u',
        'stock_entry'       => '/\{:([A-Fa-f0-9]{4,8})\}(?:\(([^)]+)\))?/u',
        'file_share'        => '/\{=([A-Fa-f0-9]{4,8})\}(?:\(([^)]+)\))?/u',
        'project'           => '/\{\$([A-Fa-f0-9]{4,8})\}(?:\(([^)]+)\))?/u',
        'task'              => '/\{\^([A-Fa-f0-9]{4,8})\}(?:\(([^)]+)\))?/u',
    ];

    $fetchLabel = function (string $type, string $hex) use ($options) {
        $cacheKey = "almax:resolve:{$type}:{$hex}";
        if ($options['cache_ttl'] > 0) {
            return cache()->remember($cacheKey, $options['cache_ttl'], fn() => resolve_label($type, $hex));
        }
        return resolve_label($type, $hex);
    };

    $callback = function ($type) use ($fetchLabel) {
        return function ($matches) use ($type, $fetchLabel) {
            $hex = $matches[1];
            $alt = $matches[2] ?? null;
            $label = $fetchLabel($type, $hex) ?? $alt ?? "{$type[0]}{$hex}";
            return "<span class=\"almax-token almax-{$type} bg-neutral-800 text-xs mx-2 px-3 rounded-lg text-white hover:bg-neutral-700 cursor-pointer\" "
                . "data-type=\"{$type}\" data-id=\"" . e($hex) . "\" "
                . "title=\"" . e($label) . "\" "
                . "onclick=\"window.dispatchEvent(new CustomEvent('almax-model', { detail: { type: '{$type}', id: '{$hex}' } }));\">"
                . e($label) . "</span>";
        };
    };

    // 1) Replace tokens BEFORE Markdown
    foreach ($patterns as $type => $regex) {
        $content = preg_replace_callback($regex, $callback($type), $content);
    }

    // 2) Markdown conversion
    static $converter = null;
    if ($converter === null) {
        $converter = new \League\CommonMark\CommonMarkConverter([]);
    }
    $html = $converter->convert($content)->getContent();

    // 3) Sanitize
    $html = purifier()->purify($html);

    return $options['inline'] ? purifier()->purify($content) : $html;
}

if (!function_exists('_get_host')) {
    /**
     * Resolve the host/domain name from a given URL.
     *
     * @param string|null $url The URL to extract the domain from|the current host.
     * use \App\Models\Domain where name == host 
     * find the host \App\Models\Client
     * @return array|null clinet [name, uuid, domain] | default [env.app_name, 1, env.app_url]
     */
    function _get_host(?string $url = null): ?array
    {
        // Parse the URL to get the host
        // Remove 'www.' prefix if present 
        // Validate the domain format (basic validation)
        // $host = parse_url($url ?? request()->getHost(), PHP_URL_HOST);

        $host = request()->getHost(); // parse_url(request()->getHost(), PHP_URL_HOST);
        if (!$host) {
            return [
                'name'                      => env('APP_NAME', 'ALmax'),
                'uuid'                      => make_uuid(1, 'client'),
                'domain'                    => env('APP_URL', 'https://almaxcloud.com'),
                'allow_node'                => env('ALLOW_NODE', false),
                'allow_guest'               => env('ALLOW_GUEST', true),
                'save_as_gateway'           => env('SAVE_AS_GATEWAY', false),
                'tagline'                   => Client::find(1)->tagline ?? env('TAGLINE', ''),
                'overview'                  => Client::find(1)->overview ?? env('OVERVIEW', ''),
                'mission'                   => Client::find(1)->mission ?? env('MISSION', ''),
                'vision'                    => Client::find(1)->vision ?? env('VISION', ''),
                'description'               => Client::find(1)->description ?? env('DESCRIPTION', ''),
                'telephone'                 => Client::find(1)->telephone ?? env('TELEPHONE', '+234 81 6663 6567'),
                'email'                     => Client::find(1)->email ?? env('EMAIL', 'abituho7s@mozmail.com'),
                'welcome_page'              => 'guest',
                'ANDROID_SHA256'            => Client::find(1)->ANDROID_SHA256 ?? config('services.COREX_SHA256')
            ];
        }

        $host = preg_replace('/^www\./', '', $host);
        // if (!preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $host)) {
        //     return [$host, request()->getHost(),  preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $host)];
        //     return [
        //         'name'                      => env('APP_NAME'),
        //         'uuid'                      => make_uuid(1, 'client'),
        //         'domain'                    => env('APP_URL'),
        //         'allow_node'                => env('ALLOW_NODE', false),
        //         'save_as_gateway'           => env('SAVE_AS_GATEWAY', false),
        //         'tagline'                   => Client::find(1)->tagline ?? env('TAGLINE', ''),
        //         'overview'                  => Client::find(1)->overview ?? env('OVERVIEW', ''),
        //         'mission'                   => Client::find(1)->mission ?? env('MISSION', ''),
        //         'vision'                    => Client::find(1)->vision ?? env('VISION', ''),
        //         'description'               => Client::find(1)->description ?? env('DESCRIPTION', ''),
        //         'telephone'                 => Client::find(1)->telephone ?? env('TELEPHONE', '+234 81 6663 6567'),
        //         'email'                     => Client::find(1)->email ?? env('EMAIL', 'abituho7s@mozmail.com'),
        //         'welcome_page'              => 'guest',
        //     ];
        // }

        // Attempt to find the domain in the database
        $domain = \App\Models\Domain::where('name', $host)->first();
        if ($domain && $domain?->client) {
            // Domain found, return client information
            return [
                'name'                      => $domain->client->name,
                'uuid'                      => make_uuid($domain->client->id, 'client'),
                'domain'                    => $domain->name,
                'allow_node'                => $domain->allow_almax_nodes ?? false,
                'allow_guest'               => $domain->allow_guest ?? true,
                'save_as_gateway'           => $domain->client->save_as_gateway ?? false,
                'tagline'                   => $domain->client->tagline,
                'overview'                  => $domain->client->overview,
                'mission'                   => $domain->client->mission,
                'vision'                    => $domain->client->vision,
                'description'               => $domain->client->description,
                'telephone'                 => $domain->client->telephone,
                'email'                     => $domain->client->email,
                'welcome_page'              => $domain->welcome_page,
                'ANDROID_SHA256'            => $domain->client->ANDROID_SHA256 ?? config('services.COREX_SHA256')
            ];
        }

        // Domain not found, return default values
        return [
            'name'                          => env('APP_NAME'),
            'uuid'                          => make_uuid(1, 'client'),
            'domain'                        => env('APP_URL'),
            'allow_node'                    => env('ALLOW_NODE', false),
            'allow_guest'                   => env('ALLOW_GUEST', true),
            'save_as_gateway'               => env('SAVE_AS_GATEWAY', false),
            'tagline'                       => Client::find(1)->tagline ?? env('TAGLINE', ''),
            'overview'                      => Client::find(1)->overview ?? env('OVERVIEW', ''),
            'mission'                       => Client::find(1)->mission ?? env('MISSION', ''),
            'vision'                        => Client::find(1)->vision ?? env('VISION', ''),
            'description'                   => Client::find(1)->description ?? env('DESCRIPTION', ''),
            'telephone'                     => Client::find(1)->telephone ?? env('TELEPHONE', '+234 81 6663 6567'),
            'email'                         => Client::find(1)->email ?? env('EMAIL', 'abituho7s@mozmail.com'),
            'welcome_page'                  => 'guest',
            'ANDROID_SHA256'                => Client::find(1)->ANDROID_SHA256 ?? config('services.COREX_SHA256')
        ];
    }
}

// Retrieves the list of permissions available to the authenticated user based on their roles.
if (!function_exists('user_access')) {
    /**
     * Get all permission labels available to the authenticated user via their roles.
     *
     * @return array List of permission labels.
     */
    function user_access()
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }
        $permissions                        = [];
        foreach ($user->roles as $role) {
            $role_permissions               = $role->permissions;
            foreach ($role_permissions as $permission) {
                $permissions[]              = $permission->label;
            }
        }
        return $permissions;
    }
}

// Retrieves the list of features (permissions) available to the authenticated user's client.
if (!function_exists('client_access')) {
    /**
     * Get all feature labels available to the authenticated user's client.
     *
     * @return array List of feature labels.
     */
    function client_access(): array
    {
        $client = Auth::user()->client;
        if (!$client) {
            return [];
        }
        $client_features = ['x-hub', 'edge', 'explorer', 'store', 'x_guard'];
        foreach ($client->plans as $plan) {
            $features = $plan->features;
            foreach ($features as $feature) {
                $client_features[] = $feature->label;
            }
        }
        return $client_features;
    }
}

// Checks if a given access/permission exists in the provided permissions array.
if (!function_exists('check_if')) {
    /**
     * Check if a specific access/permission exists in the permissions array.
     *
     * @param array $permissions List of permissions or features.
     * @param string $access The permission or feature label to check.
     * @return bool True if found, false otherwise.
     */
    function check_if(array $permissions, string $access): bool
    {
        if ($access == 'global_access') {
            return true;
        }
        foreach ($permissions as $permission) {
            if ($permission == $access) {
                return true;
            }
        }
        return false;
    }
}

// Checks if the authenticated user has access to a specific permission or feature.
if (!function_exists('can_access')) {
    /**
     * Determine if the authenticated user has access to a specific permission.
     *
     * @param string $access The permission or feature label to check.
     * @return bool True if the user has access, false otherwise.
     */
    function can_access(string $access): bool
    {
        // Allow if the user is the owner of the client (super admin)
        if (Auth::user()->id === 1  || Auth::user()->is_admin()) {
            return true;
        }
        if ($access == 'systemd') {
            if (!(Auth::user()->id === 1)) {
                return false;
            }
        }
        // Otherwise, check if the user has the specified access
        return check_if(user_access(), $access);
    }
}

// Checks if the authenticated client has access to a specific feature or permission.
if (!function_exists('can_use')) {
    /**
     * Determine if the authenticated client has access to a specific feature.
     *
     * @param string $access The feature or permission label to check.
     * @return bool True if the client has access, false otherwise.
     */
    function can_use(string $access): bool
    {
        // Special bypass for a specific admin user (ID 1 and specific email)
        if (Auth::check()) {
            if (Auth::user()->id == 1) {
                return true;
            }
        }
        if (!Auth::check()) {
            return false;
        }
        // Check if the client has the specified access
        return check_if(client_access(), $access);
    }
}

if (!function_exists('email')) {
    /**
     * Send a quick email to one or many recipients.
     *
     * @param array|string $emails
     * @param string $content HTML or plain content
     * @param string|null $subject
     * @return void
     */
    function email($emails, $content, $subject = 'ALmax Notification')
    {
        $emails = Arr::wrap($emails);

        Mail::send([], [], function ($message) use ($emails, $content, $subject) {
            $message->to($emails)->subject($subject);

            $html = <<<HTML
                        <div style="width: 90%; margin: auto; font-family: Arial, sans-serif; background-color: #001; color: #e6e6ef; padding: 20px; border-radius: 10px; border: 1px solid #0d1117;">
                            <div style="display: flex; gap: 8px; margin-bottom: 15px;">
                                <span style="width: 12px; height: 12px; border-radius: 50%; background-color: #ff5f57;"></span>
                                <span style="width: 12px; height: 12px; border-radius: 50%; background-color: #ffbd2e;"></span>
                                <span style="width: 12px; height: 12px; border-radius: 50%; background-color: #28c941;"></span>
                                <span style="font-size: 16px; color: rgb(255, 255, 255); font-weight: 900; right: 0; border-block: 4px double white; margin-left: auto; text-align:right;"> <a style="color:white !important;" href="almaxcloud.com">ALmax Cloud</a> 📜 </span>
                            </div>
                            <h2 style="margin: 0 0 10px;">{$subject}</h2>
                            <hr>
                            <div style="font-size: 14px; color: rgb(255, 255, 255); background-color: #011; padding: 5px; border-radius: 10px;">{$content}</div>
                        </div>
                    HTML;

            $message->html($html, 'text/html');
        });
    }
}

if (!function_exists('_app')) {
    /**
     * Retrieve a translation for a given key based on the user's language preference.
     *
     * @param string|null $key The translation key to look up.
     * @return string The translated text or the formatted key if not found.
     */
    function _app(string|null $key): string
    {
        if ($key === null || $key === '') {
            return '';
        }
        // Normalize the key to lowercase for case-insensitive lookup
        $normalizedKey = strtolower($key);

        // Get the user's language (default to 'English' if none is set)
        $language = Auth::user()->language ?? 'English';

        // Cache key based on language
        $cacheKey = 'translations_' . $language;

        // Check if translations are cached
        $translations = cache()->get($cacheKey);

        // If not cached, load the language file and cache it
        if (!$translations) {
            // Define the path to language files
            $languageFilePath = app_path("Helpers/Languages/{$language}.php");

            // Load the language file based on the user's language preference
            if (file_exists($languageFilePath)) {
                $translations = require $languageFilePath;
            }

            // Cache the translations for 24 hours
            cache()->put($cacheKey, $translations, 24 * 60); // 24 hours
        }

        // Check if the normalized key exists in the translations (case-insensitive)
        if (array_key_exists($normalizedKey, $translations)) {
            return   $translations[$normalizedKey];
        }

        // Fall back to English if the translation is missing in the current language
        $translations = cache()->get('translations_English');
        if (!$translations) {
            $translations = require app_path('Helpers/Languages/English.php');
            cache()->put('translations_English', $translations, 24 * 60); // Cache English translations
        }

        // Return the translation for the key in English (fall back)
        return ($translations[$normalizedKey]  ?? formatKey($key)); // Format the key if not found in English
    }

    /**
     * Format a key like 'axx_byy_c' to 'Axx Byy C'
     */
    function formatKey($key)
    {
        // Convert snake_case to Title Case (e.g., axx_byy_c -> Axx Byy C)
        $formattedKey = ucwords(str_replace('_', ' ', $key));

        return $formattedKey;
    }
}


if (!function_exists('generate_wallet_address')) {
    /**
     * Generate a unique 20-character wallet address that starts with "0x" and includes the user ID.
     *
     * @param int $number
     * @return string
     */
    function generate_wallet_address(string|int $number): string
    {
        // Convert the number to a string
        $n_string = (string) make_uuid($number);

        // Calculate the remaining length after "0x" and user ID
        $remaining_length = 32 - strlen($n_string); // 2 for "0x"

        // Generate a random string to fill the remaining length
        $r_string = \Illuminate\Support\Str::random($remaining_length);

        // Construct the wallet address
        $address = '0xFF' . $n_string . $r_string;

        //Avoid Duplicate
        if (Wallet::where('address', $address)->exists()) {
            return generate_wallet_address($number);
        }
        return $address;
    }
}

if (!function_exists('validate_wallet_address')) {
    /**
     * Validate $address if it exists in \App\Models\Wallet or WalletAsset.
     * @param string $address
     * @return array [
     *      'status'            => bool,
     *      'wallet_id'         => nullable,
     *      'wallet_asset_id'   => nullable,
     *      'message'           => string
     *      'model'             => Wallet|WalletAsset|null
     * ]
     */
    function validate_wallet_address(string $address): array
    {
        $wallet = \App\Models\Wallet::where('address', $address)->first();
        if ($wallet) {
            return [
                'status'                    => true,
                'wallet_id'                 => $wallet->id,
                'wallet_asset_id'           => $wallet->asset_id ?? null,
                'message'                   => 'Wallet address found in Wallet.',
                'model'                     => 'wallet',
            ];
        }

        $wallet_asset = null;
        if (class_exists('\App\Models\WalletAsset')) {
            $wallet_asset = \App\Models\WalletAsset::where('address', $address)->first();
        }

        if ($wallet_asset) {
            return [
                'status'                    => true,
                'wallet_id'                 => $wallet_asset->id ?? null,
                'wallet_asset_id'           => $wallet_asset->asset_id ?? null,
                'message'                   => 'Wallet address found in Wallet Asset.',
                'model'                     => 'wallet_asset',
            ];
        }

        return [
            'status'                        => false,
            'wallet_id'                     => null,
            'wallet_asset_id'               => null,
            'message'                       => 'Wallet address not found.',
            'model'                         => null,
        ];
    }
}

if (!function_exists('generate_tx_ref')) {
    /**
     * Generate a unique transaction reference.
     *
     * @return string
     */
    function generate_tx_ref(): string
    {
        // Generate a random 16-character string
        $randomString = \Illuminate\Support\Str::random(12);

        // Get the current timestamp
        $timestamp = time();

        // Combine the random string and timestamp
        return 'trx-' . strtoupper($randomString) . '_' . $timestamp;
    }
}


if (!function_exists('get_banks')) {
    /**
     * Fetches a list of banks for the specified country using the Flutterwave API.
     * 
     * @param string $country The country code (e.g., 'NG' for Nigeria, 'US' for United States).
     * @return array|null The list of banks or null if the request fails.
     */
    function get_banks(string $country = 'NG'): array|null
    {
        $apiKey = env('SECRET_KEY');
        $url = "https://api.flutterwave.com/v3/banks/{$country}";

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer {$apiKey}",
                "Content-Type: application/json",
                "Accept: application/json"
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Check for successful response
            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                // Sort the array by the 'name' key
                // usort($data, function ($a, $b) {
                //     return strcmp($a['name'], $b['name']);
                // });
                return $data['data'] ?? []; // Return the 'data' array (list of banks)
            } else {
                Log::error('Failed to fetch banks from Flutterwave', [
                    'country' => $country,
                    'http_code' => $httpCode,
                    'response' => $response,
                ]);
                return [];
            }
        } catch (\Exception $e) {
            Log::error('Exception in fetching banks from Flutterwave', [
                'country' => $country,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}

if (!function_exists('get_account_name')) {
    /**
     * Resolve the account name using Flutterwave API.
     *
     * @param string $account_number The account number to resolve.
     * @param string $account_bank The bank code associated with the account.
     * 
     * @return array|null Returns the resolved account details or null if an error occurs.
     */
    function resolve_account_name(string $account_number, string $account_bank): array|null
    {
        // Validate inputs
        if (empty($account_number) || empty($account_bank)) {
            Log::error('Invalid parameters provided to resolve_account_name', [
                'account_number' => $account_number,
                'account_bank' => $account_bank,
            ]);
            return [];
        }

        try {
            $url = 'https://api.flutterwave.com/v3/accounts/resolve';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('SECRET_KEY'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($url, [
                'account_number' => $account_number,
                'account_bank' => $account_bank,
            ]);

            // Check if the response is successful
            if ($response->successful()) {
                $data = $response->json();

                // Return the resolved account details
                if (isset($data['status']) && $data['status'] === 'success') {
                    return $data['data'] ?? [];
                } else {
                    Log::error('Flutterwave API returned an error', [
                        'response' => $data,
                    ]);
                    return [];
                }
            } else {
                // Log error if the response is not successful
                Log::error('Failed to resolve account name via Flutterwave API', [
                    'status_code' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }
        } catch (\Exception $e) {
            // Catch and log any unexpected errors
            Log::error('Exception occurred in resolve_account_name', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}

if (!function_exists('createTransfer')) {
    /**
     * Flutterwave Transfer Helper Function
     * 
     * Creates a bank transfer using Flutterwave API
     * 
     * @param string $bankCode The bank code (e.g., "044" for Access Bank)
     * @param string $accountNumber The recipient's account number
     * @param float $amount The amount to transfer
     * @param string $reference Unique reference for the transaction
     * 
     * @return array API response containing transfer details
     * 
     * @throws Exception If cURL request fails or API returns error
     */
    function createTransfer(string $bankCode, string $accountNumber, float $amount, string $reference): array
    {
        // Get environment variables from Laravel .env file
        $secretKey = env('SECRET_KEY');
        $publicKey = env('PUBLIC_KEY');
        $encryptionKey = env('ENCRYPTION_KEY');
        $environment = env('ENV', 'staging'); // Default to staging if not set

        // Validate required environment variables
        if (empty($secretKey) || empty($publicKey)) {
            throw new Exception('Flutterwave API keys are not configured');
        }

        // Determine API base URL based on environment
        $baseUrl = $environment === 'production'
            ? 'https://api.flutterwave.com/v3'
            : 'https://api.flutterwave.com/v3';

        // Prepare the request payload
        $payload = [
            'account_bank' => $bankCode,
            'account_number' => $accountNumber,
            'amount' => $amount,
            'narration' => 'Funds Transfer',
            'currency' => 'NGN', // Default currency, adjust as needed
            'reference' => $reference,
            'callback_url' => null, // Optional: Add your webhook URL if needed
            'debit_currency' => 'NGN' // Optional: Currency to debit from
        ];

        // Initialize cURL session
        $ch = curl_init();

        // Set cURL options
        curl_setopt_array($ch, [
            CURLOPT_URL => $baseUrl . '/transfers',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $secretKey,
                'Content-Type: application/json',
            ],
        ]);

        // Execute cURL request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        // Close cURL session
        curl_close($ch);

        // Check for cURL errors
        if ($curlError) {
            throw new Exception('cURL Error: ' . $curlError);
        }

        // Decode the JSON response
        $responseData = json_decode($response, true);

        // Check if JSON decoding was successful
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from Flutterwave API');
        }

        // Check for API errors
        if ($httpCode !== 200 || $responseData['status'] !== 'success') {
            $errorMessage = $responseData['message'] ?? 'Unknown API error';
            throw new Exception('Flutterwave API Error: ' . $errorMessage);
        }

        // Return successful response
        return [
            'success' => true,
            'status' => $responseData['status'],
            'message' => $responseData['message'],
            'data' => $responseData['data'] ?? null,
            'transfer_id' => $responseData['data']['id'] ?? null,
            'reference' => $reference
        ];
    }
}

if (!function_exists('verifyTransfer')) {
    /**
     * Flutterwave Helper Functions
     * Collection of helper functions for Flutterwave API operations
     */

    /**
     * Verify a transfer using transfer ID or reference
     * 
     * @param string $transferId The transfer ID or reference to verify
     * @return array API response containing transfer details
     * 
     * @throws Exception If cURL request fails or API returns error
     */
    function verifyTransfer(string $transferId): array
    {
        $secretKey = env('SECRET_KEY');
        $environment = env('ENV', 'staging');

        if (empty($secretKey)) {
            throw new Exception('Flutterwave secret key is not configured');
        }

        $baseUrl = $environment === 'production'
            ? 'https://api.flutterwave.com/v3'
            : 'https://api.flutterwave.com/v3';

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $baseUrl . '/transfers/' . $transferId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $secretKey,
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($curlError) {
            throw new Exception('cURL Error: ' . $curlError);
        }

        $responseData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from Flutterwave API');
        }

        if ($httpCode !== 200) {
            $errorMessage = $responseData['message'] ?? 'Unknown API error';
            throw new Exception('Flutterwave API Error: ' . $errorMessage);
        }

        return [
            'success' => $responseData['status'] === 'success',
            'status' => $responseData['status'],
            'message' => $responseData['message'],
            'data' => $responseData['data'] ?? null,
            'transfer_status' => $responseData['data']['status'] ?? null,
            'full_response' => $responseData
        ];
    }
}

/**
 * Get all transfers with optional filtering
 * 
 * @param array $filters Optional filters (page, status, etc.)
 * @return array List of transfers
 * 
 * @throws Exception If cURL request fails or API returns error
 */
function getTransfers(array $filters = []): array
{
    $secretKey = env('SECRET_KEY');
    $environment = env('ENV', 'staging');

    if (empty($secretKey)) {
        throw new Exception('Flutterwave secret key is not configured');
    }

    $baseUrl = $environment === 'production'
        ? 'https://api.flutterwave.com/v3'
        : 'https://api.flutterwave.com/v3';

    // Build query string from filters
    $queryString = '';
    if (!empty($filters)) {
        $queryString = '?' . http_build_query($filters);
    }

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . '/transfers' . $queryString,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($curlError) {
        throw new Exception('cURL Error: ' . $curlError);
    }

    $responseData = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response from Flutterwave API');
    }

    if ($httpCode !== 200) {
        $errorMessage = $responseData['message'] ?? 'Unknown API error';
        throw new Exception('Flutterwave API Error: ' . $errorMessage);
    }

    return [
        'success' => $responseData['status'] === 'success',
        'status' => $responseData['status'],
        'message' => $responseData['message'],
        'data' => $responseData['data'] ?? [],
        'meta' => $responseData['meta'] ?? null
    ];
}

/**
 * Get transfer fee for a specific amount
 * 
 * @param float $amount The amount to check fee for
 * @param string $currency Currency code (default: NGN)
 * @return array Fee information
 * 
 * @throws Exception If cURL request fails or API returns error
 */
function getTransferFee(float $amount, string $currency = 'NGN'): array
{
    $secretKey = env('SECRET_KEY');
    $environment = env('ENV', 'staging');

    if (empty($secretKey)) {
        throw new Exception('Flutterwave secret key is not configured');
    }

    $baseUrl = $environment === 'production'
        ? 'https://api.flutterwave.com/v3'
        : 'https://api.flutterwave.com/v3';

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . '/transfers/fee?amount=' . $amount . '&currency=' . $currency,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($curlError) {
        throw new Exception('cURL Error: ' . $curlError);
    }

    $responseData = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response from Flutterwave API');
    }

    if ($httpCode !== 200) {
        $errorMessage = $responseData['message'] ?? 'Unknown API error';
        throw new Exception('Flutterwave API Error: ' . $errorMessage);
    }

    return [
        'success' => $responseData['status'] === 'success',
        'status' => $responseData['status'],
        'message' => $responseData['message'],
        'fee' => $responseData['data']['fee'] ?? 0,
        'currency' => $responseData['data']['currency'] ?? $currency,
        'full_response' => $responseData
    ];
}

/**
 * Get account balance
 * 
 * @param string $currency Currency code (optional)
 * @return array Balance information
 * 
 * @throws Exception If cURL request fails or API returns error
 */
function getBalance(string $currency = ''): array
{
    $secretKey = env('SECRET_KEY');
    $environment = env('ENV', 'staging');

    if (empty($secretKey)) {
        throw new Exception('Flutterwave secret key is not configured');
    }

    $baseUrl = $environment === 'production'
        ? 'https://api.flutterwave.com/v3'
        : 'https://api.flutterwave.com/v3';

    $url = $baseUrl . '/balances';
    if (!empty($currency)) {
        $url .= '/' . $currency;
    }

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: ' . 'application/json',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($curlError) {
        throw new Exception('cURL Error: ' . $curlError);
    }

    $responseData = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response from Flutterwave API');
    }

    if ($httpCode !== 200) {
        $errorMessage = $responseData['message'] ?? 'Unknown API error';
        throw new Exception('Flutterwave API Error: ' . $errorMessage);
    }

    return [
        'success' => $responseData['status'] === 'success',
        'status' => $responseData['status'],
        'message' => $responseData['message'],
        'balance' => $responseData['data']['available_balance'] ?? 0,
        'currency' => $responseData['data']['currency'] ?? 'NGN',
        'full_response' => $responseData
    ];
}

/**
 * Verify bank account details before transfer
 * 
 * @param string $accountNumber Account number to verify
 * @param string $bankCode Bank code
 * @return array Account verification details
 * 
 * @throws Exception If cURL request fails or API returns error
 */
function verifyBankAccount(string $accountNumber, string $bankCode): array
{
    $secretKey = env('SECRET_KEY');
    $environment = env('ENV', 'staging');

    if (empty($secretKey)) {
        throw new Exception('Flutterwave secret key is not configured');
    }

    $baseUrl = $environment === 'production'
        ? 'https://api.flutterwave.com/v3'
        : 'https://api.flutterwave.com/v3';

    $payload = [
        'account_number' => $accountNumber,
        'account_bank' => $bankCode
    ];

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . '/accounts/resolve',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: ' . 'application/json',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($curlError) {
        throw new Exception('cURL Error: ' . $curlError);
    }

    $responseData = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response from Flutterwave API');
    }

    if ($httpCode !== 200) {
        $errorMessage = $responseData['message'] ?? 'Unknown API error';
        throw new Exception('Flutterwave API Error: ' . $errorMessage);
    }

    return [
        'success' => $responseData['status'] === 'success',
        'status' => $responseData['status'],
        'message' => $responseData['message'],
        'account_name' => $responseData['data']['account_name'] ?? null,
        'account_number' => $responseData['data']['account_number'] ?? null,
        'full_response' => $responseData
    ];
}

/**
 * Get list of supported banks
 * 
 * @param string $country Country code (default: NG)
 * @return array List of supported banks
 * 
 * @throws Exception If cURL request fails or API returns error
 */
function getBanks(string $country = 'NG'): array
{
    $secretKey = env('SECRET_KEY');
    $environment = env('ENV', 'staging');

    if (empty($secretKey)) {
        throw new Exception('Flutterwave secret key is not configured');
    }

    $baseUrl = $environment === 'production'
        ? 'https://api.flutterwave.com/v3'
        : 'https://api.flutterwave.com/v3';

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . '/banks/' . $country,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: ' . 'application/json',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($curlError) {
        throw new Exception('cURL Error: ' . $curlError);
    }

    $responseData = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response from Flutterwave API');
    }

    if ($httpCode !== 200) {
        $errorMessage = $responseData['message'] ?? 'Unknown API error';
        throw new Exception('Flutterwave API Error: ' . $errorMessage);
    }

    return [
        'success' => $responseData['status'] === 'success',
        'status' => $responseData['status'],
        'message' => $responseData['message'],
        'banks' => $responseData['data'] ?? [],
        'full_response' => $responseData
    ];
}

/**
 * Retry a failed transfer
 * 
 * @param string $transferId The transfer ID to retry
 * @return array API response
 * 
 * @throws Exception If cURL request fails or API returns error
 */
function retryTransfer(string $transferId): array
{
    $secretKey = env('SECRET_KEY');
    $environment = env('ENV', 'staging');

    if (empty($secretKey)) {
        throw new Exception('Flutterwave secret key is not configured');
    }

    $baseUrl = $environment === 'production'
        ? 'https://api.flutterwave.com/v3'
        : 'https://api.flutterwave.com/v3';

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . '/transfers/' . $transferId . '/retries',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: ' . 'application/json',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($curlError) {
        throw new Exception('cURL Error: ' . $curlError);
    }

    $responseData = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response from Flutterwave API');
    }

    if ($httpCode !== 200) {
        $errorMessage = $responseData['message'] ?? 'Unknown API error';
        throw new Exception('Flutterwave API Error: ' . $errorMessage);
    }

    return [
        'success' => $responseData['status'] === 'success',
        'status' => $responseData['status'],
        'message' => $responseData['message'],
        'data' => $responseData['data'] ?? null,
        'full_response' => $responseData
    ];
}

/**
 * Encrypts  values using a key from the environment variables.
 */
if (!function_exists('write')) {
    /**
     * Encrypts the given value.
     *
     * @param mixed $value The value to be encrypted.
     * @return string The encrypted string.
     */
    function write(mixed $value): string
    {
        $key = config('services.READ_AND_WRITE');
        if (!$key) {
            Log::error('Encryption key not set in .env');
            return "There is Error on our end";
            die;
        }

        $keyHash = hash('sha256', $key);
        return Crypt::encrypt($value, $keyHash);
        // $key = config('services.READ_AND_WRITE');

        // $payload = base64_encode($value);

        // $signature = substr(hash_hmac('sha256', $payload, $key), 0, 16);

        // return $payload . '.' . $signature;
    }
}
/**
 * Decrypts values using a key from the environment variables.
 */
if (!function_exists('read')) {
    /**
     * Decrypts the given encrypted value.
     *
     * @param string $value The encrypted string.
     * @return mixed The decrypted value.
     */
    function read(string $value): mixed
    {
        $key = config('services.READ_AND_WRITE');
        if (!$key) {
            Log::error('Decryption key not set in .env');
            return "There is Error on our end";
            die;
        }

        $keyHash = hash('sha256', $key);
        return Crypt::decrypt($value, $keyHash);
        // $key = config('services.READ_AND_WRITE');

        // [$payload, $signature] = explode('.', $value);

        // $expected = substr(hash_hmac('sha256', $payload, $key), 0, 16);

        // if (!hash_equals($expected, $signature)) {
        //     throw new Exception("Invalid signature");
        // }

        // return base64_decode($payload);
    }
}

if (!function_exists('varify_payment')) {


    /**
     * Verify a payment transaction using its reference.
     *
     * @param string $tx_ref Transaction reference to verify.
     * @return object Decoded JSON response from the payment verification API.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException For validation and processing errors.
     */
    function varify_payment($tx_ref): mixed
    {
        // Validate transaction reference input
        if (empty($tx_ref) || !is_string($tx_ref)) {
            Log::error('Transaction reference (tx_ref) is missing, empty, or invalid.');
            abort(400, 'Invalid or missing transaction reference.');
        }

        // Check if the transaction exists in the database
        // $transactionExists = DB::table('transactions')->where('transaction_reference_code', $tx_ref)->exists();
        // if (!$transactionExists) {
        //     Log::error("Transaction reference ($tx_ref) not found in the database.");
        //     abort(404, "Transaction reference not found.");
        // }

        // API configuration
        $url = "https://api.flutterwave.com/v3/transactions/verify_by_reference?tx_ref=$tx_ref";
        $apiKey = env('SECRET_KEY');
        $maxRetries = 5; // Maximum retry attempts
        $retryDelay = 2; // Delay between retries in seconds

        $attempt = 0;
        $response = null;

        while ($attempt < $maxRetries) {
            $ch = curl_init();

            // Set cURL options for the API call
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $apiKey",
                'Content-Type: application/json',
                'Accept: application/json',
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Set a timeout for the request

            $response = curl_exec($ch);

            // Handle cURL errors
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                Log::warning("Attempt $attempt: cURL error during payment verification for tx_ref ($tx_ref): $error");

                curl_close($ch);
                $attempt++;
                sleep($retryDelay); // Wait before retrying
                continue; // Retry the request
            }

            curl_close($ch);
            break; // Exit the retry loop if the request is successful
        }

        // Check if all retry attempts failed
        if ($response === false) {
            Log::error("Payment verification failed after $maxRetries attempts for tx_ref ($tx_ref).");
            abort(500, 'Payment verification failed. Please try again later.');
        }

        // Decode and validate the API response
        $decodedResponse = json_decode($response);

        // Ensure the response is properly formatted
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("Invalid JSON response during payment verification for tx_ref ($tx_ref): $response");
            abort(500, 'Invalid payment verification response.');
        }

        // Check for successful verification status
        if (!isset($decodedResponse->status) || $decodedResponse->status !== 'success') {
            $errorMessage = $decodedResponse->message ?? 'Unknown error during payment verification.';
            Log::error("Payment verification failed for tx_ref ($tx_ref): $errorMessage");
            abort(500, 'Payment verification unsuccessful: ' . $errorMessage);
        }

        // Log successful verification for auditing
        Log::info("Payment verification successful for tx_ref ($tx_ref).");

        // Return the decoded response
        return $decodedResponse;
    }
}

// if (!function_exists('init_fw')) {
//     /**
//      * Helper function to initialize and handle a Flutterwave payment.
//      *
//      * @return object Payment controller.
//      */
//     function init_fw()
//     {
//         // Initialize Flutterwave SDK
//         Flutterwave::bootstrap();

//         $customHandler          = new PaymentHandler();
//         $client                 = new Flutterwave();
//         $modalType              = Modal::POPUP;
//         $controller             = new PaymentController($client, $customHandler, $modalType);

//         return $controller;
//     }
// }

// Returns a constant value used for encoding/decoding IDs in make_uuid/get_id.
if (!function_exists('almax_constant')) {
    /**
     * Returns a constant integer used for encoding/decoding IDs.
     *
     * @return int
     */
    function almax_constant(): int
    {
        return 1012;
    }
}

if (!function_exists('model_constant')) {
    function model_constant($model): int
    {
        return match ($model) {
            'blog'              => 3,
            'client'            => 5,
            'product'           => 7,
            'product_variant'   => 41,
            'product_category'   => 47,
            'user'              => 11,
            'ticket'            => 13,
            'journal_entry'     => 17,
            'file_share'        => 19,
            'project'           => 23,
            'task'              => 29,
            'pos_cart'          => 31,
            'contract'          => 37,
            default             => 1,
        };
    }
}


if (!function_exists('make_uuid')) {
    /**
     * Generates a pseudo-UUID based on the given ID using a quadratic formula and returns its hexadecimal representation.
     *
     * @param int $id The numeric ID to encode.
     * @return string The generated UUID as a hexadecimal string.
     */
    function make_uuid(int $id, string $model = 'any'): string
    {
        return dechex((((int) $id ** 2 + (2 * (int) $id * almax_constant()) + almax_constant() ** 2)) * model_constant($model));
    }
}

if (!function_exists('get_id')) {
    /**
     * Retrieves the original ID from a given pseudo-UUID.
     *
     * @param string $uuid The UUID (hexadecimal string) to decode.
     * @return int The decoded numeric ID.
     */
    function get_id(string $uuid, string $model = 'any'): int
    {
        try {
            $value = hexdec($uuid);
            $prime = model_constant($model);

            // HARD CHECK: ensure this UUID belongs to this model
            if ($value % $prime !== 0) {
                throw new Exception("Invalid " . $model != 'any' ? $model : '' . " Hash Tag");
            }

            $base = $value / $prime;

            return (int) sqrt($base) - almax_constant();
        } catch (\Throwable $th) {
            // dd($th);
            return 0;
        }
    }
}

// Returns a Bootstrap-styled loading spinner placeholder as HTML.
if (!function_exists('loading_placeholder')) {
    /**
     * Generate a loading spinner placeholder HTML.
     *
     * @return string HTML for a centered loading spinner overlay.
     */
    function loading_placeholder()
    {
        return <<<'HTML'
                            <div class="show bg-dark position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center" id="spinner">
                                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                                    <span class="sr-only">Loading...</span>
                                </div>
                            </div> 
        HTML;
    }
}

if (!function_exists('airdrop')) {
    /**
     * Perform an airdrop to a wallet address if eligible.
     *
     * This function checks if the wallet exists and if the base wallet has enough balance.
     * If the target wallet's balance is below the airdrop threshold, it credits the wallet
     * and debits the base wallet, recording the transaction history for both.
     *
     * @param string $address The wallet address to airdrop to.
     * @return bool True if airdrop processed or wallet exists, false otherwise.
     */
    function airdrop($address)
    {
        $wallet = Wallet::where('address', $address)->first();

        if ($wallet) {

            $base_wallet = Wallet::find(1);

            if ($base_wallet->balance > (0.3 * 1000000)) {

                $transferAmount                         = 0.005;

                // Check if the wallet's balance is below the airdrop threshold
                if ($wallet->balance < $transferAmount) {
                    DB::transaction(function () use ($wallet, $base_wallet, $transferAmount) {

                        $wallet->balance                += $transferAmount;
                        $base_wallet->balance           -= $transferAmount;

                        $wallet->save();
                        $base_wallet->save();

                        $wallet->history()->create(
                            [
                                'amount'                    => $transferAmount,
                                'tx_ref'                    => generate_tx_ref(),
                                'status'                    => 'completed',
                                'type'                      => 'credit',
                                'description'               => "Airdrop",
                                'currency'                  => 'FADA',
                            ]
                        );

                        $base_wallet->history()->create(
                            [
                                'amount'                    => $transferAmount,
                                'tx_ref'                    => generate_tx_ref(),
                                'status'                    => 'completed',
                                'type'                      => 'debit',
                                'description'               => "Airdrop",
                                'currency'                  => 'FADA',
                            ]
                        );
                    });
                }
            }
            return true;
        } else {
            Log::error('Wallet not found for address: ' . $address);
        }
        return false;
    }
}

if (!function_exists('ip_in_range')) {

    function ip_in_range($ip, $ranges)
    {
        foreach ($ranges as $range) {
            $range = str_replace(' ', '', $range); // Remove extra spaces

            // CIDR format: 192.168.1.0/24
            if (Str::contains($range, '/')) {
                if (IpUtils::checkIp($ip, $range)) return true;
            }

            // Range format: 192.168.1.100-192.168.1.200
            elseif (Str::contains($range, '-')) {
                [$start, $end] = explode('-', $range);
                if (ip2long($ip) >= ip2long($start) && ip2long($ip) <= ip2long($end)) {
                    return true;
                }
            }

            // Single IP match
            elseif ($ip === $range) {
                return true;
            }
        }

        return false;
    }
}
if (!function_exists('last_seen')) {
    function last_seen($data = null)
    {

        // if data is null or string make it an array with key 'url' and value of the string
        if (is_string($data)) {
            $data = ['data' => $data];
        }

        \App\helpers\RequestTracker::track($data ?? []);

        if (auth()->check()) {
            auth()->user()->update(['last_seen_at' => now()]);
        }
    }
}
if (!function_exists('valid_ip')) {

    function valid_ip($ip)
    {

        $allowedRanges = [
            '192.168.43.0/24',
            '192.168.0.0/24',
            '192.168.1.0/24',
            '10.10.16.100-10.10.16.200',
            '127.0.0.1',
            // 'localhost',
        ];
        return ip_in_range($ip, $allowedRanges);
    }
}

if (!function_exists('make_valid_email')) {
    function make_valid_email($value, $domain = 'carbon.app')
    {
        $main_domain =  strtolower(str_replace(['/', ' '], '-', $domain));
        // Sanitize value: remove special characters and spaces
        $sanitized = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($value));

        // Ensure the domain does not start with a number
        if (preg_match('/^[0-9]/', $domain)) {
            $domain = 'carbon.app' . $domain;
        }

        // Ensure the local part (username) doesn't start with a number
        if (preg_match('/^[0-9]/', $sanitized)) {
            $sanitized = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($domain)) . $sanitized;
        }

        // Return final email
        return $sanitized . '@' . $main_domain;
    }
}
