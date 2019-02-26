<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    const TYPES = [
        'plan_charges'     => 1,
        'feature_charges'  => 2,
        'one_time_charges' => 3,
        'usage_charges'    => 4,
        'regulatory_fee'   => 5,
        'coupon'           => 6,
        'taxes'            => 7,
        'manual'           => 8,
        'payment'          => 9,
    ];

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

    public function subscriptionDetail()
    {
        return $this->belongsTo('App\Model\Subscription', 'subscription_id', 'id');
    }

    public function scopeServices($query)
    {
        return $query->whereIn('type', [
            self::TYPES['plan_charges'], 
            self::TYPES['feature_charges'], 
            self::TYPES['one_time_charges'], 
            self::TYPES['usage_charges']
        ]);
    }

    public function scopeTaxes($query)
    {
        return $query->whereIn('type', [
            self::TYPES['regulatory_fee'],
            self::TYPES['taxes'],
        ]);
    }

    public function scopeCredits($query)
    {
        return $query->whereIn('type', [
            self::TYPES['coupon'],
            self::TYPES['manual'],
        ]);
    }

    public function scopePlanCharges($query)
    {
        return $query->whereIn('type', [
            self::TYPES['plan_charges'], 
            self::TYPES['feature_charges']
        ])->where('product_type', 'plan');
    }

    public function scopeOnetimeCharges($query)
    {
        return $query->where('type', self::TYPES['one_time_charges'])
                     ->whereIn('product_type', ['device', 'sim']);
    }

    public function totalAmount()
    {
        return $this->amount;
    }


}
