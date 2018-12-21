<?php

namespace App\Http\Controllers\Api\V1;
use Validator;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Model\Customer;
use App\Model\Subscription;
use App\Model\SubscriptionAddon;

class CustomerController extends Controller
{
    public function __construct(){
        $this->content = array();
    }

    public function post(Request $request){
    	$validation = Validator::make($request->all(),[
          'order_hash'=>'string|required',
          'user_name'=> 'string|required',
          'password'=>'string|required',
          'fname'=>'string|required',
          'lname'=>'string|required',
          'email'=>'string|required',
          'business_verfied'=>'numeric',
          'business_verification_id'=>'numeric',
          'company_name'=>'string|required',
          'pin'   =>'numeric|required',
          'billing_address1'=>'string|required',
          'billing_address2'=>'string|required',
          'billing_city'=>'string|required',
          'billing_state_id'=>'string|required',
          'shipping_address1'=>'string|required',
          'shipping_address2'=>'string',
          'shipping_city'=>'string|required',
          'shipping_state_id'=>'string|required'

    	]);
    	if($validation->fails()){
    		return response()->json($validation->getMessageBag()->all());
    	}
    	$data= $request->all();
    	$customer = Customer::create([
         'billing_address1' => $data['billing_address1'],
         'billing_address2' => $data['billing_address2'],
         'billing_city' => $data['billing_city'],
         'billing_state_id' => $data['billing_state_id'],
         'shipping_address1' => $data['shipping_address1'],
         'shipping_address2' => $data['shipping_address2'],
         'shipping_city' => $data['shipping_city'],
         'shipping_state_id'=> $data['shipping_state_id'],
          'hash'=>sha1(time()) 
    	]);
        
       return response()->json(['success'=> true, 'customer'=> $customer]);
    }



    public function subscription_list(Request $request){

      $output = ['success' => false , 'message' => ''];

      $company = \Request::get('company');
      $customer_id = $request->input(['customer_id']);
      $validation = Validator::make($request->all(),[
         'customer_id'=>'numeric|required'

      ]);
      if($validation->fails()){
        $output['message'] = $validation->getMessageBag()->all();
        return response()->json($output);
   
      }

      $customer = Customer::where('id', $customer_id)->get();
      $customer = $customer[0];

      if($customer->company_id != $company->id){
        return Response()->json(array('error'=>[' customer id does not exist']));
      }

       $data = Subscription::with(['SubscriptionAddon'])->where('customer_id', $customer_id)->get();
       $output['success'] = true;
       return response()->json($data);
    }



}
