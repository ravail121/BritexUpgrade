<?php

namespace App\Events;

use App\Model\Customer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class AccountSuspended
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $customer;
    public $subscriptions;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Customer $customer, $subscriptions, $amount)
    {
        $this->customer = $customer;
        $this->subscriptions = $subscriptions;
        $this->amount = $amount;
    }
}
