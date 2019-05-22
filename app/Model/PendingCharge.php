<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\libs\Constants\ConstantInterface;

class PendingCharge extends Model implements ConstantInterface
{

    protected $table = 'pending_charge';
    protected $fillable = ['customer_id', 'subscription_id', 'invoice_id', 'type', 'amount', 'description',];


    public function Customer()
    {
        return $this->hasOne('App\Model\Customer', 'id', 'customer_id');
   	}

    public function customerDetails()
    {
        return $this->belongsTo('App\Model\Customer', 'customer_id', 'id');
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'subscription_id', 'id');
    }

    public function scopeWithoutInvoice($query)
    {
        return $query->where('invoice_id', 0);
    }

    public function scopeZeroInvoice($query)
    {
        return $query->where('invoice_id', 0)->whereIn('type', [
            self::INVOICE_ITEM_TYPES['one_time_charges'],
            self::INVOICE_ITEM_TYPES['usage_charges'],
        ]);
    }
}
