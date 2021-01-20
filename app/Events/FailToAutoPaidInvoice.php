<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

/**
 * Class FailToAutoPaidInvoice
 *
 * @package App\Events
 */
class FailToAutoPaidInvoice
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $customer;
    public $description;
    

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($customer, $description)
    {
        $this->customer = $customer;
        $this->description = $description;
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
