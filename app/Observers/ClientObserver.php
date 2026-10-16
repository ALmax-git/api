<?php

namespace App\Observers;

use App\Models\Client;
use App\Models\Wallet;

class ClientObserver
{
    /**
     * Handle the Client "created" event.
     */
    public function created(Client $client): void
    {
        $wallet                     = new Wallet();
        $wallet->client_id          = $client->id;
        $wallet->label              = 'Business Wallet';
        $wallet->address            = generate_wallet_address($client->id);
        $wallet->number             = $client->phone_number ?? null;
        $wallet->created_by         = 'system';
        $wallet->is_default         = true;
        $wallet->is_cold_storage    = false;
        $wallet->status             = \App\Enums\WalletStatus::PENDING_VERIFICATION;
        $wallet->private_key_encrypted = '';
        $wallet->save();
    }

    /**
     * Handle the Client "updated" event.
     */
    public function updated(Client $client): void
    {
        //
    }

    /**
     * Handle the Client "deleted" event.
     */
    public function deleted(Client $client): void
    {
        //
    }

    /**
     * Handle the Client "restored" event.
     */
    public function restored(Client $client): void
    {
        //
    }

    /**
     * Handle the Client "force deleted" event.
     */
    public function forceDeleted(Client $client): void
    {
        //
    }
}
