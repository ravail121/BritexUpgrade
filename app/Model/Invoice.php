<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\libs\Constants\ConstantInterface;

/**
 * Class Invoice
 *
 * @package App\Model
 */
class Invoice extends Model implements ConstantInterface
{

	/**
	 *
	 */
	const TYPES = [
        'monthly'   => 1,
        'one-time'  => 2
    ];

	/**
	 *
	 */
	const INVOICESTATUS = [
        'closed&upaid'  => 0,
        'open'          => 1,
        'closed'        => 2,
        'closed&paid'   => 2,
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
	 * @var string
	 */
	protected $table = 'invoice';

	/**
	 * @var string[]
	 */
	protected $fillable = [
		'customer_id',
		'type',
		'status',
		'start_date',
		'end_date',
		'due_date',
		'subtotal',
		'total_due',
		'prev_balance',
		'payment_method',
		'notes',
		'business_name',
		'billing_fname',
		'billing_lname',
		'billing_address_line_1',
		'billing_address_line_2',
		'billing_city',
		'billing_state',
		'billing_zip',
		'shipping_fname',
		'shipping_lname',
		'shipping_address_line_1',
		'shipping_address_line_2',
		'shipping_city',
		'shipping_state',
		'shipping_zip',
		'created_at',
		'staff_id'
	];

	/**
	 * @var string[]
	 */
	protected $dates = [
        'due_date'
    ];

	/**
	 * @var string[]
	 */
	protected $appends = [
        'type_description', 'created_at_formatted_with_time'
    ];

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasOne
	 */
	public function order()
	{
		return $this->hasOne(Order::class);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function invoiceItem()
   	{
        return $this->hasMany('App\Model\InvoiceItem', 'invoice_id', 'id');
    }

	/**
	 * @return mixed
	 */
	public function invoiceItemOfServices()
    {
        return $this->invoiceItem()->services();
    }

	/**
	 * @return string
	 */
	public function getCalServiceChargesAttribute()
    {
        $invoiceItems = $this->invoiceItem()->services()->get();
        return $this->calAmount($invoiceItems);
    }

	/**
	 * @return string
	 */
	public function getCalTaxesAttribute()
    {
        $invoiceItems = $this->invoiceItem()->taxes()->get();
        return $this->calAmount($invoiceItems);
    }

	/**
	 * @return string
	 */
	public function getCalRegulatoryAttribute()
    {
        $invoiceItems = $this->invoiceItem()->regulatory()->get();
        return $this->calAmount($invoiceItems);
    }

	/**
	 * @return string
	 */
	public function getCalStateTaxAttribute()
    {
        $invoiceItems = $this->invoiceItem()->stateTax()->get();
        return $this->calAmount($invoiceItems);
    }

	/**
	 * @return string
	 */
	public function getCalCreditsAttribute()
    {
        $invoiceItems = $this->invoiceItem()->credits()->get();
        return $this->calAmount($invoiceItems);
        
    }

	/**
	 * @return string
	 */
	public function getCalPlanChargesAttribute()
    {
        $invoiceItems = $this->invoiceItem()->planCharges()->get();
        return $this->calAmount($invoiceItems);
    }

	/**
	 * @return string
	 */
	public function getCalPlanOnlyChargesAttribute()
    {
        $invoiceItems = $this->invoiceItem()->planOnlyCharges()->get();
        return $this->calAmount($invoiceItems);
    }

    public function getCalOnetimeAttribute()
    {
        $invoiceItems = $this->invoiceItem()->onetimeCharges()->get();
        return $this->calAmount($invoiceItems);        
    }

    public function getCalUsageChargesAttribute()
    {
        $invoiceItems = $this->invoiceItem()->usageCharges()->get();
        return $this->calAmount($invoiceItems);
    }

    public function getCalTotalChargesAttribute()
    {
        $total = [];
        array_push($total, $this->cal_taxes);
        array_push($total, $this->cal_service_charges);
        $discount = $this->cal_credits;

        $totalCharges = array_sum($total);
        return self::toTwoDecimals($totalCharges - $discount);
    }

    public function creditsToInvoice()
    {
        return $this->hasMany('App\Model\CreditToInvoice');
    }

    protected function calAmount($invoiceItems)
    {
        $amount = [];
        foreach ($invoiceItems as $invoiceItem) {
            array_push($amount, $invoiceItem->amount);
        }
        $total = array_sum($amount);
        return self::toTwoDecimals($total);
    }

    public function scopeMonthly($query)
    {
        return $query->where('type', self::INVOICE_TYPES['monthly']);
    }

    public function scopeOnetime($query)
    {
        return $query->where('type', self::INVOICE_TYPES['one_time']);
    }

    public function scopeClosedAndUnpaid($query)
    {
        return $query->where('status', self::STATUS['closed_and_unpaid']);
    }

    public function scopePendingPayment($query)
    {
        return $query->where('status', self::STATUS['pending_payment']);
    }


    public function scopeClosedAndPaid($query)
    {
        return $query->where('status', self::STATUS['closed_and_paid']);
    }

    public function scopeAfterDate($query, $date)
    {
        return $query->where('start_date', '>', $date);
    }

    public function scopePendingAndUnpaid($query)
    {
        return $query->whereIn('status', [
            self::STATUS['pending_payment'],
            self::STATUS['closed_and_unpaid'],
        ]);
    }

    public function scopeOpenAndUnpaid($query)
    {
        return $query->where('type', self::TYPES['monthly'])
                        ->where('status', self::INVOICESTATUS['open']);
    }

    /**
     * Fetches Invoice Details from invoice table if status < 2
     * 
     * @param  Query   $query      
     * @param  int     $customerId
     * @return query
     */
    public function scopeGetDues($query, $customerId)
    {
        return $query->where('customer_id' , $customerId)->pendingAndUnpaid();
    }

    public function scopeMonthlyInvoicePending($query)
    {
        return $query->monthly()->pendingPayment();
    }

    public function scopeMonthlyInvoicePaid($query)
    {
        return $query->monthly()->closedAndPaid();
    }

    public function scopeMonthlyInvoiceClosedAndUnpaid($query)
    {
        return $query->monthly()->closedAndUnpaid();
    }

    public function scopeOverDue($query)
    {
        return $query->where('due_date', '<', self::currentDate());
    }

    /**
     * Returns total_due amount with 2 decimal places
     * 
     * @return float 
     */
    public function getTotalAttribute()
    {
        return self::toTwoDecimals($this->total_due);
    }

    public function getTypeNotOneAttribute()
    {
        return ($this->type != self::TYPES['monthly_invoice'] && $this->start_date > $this->customer->billing_end) ;

    }

    public function getSubTotalAmountAttribute()
    {
        $sub_total = 0;

        if ($this->start_date == $this->customer->billing_start) {
            $sub_total = $this->subtotal;
        }
        return self::toTwoDecimals($sub_total);
    }

    public function getPastDueAttribute()
    {
        $pastDue = 0;

        if ($this->status == 0 && strtotime($this->start_date) < strtotime($this->customer->billing_start)) {

            $pastDue = $this->subtotal;
        }
        return self::toTwoDecimals($pastDue);
    }

    public function getTodayGreaterThanDueDateAttribute()
    {
        $today   = self::currentDate();
        $dueDate = Carbon::parse($this->due_date);
        return $today->gt($dueDate);
    }


    public static function currentDate()
    {
        return Carbon::today();
    }

    public function creditToInvoice()
    {
        return $this->hasMany('App\Model\CreditToInvoice', 'invoice_id', 'id');
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

    public function getTypeDescriptionAttribute()
    {
        $value = $this->type;
        if ($this->type == 2) {
            if ($this->invoiceItem()->where('product_type', 'refund')->count()) {
                return 'Refund';    
            }
            return 'One-time invoice';
        }
        return 'Monthly invoice';
    }

    // public function getCreatedAtAttribute($value)
    // {
    //     return Carbon::parse($value)->format('M-d-Y h:i A');
    // }

    public function getDueDateFormattedAttribute()
    {
        if($this->due_date){
            return Carbon::parse($this->due_date)->format('M d, Y');   
        }
        return 'NA';
    }

    public function getCreatedAtFormattedAttribute()
    {
        if($this->created_at){
            return Carbon::parse($this->created_at)->format('M d, Y');   
        }
        return 'NA';
    }

    public static function dateFormatForInvoice($date)
    {
        return Carbon::parse($date)->toFormattedDateString();
    }

    public static function standAloneTotal($id)
    {
		$invoice = self::find($id)->invoiceItem->where('subscription_id', null);
        $total = $invoice
            ->where('type', '!=', self::InvoiceItemTypes['coupon'])
            ->where('type', '!=', self::InvoiceItemTypes['manual'])
            ->sum('amount');

        $discounts = $invoice->whereIn('type', 
        [
            self::InvoiceItemTypes['coupon'], 
            self::InvoiceItemTypes['manual']
        ])->sum('amount');

        return $total - $discounts;
    }

    public function couponUsed()
    {
        return $this->invoiceItem()->usedCoupon();
    }

    public function refundLog()
    {
        return $this->hasOne('App\Model\PaymentRefundLog');
    }

    public function paymentLog()
    {
        return $this->hasOne('App\Model\PaymentLog');
    }

    public function getCreatedAtFormattedWithTimeAttribute()
    {
        if($this->created_at){
            return Carbon::parse($this->created_at)->format('M-d-Y h:i A');   
        }
        return 'NA';
    }

    public static function onlyAddonItems($id)
    {
        $invoice = Invoice::find($id);
        $plans = $invoice->invoiceItem->where('type', self::InvoiceItemTypes['plan_charges'])->count();
        $addons = $invoice->invoiceItem->where('type', self::InvoiceItemTypes['feature_charges'])->count();
        return !$plans && $addons ? true : false;
    }

    public static function boot()
    {
        parent::boot();
        static::deleting(function($invoice) {
            $invoice->order()->delete();
            $invoice->invoiceItem()->delete();
            $invoice->creditsToInvoice()->delete();
        });
    }

    public function withSubscription()
    {
        return $this->invoiceItem()->withSubscription();
    }
    
    public function refundInvoiceItem()
    {
        return $this->invoiceItem()->refundItem();
    }

    public function scopeCustomerInvoice($query)
    {
        return $query->has('customer');
    }

	/**
	 * @return string
	 */
	public function getCalSurchargeAttribute()
	{
		$invoiceItems = $this->invoiceItem()->surcharge()->get();
		return $this->calAmount($invoiceItems);
	}


	/**
	 * @return string
	 */
	public function getCalSubtotalAttribute()
	{
		$subTotal = $this->subtotal;

		$subTotalWithoutSurcharge = $subTotal - $this->cal_surcharge;

		return self::toTwoDecimals($subTotalWithoutSurcharge);

	}

	public function getCalUsedCouponDiscountAttribute(){
		$invoiceItems = $this->invoiceItem()->usedCoupon()->get();
		return $this->calAmount($invoiceItems);
	}

}
