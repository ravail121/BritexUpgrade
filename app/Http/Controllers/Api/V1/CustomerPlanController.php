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

    	$customerId = Customer::whereHash($request->hash)->first(['id']);

    	return $this->getSubscriptions($customerId['id']);
    }

    public function getSubscriptions($customerId){

    	$subscriptions = Subscription::whereCustomerId($customerId)->get();

    	foreach ($subscriptions as $key => $subscription) {
    		$subscriptions[$key]['plan'] = $subscription->plan;
    		$subscriptions[$key]['device'] = $subscription->device;
    	}
    	
    	return $this->respond($subscriptions);
    }
}
