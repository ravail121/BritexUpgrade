<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $table = 'subscription';

    protected $fillable = [
      'order_id',
      'customer_id',
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
    ];

    protected $dates = [
        'account_past_due_date',
        'scheduled_suspend_date',
        'scheduled_close_date'
    ];

    protected $appends = [
        'phone_number_formatted'
    ];

    const SUB_STATUSES = [
        'active'            => 'active',
        'suspend-scheduled' => 'suspend-scheduled',
        'close-scheduled'   => 'close-scheduled',
        'account-past-due'  => 'account-past-due',
        'for-restoration'   => 'for-restoration',
        'closed'            => 'closed',
        'confirm-closing'   => 'confirm-closing',
        'confirm-suspension'=> 'confirm-suspension'
    ];

    const STATUS = [
        'suspended'         => 'suspended',
        'closed'            => 'closed',
        'active'            => 'active',
    ];

    public function Customer()
    {
        return $this->hasOne('App\Model\Customer', 'id');
    }

    public function sim()
    {
        return $this->belongsTo('App\Model\Sim', 'sim_id', 'id');
    }

    public function customerRelation()
    {
        return $this->belongsTo('App\Model\Customer', 'customer_id');
    }

    public function port()
    {
        return $this->hasOne('App\Model\Port');
    }

    public function subscription_addon(){

    	return $this->hasMany('App\Model\SubscriptionAddon', 'id');
    }
    public function getNamesOfSubscriptionAddonNotRemovedAttribute()
    {
        return $this->subscriptionAddonNotRemoved->load('addons')->pluck('addons.name');
    }

    public function subscriptionAddon()
    {
        return $this->hasMany('App\Model\SubscriptionAddon', 'subscription_id', 'id');
    }

    public function subscriptionAddonNotRemoved()
    {
        return $this->subscriptionAddon()->notRemoved();
    }

    public function billableSubscriptionAddons()
    {
        return $this->subscriptionAddon()->billable();
    }

    public function invoiceItemDetail()
    {
        return $this->hasMany('App\Model\InvoiceItem', 'subscription_id', 'id');
    }

    public function invoiceItemOfTaxableServices()
    {
        return $this->invoiceItemDetail()->services()->taxable();
    }

    public function pendingCharges()
    {
      return $this->hasMany(PendingCharge::class, 'subscription_id', 'id');
    }


    public function plan()
    {
    	return $this->belongsTo('App\Model\Plan', 'plan_id', 'id');
    }

    public function device()
    {
        return $this->hasOne('App\Model\Device', 'id', 'device_id');
    }

    public function new_plan()
    {
        return $this->hasOne('App\Model\Plan', 'id' );
    }

    public function oldPlan()
    {
        return $this->belongsTo('App\Model\Plan', 'old_plan_id', 'id');
    }

    /**
     * Creating new function, and not touching original new_plan()
     * as it may be used in the application
     * @return App\Model\Plan
     */
    public function newPlanDetail()
    {
        return $this->belongsTo('App\Model\Plan', 'new_plan_id', 'id');
    }

    public function order()
    {
        return $this->belongsTo('App\Model\Order');
    }

    public function plans()
    {
        return $this->belongsTo('App\Model\Plan', 'plan_id');
    }

    public function addon()
    {
        return $this->belongsTo('App\Model\Addon' , 'id');
    }

    public function coupon()
    {
        return $this->hasOne('App\Model\coupon', 'id');
    }

    public function subscriptionCoupon()
    {
        return $this->hasMany('App\Model\SubscriptionCoupon');
    }

    public function subscriptionCouponRedeemable()
    {
        return $this->subscriptionCoupon()->redeemable();
    }

    public function scopeBillabe($query)
    {
        return $query
                ->whereIn('status', [
                  'active', 'shipping', 'for-activation'])
                ->whereNotIn('phone_number', ['', 'null'])
                ->notSuspendedOrClosed()
                ->notScheduledForSuspensionOrClosure();
    }

    public function scopeShipping($query)
    {
        return $query->where([['status', 'shipping'],['sent_to_readycloud', 0 ]]);
    }

    public function scopeShippingData($query)
    {
        return $query->where('status', 'shipping');
    }

    public function scopeNotScheduledForSuspensionOrClosure($query)
    {
      return $query->whereNotIn('sub_status', ['suspend-scheduled', 'close-scheduled']);
    }

    public function scopeNotScheduledForDowngrade($query)
    {
      return $query->where('upgrade_downgrade_status', '!=', 'downgrade-scheduled');
    }

    public function scopeNotSuspendedOrClosed($query)
    {
      return $query->whereNotIn('status', ['suspend-scheduled', 'close-scheduled']);
    }

    public function scopeTodayEqualsDowngradeDate($query)
    {
        $today = Carbon::today();
        return $query->where('downgrade_date', $today->toDateString());
    }

    public function checkGracePeriod($grace)
    {
        $today = Carbon::today();
        $date  = Carbon::parse($this->suspended_date);
        $value = $today->diffInDays($date);
        // , $value, $today
        return $value > $grace;
    }

    public function getIsStatusShippingOrForActivationAttribute()
    {
      return in_array($this->status, ['shipping', 'for-activation']);
    }

    public function getPhoneNumberFormattedAttribute()
    {
        if($this->phone_number){
            $length = strlen((string)$this->phone_number) -6;
            return preg_replace("/^1?(\d{3})(\d{3})(\d{".$length."})$/", "$1-$2-$3", $this->phone_number);  
        }
        return 'NA';
    }

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

    public function getStatusActiveAndUpgradeDowngradeStatusAttribute()
    {
        return ($this->status == 'active' && $this->upgrade_downgrade_status == 'downgrade-scheduled');
    }

    public function getShippingDateAttribute($value)
    {
        if (isset($value)) {
            return Carbon::parse($value)->format('M-d-Y');
        }
        return "NA";
    }


    public function getCalPlanChargesAttribute()
    {
        $plans = $this->invoiceItemDetail()->planCharges()->get();
        return $this->calCharges($plans);
    }


    public function getCalUsageChargesAttribute()
    {
        $invoiceItems = $this->invoiceItemDetail()->usageCharges()->get();
        return $this->calCharges($invoiceItems);
    }


    public function getCalOnetimeChargesAttribute()
    {
        $products = $this->invoiceItemDetail()->onetimeCharges()->get();
        return $this->calCharges($products);
    }

    public function getCalTaxesAttribute()
    {
        $invoiceItems = $this->invoiceItemDetail()->taxes()->get();
        return $this->calCharges($invoiceItems);
    }    


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
}