<?php

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\Models\Reply;

class MessageReplied implements ShouldBroadcast
{
    public $reply;

    public function __construct(Reply $reply)
    {
        $this->reply = $reply;
    }

    public function broadcastOn()
    {
        // Broadcasting to a specific channel
        return new Channel('chat');
    }

    public function broadcastAs()
    {
        return 'MessageReplied';
    }
}
