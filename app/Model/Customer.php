<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Events\InvoiceGenerated;

class Customer extends Authenticatable
{

    use Notifiable;

    const AUTO_PAY = [
        'disable'    =>  0,
        'enable'     =>  1,
    ];
    
    protected $table = 'customer'; 
    protected $fillable = [
        'hash',  
        'company_id',
        'business_verification_id',
        'business_verified',
        'fname',
        'lname',
        'password',
        'phone',
        'alternate_phone',
        'pin',
        'email',
        'company_name',
        'subscription_start_date',
        'billing_start',
        'billing_end',
        'primary_payment_method',
        'primary_payment_card',
        'account_suspended',
        'billing_address1',
        'billing_address2', 
        'billing_city',
        'billing_state_id',
        'billing_zip',
        'shipping_address1',
        'shipping_address2',
        'shipping_city',
        'shipping_state_id',
        'shipping_zip',
        'shipping_fname',
        'shipping_lname',
        'billing_fname',
        'billing_lname',
        'auto_pay'
    ];

    protected $attributes = [
        'auto_pay' => 0,
    ];

    public function company()
    {
    	return $this->hasOne('App\Model\Company', 'id', 'company_id');
    }

    public function subscription()
    {
        return $this->hasMany('App\Model\Subscription', 'customer_id', 'id');
    }

    public function openMonthlyInvoice()
    {
        return $this->hasOne(Invoice::class)->monthly()->pendingPayment();
    }

    public function billableSubscriptions()
    {
        return $this->subscription()->billabe();
    }

    public function pending_charge()
    {
        return $this->hasMany('App\Model\PendingCharge', 'customer_id', 'id');
    }

    public function pendingChargesWithoutInvoice()
    {
        return $this->pending_charge()->withoutInvoice();
    }

    public function generatedInvoiceOfNextMonth()
    {
        return $this->invoice()->monthly()->afterDate(Carbon::parse($this->billing_end));
    }

    public function advancePaidInvoiceOfNextMonth()
    {
        return $this->generatedInvoiceOfNextMonth()->closedAndPaid();
    }

    public function invoice()
    {
     return $this->hasMany('App\Model\Invoice');
    }

    public function orders()
    {
        return $this->hasMany('App\Model\Order')->completeOrders();
    }
    
    public function order()
    {
        return $this->belongsTo('App\Model\Order');
    }

     public function scopeHash($query, $hash)
    {
        return $query->where('hash', $hash)->first();
    }

     // public function pending_charge(){
     // 	return $this->belongsTo('App\Model\PendingCharge' , 'customer_id');
     // }
    public function tax()
    {
     	return $this->belongsTo('App\Model\Tax' , 'company_id');
     }
    public function coupon()
    {
        return $this->hasOne('App\Model\coupon', 'id');
    }

    public function customerCoupon()
    {
        return $this->hasMany('App\Model\CustomerCoupon');
    }

    public function customerCouponRedeemable()
    {
        return $this->customerCoupon()->redeemable();
    }

    public function BizVerification()
    {
        return $this->belongsTo('App\Model\BusinessVerification');
    }

    public function customerCreditCards()
    {
        return $this->hasMany('App\Model\CustomerCreditCard');
    }

    public function creditAmount()
    {
        return $this->hasMany('App\Model\Credit','customer_id');
    }

    public function credit()
    {
        return $this->hasMany('App\Model\Credit');
    }

    public function creditsNotAppliedCompletely()
    {
        return $this->credit()->notAppliedCompletely();
    }

    public function stateTax()
    {
        return $this->belongsTo('App\Model\Tax', 'billing_state_id', 'state')->where('tax.company_id', $this->company_id);
    }


    public function getFullNameAttribute()
    {
        return $this->fname.' '.$this->lname;
    }

    public function getZipAddressAttribute()
    {
        return $this->shipping_city.', '.$this->shipping_state_id.' '.$this->shipping_zip;
    }

    public static function customerInvoiceGroups()
    {
        $customers          = self::whereNotNull('billing_end')
                                    ->whereDate('billing_end', '>=', Carbon::today()->format('Y-m-d'))->get();

        $invoices           = [];
        
        foreach ($customers as $customer) {
            $invoice = $customer->invoice;
            
            if (count($invoice)) {
                $invoices[] = $invoice;
            }
        }
        
        return $invoices;
    }

