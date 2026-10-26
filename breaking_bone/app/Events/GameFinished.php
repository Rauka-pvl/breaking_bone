<?php

namespace App\Events;

class GameFinished extends ZhilikEvent
{
    public function broadcastAs(): string
    {
        return 'game.finished';
    }
}
