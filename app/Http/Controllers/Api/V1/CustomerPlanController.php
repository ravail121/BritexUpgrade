<?php

namespace App\Http\Controllers\Api\V1;

use App\Model\Subscription;
use App\Model\Plan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CustomerPlanController extends Controller
{
    public function get(Request $request){
    	$subscriptions = Subscription::whereCustomerId($request->id)->get();
    	foreach ($subscriptions as $key => $subscription) {
    		$plan[$key]= Plan::whereId($subscriptions[$key]['plan_id'])->first();
    		$subscriptions[$key]['plan'] = $plan[$key];
    	}
    	return $subscriptions;
    	// dd($subscriptions[1]['plan']['amount_recurring']);
    }
}
