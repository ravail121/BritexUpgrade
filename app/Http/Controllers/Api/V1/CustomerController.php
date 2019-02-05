<?php

namespace App\Http\Controllers\Api\V1;

use Validator;
use App\Model\Order;
use App\Model\Customer;
use App\Model\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Model\SubscriptionAddon;
use Illuminate\Support\Collection;
use App\Model\BusinessVerification;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\BaseController;



class CustomerController extends BaseController
{

  public function __construct(){
    $this->content = array();
  }
   
  public function post(Request $request)
  {
    $hasError = $this->validateData($request);
    if($hasError){
      return $hasError;
    }

    $data         = $request->all();
    $order        = Order::hash($data['order_hash'])->first();
    $customerData = $this->setData($order, $data);

    $customer = Customer::updateOrCreate(['id' => $order->customer_id], $customerData);

    if (!$customer) {
      return $this->respondError("problem in creating/updating a customer");
    }
    $order->update(['customer_id' => $customer->id]);

    return response()->json(['success' => true, 'customer' => $customer]); 
  }


  public function subscription_list(Request $request){

      $output       = ['success' => false , 'message' => ''];
      
      $company      = \Request::get('company');
      $customer_id  = $request->input(['customer_id']);
      $validation   = Validator::make($request->all(),[
      'customer_id' => 'numeric|required']);

    if($validation->fails()){
      $output['message'] = $validation->getMessageBag()->all();
      return response()->json($output);
    }

    $customer = Customer::where('id', $customer_id)->get();
    $customer = $customer[0];

    if($customer->company_id != $company->id){
      return Response()->json(array('error' => [' customer id does not exist']));
    }

    $data = Subscription::with(['SubscriptionAddon'])->where('customer_id', $customer_id)->get();
    $output['success'] = true;
    return response()->json($data);
  }



  /**
   * This function sets some data for creating a customer
   * 
   * @param Class   $order
   * @param array   $data
   * @return array
   */
  protected function setData($order, $data)
  {
    unset($data['order_hash']);
    
    $data['business_verification_id'] = $order->bizVerification->id;
    $data['business_verified']        = $order->bizVerification->approved;
    $data['company_id']               = $order->company_id;
    $data['password']                 = Hash::make($data['password']);
    $data['hash']                     = sha1(time());
    $data['pin']                      = Hash::make($data['pin']);

    return $data;
  }




  /**
   * Validates the Create-customer data
   * 
   * @param  Request $request
   * @return Response
   */
  protected function validateData($request) { 
    
    return $this->validate_input($request->all(), [ 
      'fname'              => 'required|string',
      'lname'              => 'required|string',
      'email'              => 'required|email',
      'company_name'       => 'required|string',
      'phone'              => 'required|string',
      'alternate_phone'    => 'nullable|string',
      'password'           => 'required|string',
      'shipping_address1'  => 'required|string',
      'shipping_address2'  => 'nullable|string',
      'shipping_city'      => 'required|string',
      'shipping_state_id'  => 'required|string|max:2',
      'shipping_zip'       => 'required|digits:5',
      'pin'                => 'required|digits:4',
    ]);
  }

  public function details(Request $request){

    $customer = Customer::where(['hash'=>$request->hash])->first();
    return $customer;
  }

  public function update(Request $request){
    $data    = $request->all();
    $validation = Validator::make($data, [
        'fname'             => 'sometimes|required',
        'lname'             => 'sometimes|required',
        'email'             => 'sometimes|required|email',
        'billing_address1'  => 'sometimes|required',
        'billing_city'      => 'sometimes|required',
    ]);
    if ($validation->fails()) {
      return $this->respondError("Validation Failed");
    }
    Customer::wherehash($data['hash'])->update($data);
      return response()->json('sucessfully Updated');
  }
}
