<?php

namespace App\Http\Controllers\Api\V1;

use App\Model\Plan;
use App\Model\Customer;
use App\Model\Subscription;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;

class CustomerPlanController extends BaseController
{
    public function get(Request $request){

        $InvoiceController = new InvoiceController();
        $innvoice = $InvoiceController->invoiceDetails($request);

    	$customerId = Customer::whereHash($request->hash)->first(['id']);

    	return $this->respond(['customer-invoice' =>$innvoice,'customer-plans' => $this->getSubscriptions($customerId['id'])]);
    }

    public function getSubscriptions($customerId){

    	$subscriptions = Subscription::whereCustomerId($customerId)->get();

    	foreach ($subscriptions as $key => $subscription) {
    		$subscriptions[$key]['plan'] = $subscription->plan;
    		$subscriptions[$key]['device'] = $subscription->device;
            $subscriptions[$key]['addons'] = $subscription->subscriptionAddon;
    	}
    	
    	return $subscriptions;
    }
}
