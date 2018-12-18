<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class BusinessVerificationCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $orderHash;
    public $bizHash;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($orderHash,$bizHash)
    {
        //\Log::info('hello world');
        $this->orderHash = $orderHash;
        $this->bizHash   = $bizHash;
        //dd($this->biz_hash);

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
