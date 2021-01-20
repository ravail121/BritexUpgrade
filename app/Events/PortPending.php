<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

/**
 * Class PortPending
 *
 * @package App\Events
 */
class PortPending
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

	/**
	 * @var
	 */
	public $portId;
    /**
     * Create a new event instance.
     *
     * @return voportId
     */
    public function __construct($portId)
    {
        $this->portId = $portId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
