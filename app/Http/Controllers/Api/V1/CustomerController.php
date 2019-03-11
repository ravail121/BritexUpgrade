<?php

namespace App\Http\Controllers\Api\V1;

use Validator;
use App\Model\Order;
use App\Model\Customer;
use App\Model\OrderGroup;
use App\Model\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Model\OrderGroupAddon;
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
    if ($request->customer_id) {
      $done = $this->updateOrder($request);
      if (!$done) {
        return $this->respondError('Customer was not created.');
      }
      return $this->respond(['success' => true]);

    }
    $hasError = $this->validateData($request);
    if($hasError){
      return $hasError;
    }


    $data  = $request->all();
    $order = Order::hash($data['order_hash'])->first();


    $customerData = $this->setData($order, $data);

    $customer = Customer::create($customerData);

    if (!$customer) {
      return $this->respondError("problem in creating/updating a customer");
    }
    $order->update(['customer_id' => $customer->id]);

    return $this->respond(['success' => true, 'customer' => $customer]); 
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

    if ($order->bizVerification) {
      $data['business_verification_id'] = $order->bizVerification->id;
      $data['business_verified']        = $order->bizVerification->approved;

    } elseif ($order->company->business_verification == 0) {
      $data['business_verification_id'] = null;
      $data['business_verified']        = 1;

    }
    
    
    $data['company_id'] = $order->company_id;
    $data['password']   = Hash::make($data['password']);
    $data['hash']       = sha1(time());
    $data['pin']        = $data['pin'];

    return $data;
  }



  protected function updateOrder($request)
  {
    if ($request->customer_id) {
      $order = Order::hash($request->order_hash)->first();
      $order->update(['customer_id' => $request->customer_id]);
      return $order;
       
    }
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




  /**
   * Get customer Details
   * 
   * @param  Request   $request
   * @return Response
   */
  public function customerDetails(Request $request)
  {
    $msg = $this->respond(['error' => 'Hash is required']);
    if ($request->hash) {
      $customer = Customer::where(['hash' => $request->hash])->first();
      if ($customer) {
        $msg = $this->respond($customer);

      } else {
        $msg = $this->respond(['error' => 'customer not found']);

      }
    }
    return $msg;
  }




  /**
   * Updates customer details
   * 
   * @param  Request    $request
   * @return Response   json
   */
  public function update(Request $request)
  {
    $data    = $request->all();
    $validation = $this->validateUpdate($data);
    if ($validation) {
      return $validation;
    }
    if(isset($data['password'])){
        $currentPassword = Customer::whereHash($data['hash'])->first();

        if(Hash::check($data['old_password'], $currentPassword['password'])){
            $password['password'] = bcrypt($data['password']);
            Customer::whereHash($data['hash'])->update($password);
            return $this->respond('sucessfully Updated');    
        }
        else{
            return $this->respondError('Incorrect Current Password');
        }
    } 
    Customer::whereHash($data['hash'])->update($data);
    return $this->respond(['message' => 'sucessfully Updated']);
  }




  /**
   * Validates the data
   * 
   * @param  array      $data
   * @return Response   validation response
   */
  protected function validateUpdate($data) 
  {
    $id = $data['id'];
    return $this->validate_input($data, [
        'fname'             => 'sometimes|required',
        'lname'             => 'sometimes|required',
        'email'             => 'sometimes|required|unique:customer,email,'.$id,
        'billing_address1'  => 'sometimes|required',
        'billing_city'      => 'sometimes|required',
        'password'          => 'sometimes|required|min:6',
        'hash'              => 'required',
        'shipping_address1' => 'sometimes|required',
        'shipping_city'     => 'sometimes|required',
        'shipping_zip'      => 'sometimes|required',
        'phone'             => 'sometimes|required',
        'pin'               => 'sometimes|required',
    ]);
  }

    public function checkEmail(Request $request){
        $data =  $request->validate([
            'newEmail'   => 'required',
            'hash'       => 'required',

        ]);

        $emailCount = Customer::where('email', '=' , $request->newEmail)->where('hash', '!=' , $request->hash)->count();
        // $emailCount = Customer::where('email', '=' , $request->newEmail)->count();
        return $this->respond(['emailCount' => $emailCount]);
    }

    public function checkPassword(Request $request)
    {
        $data =  $request->validate([
            'hash'     => 'required',
            'password' => 'required',
        ]);

        $currentPassword = Customer::whereHash($request->hash)->first();

        if(Hash::check($request->password, $currentPassword['password'])){
            return $this->respond(['status' => 0]);
        }
        else{
            return $this->respond(['status' => 1]);
        }
    }

    public function customerOrder(Request $request)
    {
        $data =  $request->validate([
            'hash'       => 'required',
        ]);
        $customer = Customer::whereHash($request->hash)->first();

        $billingDetails = Customer::with('creditAmount','invoice.order','orders.allOrderGroup.plan.subscription', 'orders.allOrderGroup.device.customerStandaloneDevice','orders.allOrderGroup.sim.customerStandaloneSim','orders.allOrderGroup.order_group_addon.addon','orders.invoice')->find($customer['id']);
        
        return $this->respond($billingDetails);
    }  
}
