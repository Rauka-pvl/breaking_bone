<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

abstract class ZhilikEvent implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public string $channel,
        public array $payload,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel($this->channel)];
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
