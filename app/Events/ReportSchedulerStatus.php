<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

/**
 * Class ReportSchedulerStatus
 *
 * @package App\Events
 */
class ReportSchedulerStatus
{
	use Dispatchable, InteractsWithSockets, SerializesModels;

	/**
	 * @var
	 */
	public $cronEntries;

	/**
	 * Create a new event instance.
	 * ReportNullSubscriptionStartDate constructor.
	 *
	 * @param $customers
	 */
	public function __construct($cronEntries)
	{
		$this->cronEntries = $cronEntries;
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
