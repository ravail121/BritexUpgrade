<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\libs\Constants\ConstantInterface;

class InvoiceItem extends Model implements ConstantInterface
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

    public function subscriptionDetail()
    {
        return $this->belongsTo('App\Model\Subscription', 'subscription_id', 'id');
    }

    public function invoice()
   	{
        return $this->belongsTo('App\Model\Invoice');
    }

    public function getIsPlanTypeAttribute()
    {
        return $this->type == self::INVOICE_ITEM_TYPES['plan_charges'];
    }

    public function scopeServices($query)
    {
        return $query->whereIn('type', [
            self::INVOICE_ITEM_TYPES['plan_charges'], 
            self::INVOICE_ITEM_TYPES['feature_charges'], 
            self::INVOICE_ITEM_TYPES['one_time_charges'], 
            self::INVOICE_ITEM_TYPES['usage_charges']
        ]);
    }

    public function scopeUsageCharges($query)
    {
        return $query->whereIn('type', [
            self::INVOICE_ITEM_TYPES['usage_charges'],
        ]);        
    }

    public function scopeTaxes($query)
    {
        return $query->whereIn('type', [
            self::INVOICE_ITEM_TYPES['regulatory_fee'],
            self::INVOICE_ITEM_TYPES['taxes'],
        ]);
    }

    public function scopeCredits($query)
    {
        return $query->whereIn('type', [
            self::INVOICE_ITEM_TYPES['coupon'],
            self::INVOICE_ITEM_TYPES['manual'],
        ]);
    }

    public function scopePlanCharges($query)
    {
        return $query->whereIn('type', [
            self::INVOICE_ITEM_TYPES['plan_charges'], 
            self::INVOICE_ITEM_TYPES['feature_charges']
        ])->whereIn('product_type', ['plan', 'addon']);
    }

    public function scopePaymentsCharges($query)
    {
        return $query->whereIn('type', [
            self::INVOICE_ITEM_TYPES['plan_charges'],
            self::INVOICE_ITEM_TYPES['coupon'],
            self::INVOICE_ITEM_TYPES['manual'],
        ]);        
    }

    public function scopeOnetimeCharges($query)
    {
        return $query->where('type', self::INVOICE_ITEM_TYPES['one_time_charges']);
                     
    }

    public function scopeTaxable($query)
    {
        return $query->where('taxable', self::TAX_TRUE);
    }

    public function totalAmount()
    {
        return $this->amount;
    }

}
