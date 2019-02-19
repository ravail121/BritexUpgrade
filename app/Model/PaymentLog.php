<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model
{
    protected $table = 'payment_log'; 

    protected $fillable = [
        'customer_id', 
        'order_id', 
        'invoice_id', 
        'transaction_num', 
        'processor_customer_num', 
        'status',
        'error',
        'exp',
        'last4',
        'card_type',
        'amount',
        'card_token',
    ];

    public function order()
    {
        return $this->hasOne(Order::class);
    }
}
