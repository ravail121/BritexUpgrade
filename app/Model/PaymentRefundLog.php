<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PaymentRefundLog extends Model
{

	const STATUS = [
        'fail'      => 0,
        'success'   => 1,
    ];
    
	protected $table = 'payment_refund_log';

	protected $fillable = [
        'payment_log_id',
        'transaction_num',
        'error',
        'amount',
        'status',
    ];
}
