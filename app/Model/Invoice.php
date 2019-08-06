<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\libs\Constants\ConstantInterface;

class Invoice extends Model implements ConstantInterface
{

    const TYPES = [
        'monthly'   => 1,
        'one-time'  => 2
    ];

    const INVOICESTATUS = [
        'open'          => 1,
        'closed'        => 2
    ];

    protected $table = 'invoice';

    protected $fillable = [ 'customer_id', 'type', 'status', 'start_date', 'end_date', 'due_date', 'subtotal', 'total_due', 'prev_balance', 'payment_method', 'notes', 'business_name', 'billing_fname', 'billing_lname', 'billing_address_line_1', 'billing_address_line_2', 'billing_city', 'billing_state', 'billing_zip', 'shipping_fname', 'shipping_lname', 'shipping_address_line_1', 'shipping_address_line_2', 'shipping_city', 'shipping_state', 'shipping_zip', 'created_at'
	];

    protected $dates = [
        'due_date'
    ];

    protected $appends = [
        'type_description'
    ];

	public function order()
	{
		return $this->hasOne(Order::class);
	}

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

	public function invoiceItem()
   	{
        return $this->hasMany('App\Model\InvoiceItem', 'invoice_id', 'id');
    }

    public function invoiceItemOfServices()
    {
        return $this->invoiceItem()->services();
    }

    public function getCalServiceChargesAttribute()
    {
        $invoiceItems = $this->invoiceItem()->services()->get();
        return $this->calAmount($invoiceItems);
    }

    public function getCalTaxesAttribute()
    {
        $invoiceItems = $this->invoiceItem()->taxes()->get();
        return $this->calAmount($invoiceItems);
    }

    public function getCalRegulatoryAttribute()
    {
        $invoiceItems = $this->invoiceItem()->regulatory()->get();
        return $this->calAmount($invoiceItems);
    }

     public function getCalStateTaxAttribute()
    {
        $invoiceItems = $this->invoiceItem()->stateTax()->get();
        return $this->calAmount($invoiceItems);
    }


    public function getCalCreditsAttribute()
    {
        $invoiceItems = $this->invoiceItem()->credits()->get();
        return $this->calAmount($invoiceItems);
        
    }

    public function getCalPlanChargesAttribute()
    {
        $invoiceItems = $this->invoiceItem()->planCharges()->get();
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
        //array_push($total, $this->cal_credits);
        array_push($total, $this->cal_service_charges);

        $totalCharges = array_sum($total);
        return self::toTwoDecimals($totalCharges);
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
        return ($this->type != self::TYPE['monthly_invoice'] && $this->start_date > $this->customer->billing_end) ;

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
            return 'One-time invoice';
        }
        return 'Monthly invoice';
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('M-d-Y h:i A');
    }

    public function getDueDateFormattedAttribute()
    {
        if($this->due_date){
            return Carbon::parse($this->due_date)->format('M d, Y');   
        }
        return 'NA';
    }

}
