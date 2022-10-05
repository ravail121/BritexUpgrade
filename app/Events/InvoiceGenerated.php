<?php

namespace App\Events;

use App\Model\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

/**
 * Class InvoiceGenerated
 *
 * @package App\Events
 */
class InvoiceGenerated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

	/**
	 * @var Order
	 */
	public $order, $pdf;


    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Order $order, $pdf)
    {
        $this->order = $order;
        $this->pdf   = $pdf;
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
