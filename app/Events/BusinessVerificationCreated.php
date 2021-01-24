<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

/**
 * Class BusinessVerificationCreated
 *
 * @package App\Events
 */
class BusinessVerificationCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

	/**
	 * @var
	 */
	public $orderHash;

	/**
	 * @var
	 */
	public $bizHash;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($orderHash,$bizHash)
    {
        $this->orderHash = $orderHash;
        $this->bizHash   = $bizHash;

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
