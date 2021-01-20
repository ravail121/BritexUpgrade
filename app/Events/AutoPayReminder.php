<?php

namespace App\Events;

use App\Model\Customer;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

/**
 * Class AutoPayReminder
 *
 * @package App\Events
 */
class AutoPayReminder
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $customer, $invoice;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Customer $customer, $invoice)
    {
        $this->customer = $customer;
        $this->invoice = $invoice;
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
