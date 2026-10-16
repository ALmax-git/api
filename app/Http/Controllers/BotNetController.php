<?php

namespace App\Http\Controllers;

use App\Enums\BotStatus;
use Illuminate\Http\Request;
use App\Models\Bot;
use App\Models\BotCommand;
use App\Models\BotEvent;
use Illuminate\Http\JsonResponse;

class BotNetController extends Controller
{
    private function getBot(string $key)
    {
        return Bot::where('device_key', $key)->firstOrFail();
    }

    public function heartbeat(string $device_key, Request $request)
    {
        $bot        = $this->getBot($device_key);
        if (!$bot) {
            return response()->json([
                'status'                => 'error',
                'message'               => 'Bot not found'
            ], 404);
        }
        $bot->update([
            'ip_address'                => $request->ip(),
            'last_seen_at'              => now(),
            // 'status'                    => 'active',
        ]);

        return response()->json([
            'status'                    => 'ok'
        ]);
    }

    public function init_pairing(string $device_key, Request $request)
    {
        $bot        = $this->getBot($device_key);
        if (!$bot) {
            return response()->json([
                'status'                => 'error',
                'message'               => 'Bot not found'
            ], 404);
        }
        $bot->update([
            'ip_address'                => $request->ip(),
            'last_seen_at'              => now(),
            'status'                    => 'pairing',
        ]);

        return response()->json([
            'status'                    => 'ok'
        ]);
    }
    public function commands(string $device_key, Request $request)
    {
        $bot                            = $this->getBot($device_key);

        $commands                       = BotCommand::where('bot_id', $bot->id)->where('executed', false)->get();

        return response()->json([
            'status'                    => 'ok',
            'commands'                  => $commands
        ]);
    }

    public function commandExecuted(Request $request)
    {
        $command                        = BotCommand::findOrFail($request->command_id);

        $command->update([
            'executed'                  => true,
            'executed_at'               => now(),
        ]);

        return response()->json([
            'status'                    => 'ok'
        ]);
    }

    public function event(string $device_key, Request $request)
    {
        $bot                            = $this->getBot($device_key);
        if (!$bot) {
            return response()->json([
                'status'                => 'error',
                'message'               => 'Bot not found'
            ], 404);
        }
        BotEvent::create([
            'bot_id'                    => $bot->id,
            'type'                      => $request->type,
            'severity'                  => $request->severity,
            'data'                      => json_encode($request->all()),
        ]);

        return response()->json([
            'status'                    => 'received'
        ]);
    }

    public function payload(string $device_key, Request $request)
    {
        $bot                            = $this->getBot($device_key);

        if (!$bot) {
            return response()->json([
                'status'                => 'error',
                'message'               => 'Bot not found'
            ], 404);
        }
        $bot->last_seen_at              = now();
        $bot->status                    = BotStatus::ACTIVE->value;
        $bot->ip_address                = $request->ip();
        $bot->save();
        last_seen([
            'bot_id'                    => $bot->id,
            'ip_address'                => $request->ip(),
        ]);
        $payload                        = $bot->payloads()->get()->map(function ($item) {

            $meta                       = $item->metadata ?? [];

            return [
                'pin'                   => (int) $item->pin_number,
                'name'                  => $item->name,
                'value'                 => (int) $item->pin_value,
                'mode'                  => $meta['mode'] ?? 'static',
                'on_ms'                 => $meta['on_ms'] ?? null,
                'off_ms'                => $meta['off_ms'] ?? null,
            ];
        });
        return response()->json([
            'status'                    => 'ok',
            'payload'                   => $payload,
        ]);
    }
    public function xguard(string $device_key, Request $request)
    {
        $bot = $this->getBot($device_key);

        if (!$bot) {
            return response()->json([
                'status' => 'error',
                'message' => 'Device not found',
            ], 404);
        }

        // Device heartbeat
        $bot->last_seen_at = now();
        $bot->status = BotStatus::ACTIVE->value;
        $bot->ip_address = $request->ip();
        $bot->save();

        last_seen([
            'bot_id' => $bot->id,
            'ip_address' => $request->ip(),
        ]);

        // Incoming telemetry
        $mq = (int) $request->input('mq', 0);
        $temp = (int) $request->input('temp', 0);
        $ir = (int) $request->input('ir', 0);

        /*
        |--------------------------------------------------------------------------
        | Decision Engine
        |--------------------------------------------------------------------------
        */

        $alarm = false;
        if ($mq > 550 || ($temp > 4500 && $ir == 1)) {
            $alarm = true;
        }

        return response()->json([
            'status' => 'ok',

            'payload' => [

                'alarm' => $alarm,

                'buzzer' => $alarm,

                'led' => $alarm,

                'interval' => $alarm ? 5 : 30,

                'message' => $alarm
                    ? 'Fire detected'
                    : 'Safe',

                'timestamp' => now()->timestamp,
                'mq' => $mq,
                'temp' => $temp,
                'ir' => $ir,
            ]
        ]);
    }
    public function status(Bot $bot): JsonResponse
    {
        $clientId = auth()->user()?->client_id;

        abort_unless(
            $clientId && $bot->client_id === $clientId,
            404
        );

        return response()->json([
            'success' => true,
            'status'  => $bot->status,
        ]);
    }
    public function ping()
    {
        return response()->json([
            'status'                    => 'ok',
            'message'                   => 'pong'
        ]);
    }
}
