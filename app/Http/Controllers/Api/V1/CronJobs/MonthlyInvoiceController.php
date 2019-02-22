<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use App\Model\Tax;
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
use App\Events\MonthlyInvoice;
use App\Model\SubscriptionAddon;
use App\Model\SubscriptionCoupon;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;

class MonthlyInvoiceController extends BaseController
{


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
    public function __construct()
    {
        $this->response = ['error' => 'Email was not sent'];
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
            if ($customer->five_days_before) {  // $today->greaterThanOrEqualTo($fiveDaysBefore) is used instead of $today->lessThanOrEqualTo($fiveDaysBefore)
                if ($customer->subscription) {
                    foreach ($customer->subscription as $subscription) {
                        if ($subscription->status == 'active' || $subscription->status == 'shipping' || $subscription->status == 'for-activation') {
                            $this->response = $this->triggerEvent($customer);
                            break;
                        }
                    }
                } elseif ($customer->pending_charge) {
                    foreach ($customer->pending_charge as $pendingCharge) {
                        if ($pendingCharge->invoice_id == 0) {

                            $this->response = $this->triggerEvent($customer);
                            break;
                            
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
                    break;
                }
            }
        }
        return $this->response;
        
    }
}
