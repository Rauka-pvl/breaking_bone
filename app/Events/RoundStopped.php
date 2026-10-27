<?php

namespace App\Events;

class RoundStopped extends ZhilikEvent
{
    public function broadcastAs(): string
    {
        return 'round.stopped';
    }
}
