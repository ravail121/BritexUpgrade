<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

/**
 * Class UpgradeDowngradeInvoice
 *
 * @package App\Events
 */
class UpgradeDowngradeInvoice
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order, $pdf;


    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($order, $pdf)
    {
        $this->order = $order;
        $this->pdf   = $pdf;
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
