<?php

namespace App\Events;

use App\Model\Customer;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

/**
 * Class AccountSuspended
 *
 * @package App\Events
 */
class AccountSuspended
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

	/**
	 * @var Customer
	 */
	public $customer;

	/**
	 * @var
	 */
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
