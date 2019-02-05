<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'order';

    protected $fillable = [
        'active_group_id', 'active_subscription_id', 'order_num', 'status', 'invoice_id', 'hash', 'company_id', 'customer_id', 'date_processed' 
    ];

    public function order_group(){
    	return $this->belongsTo('App\Model\OrderGroup')->withTrashed();
    }

    public function bizVerification()
    {
        return $this->hasOne(BusinessVerification::class);
    }

    public function OG()
    {
        return $this->hasOne('App\Model\OrderGroup', 'id', 'active_group_id');
    }

    public function scopeHash($query, $hash)
    {
        return $query->where('hash', $hash);
    }

    public function customer()
    {
        return $this->hasOne('App\Model\Customer', 'id', 'customer_id');
    }
    public function company()
    {
        return $this->hasOne('App\Model\Company', 'id', 'company_id');
    }

    public function invoice()
    {
        return $this->hasOne('App\Model\Invoice', 'id', 'company_id');
    }

    public function paymentLog()
    {
        return $this->belongsTo(PaymentLog::class);
    }
}
