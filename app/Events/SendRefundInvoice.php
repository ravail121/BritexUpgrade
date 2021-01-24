<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

/**
 * Class SendRefundInvoice
 *
 * @package App\Events
 */
class SendRefundInvoice
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

	/**
	 * @var
	 */
	public $paymentLog;

	/**
	 * @var
	 */
	public $invoice;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($paymentLog, $invoice, $pdf)
    {
        $this->paymentLog = $paymentLog;
        $this->invoice = $invoice;
        $this->pdf = $pdf;
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
