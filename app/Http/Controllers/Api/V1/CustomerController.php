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
use App\Model\CustomerStandaloneSim;
use App\Model\CustomerStandaloneDevice;
use App\Http\Controllers\BaseController;



class CustomerController extends BaseController
{

  public function __construct(){
    $this->content = array();
  }
   
  public function post(Request $request)
  {

    if ($request->customer_id) {
      
        if($request->fname){

            $customer = $this->updateCustomer($request);

            return $this->respond(['success' => true, 'customer' => $customer]);          
        }

        $order = $this->updateOrder($request);
        if (!$order) {

            return $this->respondError('Customer was not created.');
        }

        return $this->respond(['success' => true]);

    }
   
    $hasError = $this->validateData($request);
    if ($hasError) {
      
      return $hasError;
    }


    $data  = $request->except('_url');
    
    $order = Order::hash($data['order_hash'])->first();
    
    $customerData = $this->setData($order, $data);
    
    $customer = Customer::create($customerData);
    
    if (!$customer) {
        
        return $this->respondError("problem in creating/updating a customer");
    }
    $order->update(['customer_id' => $customer->id]);

    return $this->respond(['success' => true, 'customer' => $customer]); 
  }


  public function subscription_list(Request $request)
  {

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

    if ($customer->company_id != $company->id) {
     
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


  protected function updateCustomer($request)
  {
    if ($request->customer_id) {
      $request['password'] = Hash::make($request['password']);
      $customer = Customer::find($request->customer_id);
      
      $customer->update(['fname'    => $request->fname,
              'lname'               => $request->lname,
              'email'               => $request->email,
              'company_name'        => $request->company_name,
              'phone'               => $request->phone,
              'alternate_phone'     => $request->alternate_phone,
              'password'            => $request->password, 
              'pin'                 => $request->pin,  
              'shipping_address1'   => $request->shipping_address1,
              'shipping_address2'   => $request->shipping_address2,
              'shipping_city'       => $request->shipping_city,
              'shipping_state_id'   => $request->shipping_state_id,
              'shipping_zip'        => $request->shipping_zip,
              'shipping_fname'      => $request->shipping_fname,
              'shipping_lname'      => $request->shipping_lname
      ]);
      return $customer;
    }
  }




  protected function updateOrder($request)
  {
    if ($request->customer_id) {
        $order = Order::hash($request->order_hash)->first();
        $customer = Customer::find($request->customer_id);
        $order->update(['customer_id' => $request->customer_id,
                'shipping_fname'      => $customer->shipping_fname,
                'shipping_lname'      => $customer->shipping_lname,
                'shipping_address1'   => $customer->shipping_address1,
                'shipping_address2'   => $customer->shipping_address2,
                'shipping_city'       => $customer->shipping_city,
                'shipping_state_id'   => $customer->shipping_state_id,
                'shipping_zip'        => $customer->shipping_zip
            ]);
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
    $data    = $request->except('_url');
    $validation = $this->validateUpdate($data);
    if ($validation) {
      return $validation;
    }

    $data = $this->additionalData($data);
    
    Customer::whereHash($data['hash'])->update($data);
   
    return $this->respond(['message' => 'sucessfully Updated']);
  }


    private function additionalData($data)
    {
        $data = array_replace($data,
            array_fill_keys(
                array_keys($data, 'replace_with_null'),
                null
            )
        );
        

        if (isset($data['password'])) {
            $currentPassword = Customer::whereHash($data['hash'])->first();

            if (Hash::check($data['old_password'], $currentPassword['password'])) {
                $password['password'] = bcrypt($data['password']);
                Customer::whereHash($data['hash'])->update($password);
                return $this->respond('sucessfully Updated');    
            }
            else {
                return $this->respondError('Incorrect Current Password');
            }
        }

        return $data;
    }
 
  public function orderUpdate($request)
  {
    $order = $request->only('shipping_fname','shipping_lname','shipping_address1','shipping_address2','shipping_city','shipping_state_id','shipping_zip');
    $order['customer_id'] = $request->id;
    
    Order::where('customer_id','=', $order['customer_id'])->update($order);

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
        'billing_fname'     => 'sometimes|required',
        'billing_lname'     => 'sometimes|required',
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

    public function checkEmail(Request $request)
    {
        $data =  $request->validate([
            'newEmail'   => 'required',
        ]);
        if($request->hash){
            $emailCount = Customer::where('email', '=' , $request->newEmail)->where('hash', '!=' , $request->hash)->count();
        }else{
            $emailCount = Customer::where('email', '=' , $request->newEmail)->count();
        }

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

        $customerDetails = Customer::with('creditAmount','invoice.order','orders.allOrderGroup.plan', 'orders.allOrderGroup.device','orders.allOrderGroup.sim','orders.allOrderGroup.order_group_addon.addon','orders.invoice')->find($customer['id'])->toArray();

        foreach ($customerDetails['orders'] as $key => $order) {
            foreach ($order['all_order_group'] as $orderGroupKey => $orderGroup) {
                if($orderGroup['plan']){
                    $customerDetails['orders'][$key]['all_order_group'][$orderGroupKey]['plan']['subscription'] = Subscription::where([['plan_id', $orderGroup['plan']['id']],['order_id', $order['id']]])->first(); 
                }
                if($orderGroup['device']){
                    $customerDetails['orders'][$key]['all_order_group'][$orderGroupKey]['device']['customer_standalone_device'] = CustomerStandaloneDevice::where([['device_id', $orderGroup['device']['id']],['order_id', $order['id']]])->first(); 
                }
                if($orderGroup['sim']){
                    $customerDetails['orders'][$key]['all_order_group'][$orderGroupKey]['sim']['customer_standalone_sim'] = CustomerStandaloneSim::where([['sim_id', $orderGroup['sim']['id']],['order_id', $order['id']]])->first(); 
                }
            }
        }
        
        return $this->respond($customerDetails);
    }  

    public function proratedDays(Request $request)
    {
      $customer   = Customer::find($request->id);
      $totalDays  = $customer->billing_start;
      return $customer; 
    }

    public function accountStatus(Request $request)
    {
      return Customer::find($request->id)->account_suspended;
    }

    public function saveBillingDetails(Request $request)
    {
        Customer::find($request->id)->update(
            [
                'billing_state_id'  => $request->billing_state_id,
                'billing_fname'     => $request->billing_fname,
                'billing_lname'     => $request->billing_lname,
                'billing_address1'  => $request->billing_address1,
                'billing_address2'  => $request->billing_address2,
                'billing_city'      => $request->billing_city,
                'billing_zip'       => $request->billing_zip,
            ]
        );
        return ['success' => 'Details Added'];
    }

}
