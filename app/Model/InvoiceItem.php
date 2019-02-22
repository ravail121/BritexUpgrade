<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $table = 'invoice_item';


    protected $fillable = [ 'invoice_id', 'subscription_id', 'product_type', 'product_id', 'type', 'start_date', 'description', 'amount', 'taxable'];
    

    public function subscription()
   	{
        return $this->hasOne('App\Model\Subscription', 'id', 'subscription_id');
    }

    public function invoice()
   	{
        return $this->belongsTo('App\Model\Invoice');
    }

    public function scopeServices($query)
    {
        return $query->where('type', [1,2,3,4]);
    }

    public function scopeTaxes($query)
    {
        return $query->where('type', [5,7]);
    }

    public function scopeCredits($query)
    {
        return $query->where('type', [6,8]);
    }




    public static function toTwoDecimals($amount)
    {
        return number_format((float)$amount, 2, '.', '');
    }


    public function totalAmount()
    {
        return $this->amount;
    }


}
