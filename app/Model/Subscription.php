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
    ];

    protected $dates = [
        'account_past_due_date',
        'scheduled_suspend_date',
        'scheduled_close_date'
    ];

    const SUB_STATUSES = [
        'active'            => 'active',
        'suspend-scheduled' => 'suspend-scheduled',
        'close-scheduled'   => 'close-scheduled',
        'account-past-due'  => 'account-past-due',
        'for-restoration'   => 'for-restoration',
        'closed'            => 'closed',
        'confirm-closing'   => 'confirm-closing'
    ];

    public function Customer()
    {
        return $this->hasOne('App\Model\Customer', 'id');
    }

    public function subscription_addon(){

    	return $this->hasMany('App\Model\SubscriptionAddon', 'id');
    }

    public function subscriptionAddon()
    {
        return $this->hasMany('App\Model\SubscriptionAddon', 'subscription_id', 'id');
    }

    public function invoiceItemDetail()
    {
        return $this->hasMany('App\Model\InvoiceItem', 'subscription_id', 'id');
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
        return $this->hasone('App\Model\Plan', 'id' );
    }

    public function order()
    {
        return $this->belongsTo('App\Model\Order');
    }

    public function plans()
    {
        return $this->belongsTo('App\Model\plan', 'plan_id');
    }

    public function addon()
    {
        return $this->belongsTo('App\Model\Addon' , 'id');
    }
    public function subscription_coupon()
    {
        return $this->belongsTo('App\Model\SubscriptionCoupon', 'subscription_id');
    }

    public function scopeBillabe($query)
    {
        return $query->where('status', 'active')
                      ->orWhere('status', 'shipping')
                      ->orWhere('status', 'for-activation');
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
        return $value > $grace;
    }

    public function getStatusShippingOrForActivationAttribute()
    {
        return ($this->status == 'shipping' || $this->status == 'for-activation');
    }

    public function getStatusActiveNotUpgradeDowngradeStatusAttribute()
    {
        return ($this->status == 'active' && $this->upgrade_downgrade_status != 'downgrade-scheduled');
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


    public function getCalOnetimeChargesAttribute()
    {
        $products = $this->invoiceItemDetail()->onetimeCharges()->get();
        return $this->calCharges($products);
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