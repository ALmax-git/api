<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Wallet;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $wallet                     = new Wallet();
        $wallet->user_id            = $user->id;
        $wallet->label              = 'Main Wallet';
        $wallet->address            = generate_wallet_address($user->id);
        $wallet->number             = $user->phone_number ?? null;
        $wallet->created_by         = 'system';
        $wallet->is_default         = true;
        $wallet->is_cold_storage    = false;
        $wallet->status             = \App\Enums\WalletStatus::PENDING_VERIFICATION;
        $wallet->private_key_encrypted = '';
        $wallet->save();
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
