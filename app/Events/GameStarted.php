<?php

namespace App\Events;

class GameStarted extends ZhilikEvent
{
    public function broadcastAs(): string
    {
        return 'game.started';
    }
}
