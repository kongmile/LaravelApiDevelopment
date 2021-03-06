<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use App\FriendRequesting;
use Dingo\Api\Routing\Helpers;

class FriendRequestingCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $friendRequesting;
    public $from_user;
    public $to_user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(FriendRequesting $friendRequesting)
    {
        $this->friendRequesting = $friendRequesting;
        $this->friendRequesting->fromUser;
        $this->friendRequesting->toUser;

    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('chat.user.'.$this->friendRequesting->to);
    }

    public function broadcastWith()
    {
        $friendRequestion = $this->friendRequesting->toArray();
        $friendRequestion['from'] = $this->friendRequesting->fromUser;
        $friendRequestion['to'] = $this->friendRequesting->toUser;
        return $friendRequestion;
    }
}