<?php

namespace App\Http\Controllers\Api\V1\Invoice;

use Validator;
use Carbon\Carbon;
use App\Model\Tax;
use App\Model\Sim;
use App\Model\Plan;
use App\Model\Order;
use App\Model\Addon;
use App\Model\Device;
use App\Model\Coupon;
use App\Model\Company;
use App\Model\Invoice;
use App\Model\Customer;
use App\Model\InvoiceItem;
use App\Model\Subscription;
use Illuminate\Http\Request;
use App\Model\PendingCharge;
use App\Model\CustomerCoupon;
use App\Events\MonthlyInvoice;
use App\Model\SubscriptionAddon;
use App\Model\SubscriptionCoupon;
use App\Model\BusinessVerification;
use App\Model\CustomerStandaloneSim;
use App\Http\Controllers\Controller;
use App\Model\CustomerStandaloneDevice;
use App\Http\Controllers\BaseController;


class MonthlyInvoiceController extends BaseController
{
    /**
     * Date-Time variable
     * @var $carbon
     */
    public $carbon;


    /**
     * Responses from various sources
     * @var $response
     */
    public $response;




    /**
     * Sets current date variable
     * 
     * @param Carbon $carbon
     */
    public function __construct(Carbon $carbon)
    {
        $this->carbon   = $carbon;
        $this->response = false;
    }



    /**
     * Generates Monthly Invoice of all Customers by checking conditions
     * 
     * @return Response
     */
    public function generateMonthlyInvoice()
    {
        $customers = Customer::whereNotNull('billing_end')->get();

        foreach ($customers as $customer) {
            if ($customer->five_days_before) {
                if ($customer->subscription) {
                    foreach ($customer->subscription as $subscription) {
                        if ($subscription->status == 'active' || $subscription->status == 'shipping' || $subscription->status == 'for-activation') {
                            $this->response = $this->triggerEvent($customer);
                        }
                    }
                } elseif ($customer->pending_charge) {
                    foreach ($customer->pending_charge as $pendingCharge) {
                        if ($pendingCharge->invoice_id == 0) {

                            $this->response = $this->triggerEvent($customer);
                            
                        }
                    }
                    
                }
            }

        }
        return $this->respond($this->response);
    }


    /**
     * Sends mail through MonthlyInvoice event
     * 
     * @param  Customer   $customer
     * @return Response
     */
    protected function triggerEvent($customer)
    {
        if ($customer->invoice) {
            foreach ($customer->invoice as $invoice) {
                if ($invoice->type_not_one) {
                    $this->response = event(new MonthlyInvoice($customer));
                }
            }
        }
        return $this->response;
        
    }
}
