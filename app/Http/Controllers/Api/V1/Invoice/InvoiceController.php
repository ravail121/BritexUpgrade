<?php

namespace App\Http\Controllers\Api\V1\Invoice;
use Validator;
use App\Classes\GenerateMonthlyInvoiceClass;
use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Model\Invoice;
use App\Model\Customer;
use App\Model\Subscription;
use App\Model\InvoiceItem;
use App\Model\PendingCharge;
use App\Model\SubscriptionAddon;
use App\Model\Company;
use App\Model\CustomerCoupon;
use App\Model\Coupon;
use App\Model\subscriptionCoupon;
use App\Model\Tax;



class InvoiceController extends BaseController
{
	

  public function get(Request $request){

    $today = date("y-m-d");
    $fivedayaftertoday = date('Y-m-d', strtotime($today. ' + 5 days'));

  	$customers = Customer::with(['company', 'subscription', 'subscription.plan', 'subscription.new_plan', 'subscription.subscription_addon', 'subscription.subscription_addon.subscription.plan', 'pending_charge', 'invoice' , 'coupon'])->where(
            [
              ['billing_end' , '<=' , $fivedayaftertoday],
              ['billing_end', '>=', $today]
            ]
        )->whereHas('subscription', function($query)  { 
        	$query->whereIn('status', ['active', 'shipping', 'for-activation']);
        })->orWhereHas('pending_charge', function($query) {
        	$query->where('invoice_id', null);
        })->get();


  	foreach ($customers as $customer) {
  		//echo $customer;

  		// check invoice type and start_date

  		$invoice_type_1 = false;

  		if(count($customer->invoice)){
  			foreach($customer->invoice as $invoice){
  				if($invoice->type == 1 && $invoice->start_date > $customer->billing_end){
	  				$invoice_type_1 = true;
	  				break;
	  			}
  			}
	  		
	  	}


	  	if($invoice_type_1){ continue; }


	  	// Add row to invoice
	  	$_enddate = $customer->end_date;
        $start_date = date ("Y-m-d", strtotime ($_enddate ."+1 days"));
        $end_date = date ("Y-m-d", strtotime ( $start_date ."+1 months"));
        $due_date = $customer->billing_end;
        $invoice = Invoice::create([
                         'end_date'=>$start_date,
                         'start_date'=>$end_date,
                         'due_date'=>$due_date,
                         'type'=>1,
                         'status'=>1
                        ]);


	  	

        $subscriptions = $customer->subscription;
        foreach($subscriptions as $subscription){

	        	$params = [
	        		'subscription_id' => $subscription->id,
	        		'product_type' => 'plan',
	        		'type' => 1,
	        		'start_date' => $invoice->start_date

	        	];

	        	if( ($subscription->status == 'shipping' || $subscription->status == 'for-activation') ||  ($subscription->status == 'active' || $subscription->upgrade_downgrade_status == 'downgrade-scheduled')  )  {

	        		$plan = $subscription->plan;
	        		echo $plan;

	        		$params['product_id'] = $plan['id'];
	        		$params['description'] = $plan['description'];
	        		$params['amount'] = $plan['amount_recurring'];
	        		$params['taxable'] = $plan['taxable'];
	        		
	        	
	        	}else if($subscription->status == 'active' || $subscription->upgrade_downgrade_status = 'downgrade-scheduled'){
	        		$plan = $subscription->new_plan;

	        		$params['product_id'] = $plan['id'];
	        		$params['description'] = $plan['description'];
	        		$params['amount'] = $plan['amount_recurring'];
	        		$params['taxable'] = $plan['taxable'];

	        	}else{
	        		continue;
	        	}

	        	$invoice_item = InvoiceItem::create($params);
	           
	        

		        $subscription_addons = $subscription->subscription_addon;
		        $addon = [];
		        foreach($subscription_addons as $addon){

		        	if($addon['status'] == 'removal-scheduled' || $addon['status'] == 'for-removal'){
		        		continue;
		        	}
                      
		        	$params = [
		        		'subscription_id' => $addon['subscription_id'],
		        		'product_type' => 'addon',
		        		'product_id' => $addon['id'],
		        		'type' => 2,
		        		'description' => $addon['description'],
		        		'amount' => $addon['amount_recurring'],
		        		//'start_date' => $invoice->start_date,
		        		'taxable' => $addon->subscription->plan['taxable'] // Replace this with this subscription->plan->taxable
		        	];

		        	$invoice_item = InvoiceItem::create($params);

		        	//print_r($params);

                    


		        }

		        if($subscription->plan->regulatory_fee_type =1){
		        	$reg_amounts = $subscription->plan->regulatory_fee_amount;

		        }else if($subscription->plan->regulatory_fee_type =2){
		        	$reg_amount = $subscription->plan->regulatory_fee_amount;

		        }

		        $params = [ 
                  'subscription_id'=>$subscription->id,
                  'product_type'=>'' ,
                  'product_id'=>null,
                   'type'=>5,
                  //'start_date'=>
                   //'description'=>
                   'amount'=>$addon['amount_recurring'],
                   'taxable'=>0

		        ];
		        $invoice_item = InvoiceItem::create($params);

		         // echo $addon;


               
		 }


		 // echo $customer->pending_charge;

		 foreach($customer->pending_charge as $pending_charg){
		 	if($pending_charg->invoice_id ==0){
                //print_r($pending_charg);
               // echo $pending_charg;
		 	   $params = [

                    'type'=>$pending_charg['type'],
                    'amount'=>$pending_charg['amount'],
                    'description'=>$pending_charg['description']
               

		 	   ];
		 	   $invoice_item = InvoiceItem::create($params);
               
		 	}
		 	echo $invoice->id;
              //update pending charge
		 	$pendingcharge = $pending_charg->update(['invoice_id'=>$invoice->id]);
		 }

          //echo $customer->id;
		 //echo $customer->coupon->customer_coupon;
         $customer_coupon = CustomerCoupon::where('customer_id', $customer->id)->where('cycles_remaining','>' ,0)->get();
         
         //echo $customer_coupon;
         
          
          //echo $customer_coupon;

  	}

     
    return $this->respond(['Done']); //respondError
        
  }


}
