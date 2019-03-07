<?php

namespace App\Events;

use App\Model\BusinessVerification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class BusinessVerificationApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $bizVerification;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($bizHash)
    {
        $this->bizVerification = BusinessVerification::hash($bizHash)->first();

    }
    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
