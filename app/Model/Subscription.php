<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Subscription
 *
 * @package App\Model
 */
class Subscription extends Model
{
	/**
	 * @var string
	 */
	protected $table = 'subscription';

	/**
	 * @var string[]
	 */
	protected $fillable = [
		'order_id',
		'customer_id',
		'company_id',
		'plan_id',
		'phone_number',
		'status',
		'sub_status',
		'upgrade_downgrade_status',
		'upgrade_downgrade_date_submitted',
		'account_past_due_date',
		'port_in_progress',
		'sim_id',
		'sim_name',
		'sim_card_num',
		'old_plan_id',
		'new_plan_id',
		'downgrade_date',
		'scheduled_suspend_date',
		'scheduled_close_date',
		'tracking_num',
		'device_id',
		'device_os',
		'device_imei',
		'subsequent_porting',
		'requested_area_code',
		'ban_id',
		'ban_group_id',
		'activation_date',
		'suspended_date',
		'closed_date',
		'shipping_date',
		'order_num',
		'sent_to_readycloud',
		'label',
		'requested_zip',
		'sent_to_shipping_easy',
		'pending_number_change'
	];

	/**
	 * @var string[]
	 */
	protected $dates = [
		'account_past_due_date',
		'scheduled_suspend_date',
		'scheduled_close_date',
		'activation_date'
	];

	/**
	 * @var string[]
	 */
	protected $appends = [
		'phone_number_formatted',
		'status_formated'
	];

	/**
	 *
	 */
	const SUB_STATUSES = [
		'active'                => 'active',
		'suspend-scheduled'     => 'suspend-scheduled',
		'close-scheduled'       => 'close-scheduled',
		'account-past-due'      => 'account-past-due',
		'for-restoration'       => 'for-restoration',
		'closed'                => 'closed',
		'confirm-closing'       => 'confirm-closing',
		'confirm-suspension'    => 'confirm-suspension'
	];

	/**
	 *
	 */
	const STATUS = [
		'suspended'         => 'suspended',
		'closed'            => 'closed',
		'active'            => 'active',
		'for-activation'    => 'for-activation',
	];

	/**
	 *
	 */
	const STATUSFORMATED = [
		''               => '',
		'suspended'      => 'Subscription Suspended',
		'closed'         => 'Subscription Closed',
		'active'         => 'active',
		'for-activation' => 'Wating for Activation',
		'shipping'       => 'Wating for Activation',
	];

	/**
	 *
	 */
	const InvoiceItemTypes = [
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
	];

