<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CallSignal implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data;
    public $to;

    public function __construct($data, $to)
    {
        $this->data = $data;
        $this->to = $to;

        Log::info('📦 CallSignal created', [
            'type' => $data['type'] ?? 'unknown',
            'from' => $data['from'] ?? 'unknown',
            'to' => $to
        ]);
    }

    public function broadcastOn()
    {
        Log::info('📢 Broadcasting on channel: call.' . $this->to);
        return new Channel('call');
    }

    public function broadcastAs()
    {
        return 'signal';
    }

    public function broadcastWith()
    {
        return [
            'data' => $this->data
        ];
    }
}
