<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

/**
 * Class ReportNullSubscriptionStartDate
 *
 * @package App\Events
 */
class ReportNullSubscriptionStartDate
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

	/**
	 * @var
	 */
	public $customers;

	/**
	 * Create a new event instance.
	 * ReportNullSubscriptionStartDate constructor.
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
