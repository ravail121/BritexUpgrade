<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\libs\Constants\ConstantInterface;

class PendingCharge extends Model implements ConstantInterface
{

    protected $table = 'pending_charge';
    protected $fillable = ['customer_id', 'invoice_id', 'type', 'amount', 'description',];


    public function Customer()
    {
        return $this->hasOne('App\Model\Customer', 'id', 'customer_id');
   	}

    public function scopeZeroInvoice($query)
    {
        return $query->where('invoice_id', 0)->whereIn('type', [
            self::TYPES['one_time_charges'],
            self::TYPES['usage_charges'],
        ]);
    }
}
