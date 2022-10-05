<?php

namespace App\Events;

use App\Model\Customer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

/**
 * Class MonthlyInvoice
 *
 * @package App\Events
 */
class MonthlyInvoice
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

	/**
	 * @var Customer
	 */
	public $customer;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;

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
