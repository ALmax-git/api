<?php

namespace App\Helpers;

use App\Models\Request;
use Carbon\Carbon;

class RequestTracker
{
    /**
     * Track requests/activity in grouped 2-minute windows.
     *
     * Example:
     *
     * RequestTracker::track([
     *     'type' => 'bot',
     *     'id' => 1,
     *     'action' => 'heartbeat',
     *     'meta' => [
     *         'status' => 'online'
     *     ]
     * ]);
     */
    public static function track(array $data = [])
    {
        /*
        |--------------------------------------------------------------------------
        | Time Window
        |--------------------------------------------------------------------------
        |
        | Group requests into 2-minute buckets to reduce DB spam.
        |
        */

        $roundedTime = Carbon::now()
            ->startOfMinute()
            ->subMinutes(now()->minute % 2);

        /*
        |--------------------------------------------------------------------------
        | Fetch Or Create Row
        |--------------------------------------------------------------------------
        */

        $requestData = Request::firstOrNew([
            'rounded_at' => $roundedTime,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Initialize Row
        |--------------------------------------------------------------------------
        */

        if (!$requestData->exists) {

            $requestData->rounded_at = $roundedTime;

            $requestData->count = 0;

            $requestData->ip = request()->ip();

            $requestData->data = [];
        }

        /*
        |--------------------------------------------------------------------------
        | Increment Global Count
        |--------------------------------------------------------------------------
        */

        $requestData->count++;

        /*
        |--------------------------------------------------------------------------
        | Get Existing Aggregated Data
        |--------------------------------------------------------------------------
        */

        $existingData = $requestData->data ?? [];

        if (!is_array($existingData)) {
            $existingData = [];
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize Entity
        |--------------------------------------------------------------------------
        */

        $type = strtolower($data['type'] ?? 'guest');

        $allowedTypes = [
            'bot',
            'user',
            'guest',
            'system',
            'api',
        ];

        if (!in_array($type, $allowedTypes)) {
            $type = 'guest';
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve ID
        |--------------------------------------------------------------------------
        */

        $id = $data['id'] ?? request()->ip();

        /*
        |--------------------------------------------------------------------------
        | Create Safe Tracking Key
        |--------------------------------------------------------------------------
        */

        $safeId = preg_replace(
            '/[^a-zA-Z0-9]/',
            '',
            (string) $id
        );

        $key = $type . '_' . $safeId;

        /*
        |--------------------------------------------------------------------------
        | Prevent Memory Explosion
        |--------------------------------------------------------------------------
        */

        $maxEntities = 5000;

        if (
            count($existingData) >= $maxEntities
            && !isset($existingData[$key])
        ) {
            return $requestData;
        }

        /*
        |--------------------------------------------------------------------------
        | Initialize Entity
        |--------------------------------------------------------------------------
        */

        if (!isset($existingData[$key])) {

            $existingData[$key] = [

                'type'          => $type,

                'id'            => $id,

                'count'         => 0,

                'action'        => null,

                'last_ip'       => null,

                'first_seen'    => time(),

                'last_seen'     => time(),

                'meta'          => [],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Increment Entity Count
        |--------------------------------------------------------------------------
        */

        $existingData[$key]['count']++;

        /*
        |--------------------------------------------------------------------------
        | Update Metadata
        |--------------------------------------------------------------------------
        */

        $existingData[$key]['last_ip'] =
            $data['ip_address']
            ?? request()->ip();

        $existingData[$key]['last_seen'] =
            time();

        /*
        |--------------------------------------------------------------------------
        | Action
        |--------------------------------------------------------------------------
        */

        if (!empty($data['action'])) {

            $existingData[$key]['action'] =
                $data['action'];
        }

        /*
        |--------------------------------------------------------------------------
        | Merge Meta
        |--------------------------------------------------------------------------
        */

        if (
            !empty($data['meta'])
            && is_array($data['meta'])
        ) {

            $existingData[$key]['meta'] = array_merge(
                $existingData[$key]['meta'] ?? [],
                $data['meta']
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Save Back
        |--------------------------------------------------------------------------
        */

        $requestData->data = $existingData;

        /*
        |--------------------------------------------------------------------------
        | Save Once
        |--------------------------------------------------------------------------
        */
        // dd($requestData, $existingData);
        $requestData->save();

        return $requestData;
    }
}
