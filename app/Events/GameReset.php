<?php

namespace App\Events;

class GameReset extends ZhilikEvent
{
    public function broadcastAs(): string
    {
        return 'game.reset';
    }
}
