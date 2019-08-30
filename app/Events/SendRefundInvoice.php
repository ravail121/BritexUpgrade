<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class SendRefundInvoice
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $paymentLog;
    public $amount;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($paymentLog, $amount, $pdf)
    {
        $this->paymentLog = $paymentLog;
        $this->amount = $amount;
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
