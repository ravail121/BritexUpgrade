<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

/**
 * Class InvoiceEmail
 *
 * @package App\Events
 */
class InvoiceEmail
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

	/**
	 * @var
	 */
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
