<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

/**
 * Class ReportNullSubscriptionStartData
 *
 * @package App\Events
 */
class ReportNullSubscriptionStartData
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

	/**
	 * @var
	 */
	public $customers;

	/**
	 * Create a new event instance.
	 * ReportNullSubscriptionStartData constructor.
	 *
	 * @param $customers
	 */
    public function __construct($customers)
    {
        $this->customers = $customers;
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
