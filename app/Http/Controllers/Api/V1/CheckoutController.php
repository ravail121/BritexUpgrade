<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CheckoutController extends Controller
{
   $invalidate = $this->validate_input($data, [
		   'order_hash'=>'required|string',
		   'company_id'=>'required|numeric',
		   'business_verification_id'=>'required|numeric',
		   'subscribtion_start_date'=> 'required|date',
		   'billing_start'=> 'required|date',
		   'billing_end'=>'required|date',
		   'primary_payment_method'=>'required|numeric',
		   'primary_payment_card '=>'required|numeric',
		   'account_suspended '=>'required',
		   'billing_address1'=>'required',
		   'billing_address2 '=>'required',
		   'billing_city '=>'required',
		   'billing_state_id' => 'required|string',
		   'shipping_address1 ' => 'required|string'
		   'shipping_address2 ' => 'required|string'
		   'shipping_city ' => 'required|string'
		   'shipping_state_id ' => 'required'
   
	]);

   	Customer::create($invalidate);

}
