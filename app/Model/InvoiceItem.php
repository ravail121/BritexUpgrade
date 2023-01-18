<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\libs\Constants\ConstantInterface;
/**
 *
 */
class InvoiceItem extends Model implements ConstantInterface
{
	/**
	 *
	 */
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
        'refund'           => 10,
        'surcharge'        => 11
    ];

	/**
	 *
	 */
	const PRODUCT_TYPE = [
        'device'  => 'device',
        'sim'     => 'sim',
        'plan'    => 'plan',
        'addon'   => 'addon'
    ];

	/**
	 * @var string
	 */
	protected $table = 'invoice_item';

	/**
	 * @var string[]
	 */
	protected $fillable = [
		'invoice_id',
		'subscription_id',
		'product_type',
		'product_id',
		'type',
		'start_date',
		'description',
		'amount',
		'taxable'
	];


	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function subscription()
   	{
        return $this->hasOne('App\Model\Subscription', 'id', 'subscription_id');
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function subscriptionDetail()
    {
        return $this->belongsTo('App\Model\Subscription', 'subscription_id', 'id');
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function invoice()
   	{
        return $this->belongsTo('App\Model\Invoice');
    }

	/**
	 * @return bool
	 */
	public function getIsPlanTypeAttribute()
    {
        return $this->type == self::INVOICE_ITEM_TYPES['plan_charges'];
    }

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeServices($query)
    {
        return $query->whereIn('type', [
            self::INVOICE_ITEM_TYPES['plan_charges'], 
            self::INVOICE_ITEM_TYPES['feature_charges'], 
            self::INVOICE_ITEM_TYPES['one_time_charges'], 
            self::INVOICE_ITEM_TYPES['usage_charges']
        ]);
    }

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeUsageCharges($query)
    {
        return $query->whereIn('type', [
            self::INVOICE_ITEM_TYPES['usage_charges'],
        ]);        
    }

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeTaxes($query)
    {
        return $query->whereIn('type', [
            self::INVOICE_ITEM_TYPES['regulatory_fee'],
            self::INVOICE_ITEM_TYPES['taxes'],
        ]);
    }

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeCredits($query)
    {
        return $query->whereIn('type', [
            self::INVOICE_ITEM_TYPES['coupon'],
            self::INVOICE_ITEM_TYPES['manual'],
            self::INVOICE_ITEM_TYPES['payment'],
        ]);
    }

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopePlanCharges($query)
    {
        return $query->whereIn('type', [
            self::INVOICE_ITEM_TYPES['plan_charges'], 
            self::INVOICE_ITEM_TYPES['feature_charges']
        ]);
    }

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopePlanOnlyCharges($query)
    {
        return $query->whereIn('type', [
            self::INVOICE_ITEM_TYPES['plan_charges'], 
        ]);
    }

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopePaymentsCharges($query)
    {
        return $query->whereIn('type', [
            self::INVOICE_ITEM_TYPES['plan_charges'],
            self::INVOICE_ITEM_TYPES['coupon'],
            self::INVOICE_ITEM_TYPES['manual'],
        ]);        
    }

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeOnetimeCharges($query)
    {
        return $query->where('type', self::INVOICE_ITEM_TYPES['one_time_charges']);
                     
    }

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeTaxable($query)
    {
        return $query->where('taxable', self::TAX_TRUE);
    }

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeRegulatory($query)
    {
        return $query->where('type', self::INVOICE_ITEM_TYPES['regulatory_fee']);
    }

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeStateTax($query)
    {
        return $query->where('type', self::INVOICE_ITEM_TYPES['taxes']);
    }

	/**
	 * @return mixed
	 */
	public function totalAmount()
    {
        return $this->amount;
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function standaloneDevice()
    {
        return $this->hasMany('App\Model\Device', 'id', 'product_id');
    }

	/**
	 * @return mixed
	 */
	public function availableStandaloneDevices()
    {
        return $this->where(
            ['product_type' => 'device'],
            ['subscription_id' => 'null']
        );
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function standaloneSim()
    {
        return $this->hasMany('App\Model\Sim', 'id', 'product_id');
    }

	/**
	 * @return mixed
	 */
	public function availableStandaloneSims()
    {
        return $this->where(
            ['product_type' => 'sim'],
            ['subscription_id' => 'null']
        );
    }

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeUsedCoupon($query)
    {
        return $query->where('type', self::TYPES['coupon']);
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function customerCoupon()
    {   
        return $this->hasMany('App\Model\CustomerCoupon', 'coupon_id', 'product_id')->where('customer_id', $this->invoice->customer_id); // Should only be used with invoice->usedCoupon
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function subscriptionCoupon()
    {   
        return $this->belongsTo('App\Model\SubscriptionCoupon', 'subscription_id', 'subscription_id'); // Should only be used with invoice->usedCoupon
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function finiteSubscriptionCoupon()
    {
        return $this->subscriptionCoupon()->where('cycles_remaining', '!=', -1);
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function finiteCustomerCoupon()
    {
        return $this->customerCoupon()->where('cycles_remaining', '!=', -1);
    }

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeWithSubscription($query)
    {
        return $query->where('type', self::TYPES['plan_charges']);
    }

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeRefundItem($query)
    {
        return $query->whereType('10');
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function coupon()
    {
        return $this->hasOne('App\Model\Coupon', 'id', 'product_id');
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function customerCouponData()
    {   
        return $this->hasOne('App\Model\CustomerCoupon', 'id', 'product_id')->where('customer_id', $this->invoice->customer_id); // Should only be used with invoice->usedCoupon
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function subscriptionCouponData()
    {   
        return $this->hasOne('App\Model\SubscriptionCoupon', 'id', 'product_id'); // Should only be used with invoice->usedCoupon
    }

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeSurcharge($query) {
		return $query->where('type', [
			self::INVOICE_ITEM_TYPES['surcharge']
		]);
	}

}