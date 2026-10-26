<?php

namespace App\Events;

class ScoreUpdated extends ZhilikEvent
{
    public function broadcastAs(): string
    {
        return 'score.updated';
    }
}
