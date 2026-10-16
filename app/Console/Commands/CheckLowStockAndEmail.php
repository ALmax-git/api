<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\User;
use Illuminate\Console\Command;

class CheckLowStockAndEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:check-low';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check client products for low stock and send nightly email alert';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Fetch clients eager-loading their products and owner to prevent N+1 query issues
        $clients = Client::with(['products', 'owner'])->get();

        foreach ($clients as $client) {
            // 2. Filter products where available_stock <= 3

            $lowStockProducts = $client->products->filter(function ($product) {
                return $product->available_stock <= $product->min_stock_value;
            });

            // 3. If there are low stock products, determine recipient and send email
            if ($lowStockProducts->isNotEmpty()) {

                // Fallback logic: Client email, or if null, Client Owner email
                $recipientEmail = $client->email ?? $client->owner->email ?? null;

                if ($recipientEmail) {
                    $subject = "Alert: Low Stock Notification";

                    // Build a simple HTML content string listing the items
                    $content = "<h2>Low Stock Alert</h2>";
                    $content .= "<p>The following products have 3 or fewer items remaining:</p><ul>";
                    foreach ($lowStockProducts as $product) {
                        $sub = $product->sub_title ?? $product->brand;
                        $content .= "<li><strong>{$product->name} {$sub}</strong> (Stock: {$product->available_stock})</li>";
                    }
                    $content .= "</ul>";

                    // 4. Send the email using Laravel's Mail facade
                    email($recipientEmail, $content, $subject);
                    // Mail::html($content, function ($message) use ($recipientEmail, $subject) {
                    //     $message->to($recipientEmail)
                    //             ->subject($subject);
                    // });
                }
            }
        }

        email(User::find(1)->email, 'Nightly low stock check completed successfully.');
        $this->info('Nightly low stock check completed successfully.');
    }
}
