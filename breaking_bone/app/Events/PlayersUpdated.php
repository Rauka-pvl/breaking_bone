<?php

namespace App\Events;

class PlayersUpdated extends ZhilikEvent
{
    public function broadcastAs(): string
    {
        return 'players.updated';
    }
}
