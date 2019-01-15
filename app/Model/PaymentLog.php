<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model
{
    protected $table = 'payment_logs'; 

    protected $fillable = [
        'order_id', 
        'status',
    ];

    public function order()
    {
        return $this->hasOne(Order::class);
    }
}