	/**
	 *
	 */
	const UpgradeDowngradeStatus = [
		'downgrade' => 'downgrade-scheduled',
		'upgrade'   => 'for-upgrade',
		'same_plan' => 'sameplan'
	];

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function customer()
	{
		return $this->hasOne('App\Model\Customer', 'id', 'customer_id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function company()
	{
		return $this->hasOne('App\Model\Company', 'id', 'company_id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function sim()
	{
		return $this->belongsTo('App\Model\Sim', 'sim_id', 'id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function simDetail()
	{
		return $this->hasOne('App\Model\Sim', 'id', 'sim_id');
	}


	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function customerRelation()
	{
		return $this->belongsTo('App\Model\Customer', 'customer_id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function port()
	{
		return $this->hasOne('App\Model\Port');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function subscription_addon(){

		return $this->hasMany('App\Model\SubscriptionAddon', 'id');
	}

	/**
	 * @return mixed
	 */
	public function getNamesOfSubscriptionAddonNotRemovedAttribute()
	{
		return $this->subscriptionAddonNotRemoved->load('addons')->pluck('addons.name');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function subscriptionAddon()
	{
		return $this->hasMany('App\Model\SubscriptionAddon', 'subscription_id', 'id');
	}

	/**
	 * @return mixed
	 */
	public function subscriptionAddonNotRemoved()
	{
		return $this->subscriptionAddon()->notRemoved();
	}

	/**
	 * @return mixed
	 */
	public function billableSubscriptionAddons()
	{
		return $this->subscriptionAddon()->billable();
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function invoiceItemDetail()
	{
		return $this->hasMany('App\Model\InvoiceItem', 'subscription_id', 'id');
	}

	/**
	 * @return mixed
	 */
	public function invoiceItemOfTaxableServices()
	{
		return $this->invoiceItemDetail()->services()->taxable();
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function pendingCharges()
	{
		return $this->hasMany(PendingCharge::class, 'subscription_id', 'id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function ban()
	{
		return $this->belongsTo('App\Model\Ban', 'ban_id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function plan()
	{
		return $this->belongsTo('App\Model\Plan', 'plan_id', 'id');
	}


	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function device()
	{
		return $this->hasOne('App\Model\Device', 'id', 'device_id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function new_plan()
	{
		return $this->hasOne('App\Model\Plan', 'id' );
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function oldPlan()
	{
		return $this->belongsTo('App\Model\Plan', 'old_plan_id', 'id');
	}


	/**
	 * Creating new function, and not touching original new_plan()
	 * as it may be used in the application
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function newPlanDetail()
	{
		return $this->belongsTo('App\Model\Plan', 'new_plan_id', 'id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function order()
	{
		return $this->belongsTo('App\Model\Order');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function plans()
	{
		return $this->belongsTo('App\Model\Plan', 'plan_id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function addon()
	{
		return $this->belongsTo('App\Model\Addon' , 'id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function coupon()
	{
		return $this->hasOne('App\Model\coupon', 'id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function subscriptionCoupon()
	{
		return $this->hasMany('App\Model\SubscriptionCoupon');
	}

	/**
	 * @return mixed
	 */
	public function subscriptionCouponRedeemable()
	{
		return $this->subscriptionCoupon()->redeemable();
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function usageData()
	{
		return $this->hasOne('App\Model\UsageData', 'simnumber', 'sim_card_num');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function attTwoUsageData()
	{
		return $this->hasOne('App\Model\AttTwoUsageData', 'iccid', 'sim_card_num');
	}

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeBillabe($query)
	{
		return $query
			->whereIn('status', [
				'active', 'shipping', 'for-activation'])
			// ->whereNotIn('phone_number', ['', 'null'])
			->notSuspendedOrClosed()
			->notScheduledForSuspensionOrClosure();
	}

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeBillabeForCoupons($query)
	{
		return $query
			->whereIn('status', [
				'active', 'shipping', 'for-activation'])
			->notSuspendedOrClosed()
			->notScheduledForSuspensionOrClosure();
	}

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeNotClosed($query)
	{
		return $query->where('status', '!=', 'closed');
	}

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeShipping($query)
	{
		return $query->where([['status', 'shipping'], ['sent_to_readycloud', 0], ['sent_to_shipping_easy', 0]]);
	}

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeShippingData($query)
	{
		return $query->where('status', 'shipping');
	}

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeNotScheduledForSuspensionOrClosure($query)
	{
		return $query->whereNotIn('sub_status', ['suspend-scheduled', 'close-scheduled'])->orWhere('sub_status', null);
	}

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeNotScheduledForDowngrade($query)
	{
		return $query->where('upgrade_downgrade_status', '!=', 'downgrade-scheduled');
	}

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeNotSuspendedOrClosed($query)
	{
		return $query->whereNotIn('status', ['suspend-scheduled', 'close-scheduled']);
	}

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopeTodayEqualsDowngradeDate($query)
	{
		$today = Carbon::today();
		return $query->where('downgrade_date', $today->toDateString());
	}

	/**
	 * @param $grace
	 *
	 * @return bool
	 */
	public function checkGracePeriod($grace)
	{
		$today = Carbon::today();
		$date  = Carbon::parse($this->suspended_date);
		$value = $today->diffInDays($date);
		return $value > $grace;
	}

	/**
	 * @return bool
	 */
	public function getIsStatusShippingOrForActivationAttribute()
	{
		return in_array($this->status, ['shipping', 'for-activation']);
	}

	/**
	 * @return string|string[]|null
	 */
	public function getPhoneNumberFormattedAttribute()
	{
		if($this->phone_number){
			$length = strlen((string)$this->phone_number) -6;
			return preg_replace("/^1?(\d{3})(\d{3})(\d{".$length."})$/", "$1-$2-$3", $this->phone_number);
		}
		return 'NA';
	}

	/**
	 * @return bool
	 */
	public function getIsStatusActiveNotUpgradeDowngradeStatusAttribute()
	{
		return (
			$this->status == 'active'
			&& $this->upgrade_downgrade_status != 'downgrade-scheduled'
			&& !(in_array($this->sub_status, [
				self::SUB_STATUSES['suspend-scheduled'],
				self::SUB_STATUSES['close-scheduled']
			]))
		);
	}

	/**
	 * @return bool
	 */
	public function getStatusActiveAndUpgradeDowngradeStatusAttribute()
	{
		return ($this->status == 'active' && $this->upgrade_downgrade_status == 'downgrade-scheduled');
	}

	/**
	 * @param $value
	 *
	 * @return string
	 */
	public function getShippingDateAttribute($value)
	{
		if (isset($value)) {
			return Carbon::parse($value)->format('M-d-Y');
		}
		return "NA";
	}

	/**
	 * @return string
	 */
	public function getCalPlanChargesAttribute()
	{
		$plans = $this->invoiceItemDetail()->planCharges()->get();
		return $this->calCharges($plans);
	}

	/**
	 * @return string
	 */
	public function getCalUsageChargesAttribute()
	{
		$invoiceItems = $this->invoiceItemDetail()->usageCharges()->get();
		return $this->calCharges($invoiceItems);
	}

	/**
	 * @return string
	 */
	public function getCalOnetimeChargesAttribute()
	{
		$products = $this->invoiceItemDetail()->onetimeCharges()->get();
		return $this->calCharges($products);
	}

	/**
	 * @return string
	 */
	public function getCalTaxesAttribute()
	{
		$invoiceItems = $this->invoiceItemDetail()->taxes()->get();
		return $this->calCharges($invoiceItems);
	}

	/**
	 * @return string
	 */
	public function getCalCreditsAttribute()
	{
		$invoiceItems = $this->invoiceItemDetail()->credits()->get();
		return $this->calCharges($invoiceItems);
	}

	/**
	 * @param $products
	 *
	 * @return string
	 */
	protected function calCharges($products)
	{
		$charges = [];

		foreach ($products as $product) {
			array_push($charges, $product->amount);
		}

		$total = array_sum($charges);
		return self::toTwoDecimals($total);
	}


	/**
	 * [toTwoDecimals description]
	 * @param  [type] $amount [description]
	 * @return [type]         [description]
	 */
	public static function toTwoDecimals($amount)
	{
		return number_format((float)$amount, 2, '.', '');
	}

	/**
	 * @return string
	 */
	public function getCalTotalChargesAttribute()
	{
		$credits = $this->calCharges($this->invoiceItemDetail()->credits()->get());
		$total = $this->calCharges($this->invoiceItemDetail()
		                                ->where('type', '!=', self::InvoiceItemTypes['coupon'])
		                                ->where('type', '!=', self::InvoiceItemTypes['refund'])
		                                ->where('type', '!=', self::InvoiceItemTypes['manual'])
		                                ->get());
		return $total - $credits;

	}

	/**
	 * @return string
	 */
	public function getCalRegulatoryFeeAttribute()
	{
		return $this->calCharges(
			$this->invoiceItemDetail()->where('type', self::InvoiceItemTypes['regulatory_fee'])->get()
		);
	}

	/**
	 * @return string
	 */
	public function getCalTaxRateAttribute()
	{
		return $this->calCharges(
			$this->invoiceItemDetail()->where('type', self::InvoiceItemTypes['taxes'])->get()
		);
	}

	/**
	 * @param $types
	 * @param $invoiceId
	 * @param $subscriptionId
	 *
	 * @return float|int
	 */
	public static function calculateChargesForAllproducts($types, $invoiceId, $subscriptionId)
	{
		$amount = [];
		foreach ($types as $type) {
			array_push($amount, self::find($subscriptionId)
			                        ->invoiceItemDetail()
			                        ->where('invoice_id', $invoiceId)
			                        ->where('type', $type)
			                        ->sum('amount'));
		}
		return array_sum($amount);
	}

	/**
	 * @param $invoiceId
	 * @param $subscription
	 *
	 * @return mixed
	 */
	public static function totalSubscriptionCharges($invoiceId, $subscription)
	{
		$total = $subscription->invoiceItemDetail()->where('invoice_id', $invoiceId)
		                      ->where('type', '!=', self::InvoiceItemTypes['coupon'])
		                      ->where('type', '!=', self::InvoiceItemTypes['refund'])
		                      ->where('type', '!=', self::InvoiceItemTypes['manual'])
		                      ->sum('amount');
		return $total;
	}

	/**
	 * @param $invoiceId
	 * @param $subscription
	 *
	 * @return mixed
	 */
	public static function totalSubscriptionDiscounts($invoiceId, $subscription)
	{
		$discount  = $subscription->invoiceItemDetail()->where('invoice_id', $invoiceId)
		                          ->where('type', [
			                          self::InvoiceItemTypes['coupon'],
			                          self::InvoiceItemTypes['refund'],
			                          self::InvoiceItemTypes['manual']
		                          ])->sum('amount');
		return $discount;
	}

	/**
	 * @param $item
	 * @param $invoiceId
	 *
	 * @return array
	 */
	public static function getAddonData($item, $invoiceId)
	{
		$amount = self::find($item->subscription_id)
			->invoiceItemDetail
			->where('invoice_id', $invoiceId)
			->where('type', self::InvoiceItemTypes['feature_charges'])
			->where('product_id', $item->addon_id)
			->sum('amount');
		$name = Addon::find($item->addon_id)->name;
		return [
			'name' => $name,
			'amount' => $amount
		];
	}

	/**
	 * @return string
	 */
	public function getStatusFormatedAttribute()
	{
		return self::STATUSFORMATED[$this->status];
	}

	/**
	 * @return bool
	 */
	public function getDowngradeStatusAttribute()
	{
		return $this->upgrade_downgrade_status == self::UpgradeDowngradeStatus['downgrade'] ? true : false;
	}

	/**
	 * @return bool
	 */
	public function getUpgradeStatusAttribute()
	{
		return $this->upgrade_downgrade_status == self::UpgradeDowngradeStatus['upgrade'] ? true : false;
	}

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scopePendingNumberChange($query)
	{
		return $query->where('pending_number_change', 1);
	}
}