    public static function oneTimeInvoiceAfterMonthly()
    {
        $customers          = self::whereNotNull('billing_end')->get();
        $monthlyInvoice     = [];
        $customerId         = '';
        $customer           = [];
        foreach ($customers as $customer) {
            $monthlyInvoice = $customer->invoice
                                ->where('type', 1)
                                ->where('status', 1)->first();
                                
            if ($monthlyInvoice) {
               
                $customerId = $customer->invoice()
                                    ->where('type', 2)
                                    ->where('status', 2)
                                    ->where('created_at', '>', Carbon::parse($monthlyInvoice->created_at)->format('y-m-d H:i:s'))->first();
                                   
                if ($customerId) {
                    $customerIds[] = $customerId->customer_id;
                }

            }
                                
        }

        return $customerIds;
    }

    public static function shouldBeGeneratedNewInvoices()
    {
        $today     = self::currentDate();
        
        $customers = self::whereNotNull('billing_end')
                        // doesntHave needs to be before
                        // any other where condition
                        ->doesntHave('generatedInvoiceOfNextMonth')
                        ->whereHas('billableSubscriptions')
                        ->orWhereHas('pendingChargesWithoutInvoice')
                        ->with([
                            'billableSubscriptions',
                            'pendingChargesWithoutInvoice',
                            'openMonthlyInvoice'
                        ])
                        ->get();

        $customers = $customers->filter(function($customer, $i) use ($today){
            $billingEndParsed = Carbon::parse($customer->billing_end);
            $billingEndFiveDaysBefore   = $billingEndParsed->copy()->subDays(5);
            if ($today > $billingEndParsed) {
                $invoiceGenerated = $customer->invoice
                                    ->where('type', 1)
                                    ->where('status', 1);
                                    
                if (!count($invoiceGenerated)) {
                    return 
                        $today >= $billingEndFiveDaysBefore &&
                        $today > $billingEndParsed;
                }
            }
            // Is today between customer.billing_date and -5 days
            return 
                $today >= $billingEndFiveDaysBefore &&
                $today <= $billingEndParsed;
        });

        return $customers;
    }

    /**
     * For test accounts only
     * Customers should always have this value
     * @param  [type] $value
     * @return [type]       
     */
    public function getBillingAddress1Attribute($value)
    {
        return $value ?: 'N/A';
    }

    /**
     * For test accounts only
     * Customers should always have this value
     * @param  [type] $value
     * @return [type]       
     */
    // public function getShippingAddress2Attribute($value)
    // {
    //     return $value ?: 'N/A';
    // }

    /**
     * For test accounts only
     * Customers should always have this value
     * @param  [type] $value
     * @return [type]       
     */
    public function getBillingCityAttribute($value)
    {
        return $value ?: 'N/A';
    }

    /**
     * For test accounts only
     * Customers should always have this value
     * @param  [type] $value
     * @return [type]       
     */
    // Should be Commented the function as using it in where queries was
    // also creating problem
    public function getBillingStateIdAttribute($value)
    {
        return $value ?: 'N/A';
    }

    /**
     * For test accounts only
     * Customers should always have this value
     * @param  [type] $value
     * @return [type]       
     */
    public function getBillingZipAttribute($value)
    {
        return $value ?: 'N/A';
    }

    public function getIsTodayFiveDaysBeforeBillingAttribute()
    {
        $today          = self::currentDate();
        $endDate        = $this->parseEndDate();
        $fiveDaysBefore = $endDate->subDays(5);

        return ($today->gte($fiveDaysBefore) && $today->lte($endDate));
    }


    public function getTodayGreaterThanBillingEndAttribute()
    {
        $today    = self::currentDate();
        $endDate  = $this->parseEndDate();
        return $today->gt($endDate);

    }

    public function getAddDayToBillingEndAttribute()
    {
        $endDate = $this->parseEndDate();
        return $endDate->addDay()->toDateString();

    }

    public function getAddMonthToBillingEndAttribute()
    {
        $endDate = $this->parseEndDate();
        return $endDate->addMonth()->toDateString();

    }


    public static function currentDate()
    {
        return Carbon::today();
    }


    public function parseEndDate($billingEnd = null)
    {
        return Carbon::parse($billingEnd ?: $this->billing_end);
    }

    public function getBillingStartDateFormattedAttribute()
    {
        if($this->billing_start){
            return Carbon::parse($this->billing_start)->format('M d, Y');   
        }
        return 'NA';
    }

    public function getBillingEndDateFormattedAttribute()
    {
        if($this->billing_end){
            return Carbon::parse($this->billing_end)->format('M d, Y');   
        }
        return 'NA';
    }
}
