<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class InvoiceEmail
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $invoice, $pdf, $type;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($invoice, $pdf, $type)
    {
        $this->invoice = $invoice;
        $this->pdf   = $pdf;
        $this->type = $type;
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
