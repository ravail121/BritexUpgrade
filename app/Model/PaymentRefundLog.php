<?php

namespace App\Model;

use Carbon\Carbon;
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
        'invoice_id',
        'transaction_num',
        'error',
        'amount',
        'status',
    ];

    public function paymentLog()
    {
        return $this->belongsTo('App\Model\PaymentLog', 'payment_log_id')->orderBy('id', 'desc');
    }

    public function getCreatedAtFormattedAttribute()
    {
        if($this->created_at){
            return Carbon::parse($this->created_at)->format('M d, Y');   
        }
        return 'NA';
    }
}
