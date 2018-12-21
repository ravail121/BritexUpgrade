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
use App\Http\Request\CardValidation;
use App\Http\Controllers\BaseController;

class CustomerController extends BaseController
{
    public function __construct(){
        $this->content = array();
    }
   
    public function post(Request $request)
    {
        if($hasError = $this->validateData($request)){
            return $hasError;
        }

        $data = $this->addExtraData( $request->except('order_hash') );

        return $data;
        
        $data['billing_start']           = date('y-m-d');
        $data['billing_end']             = date('y-m-d');
        $data['subscription_start_date'] = date('d-m-y',strtotime('+30 days'));
        $data['primary_payment_method']  = 'card';
        $data['account_suspended']       = 0;

        $order = Order::hash($request->order_hash)->first();

        $companyId = Order::where('hash', $orderHash)->first()->id;

        $data['company_id'] = $companyId;

        $businessVerificationId = $this->getBusinessId($orderHash);
       
        $verifyBusiness=Customer::where('business_verification_id',$businessVerificationId)->first();
        
        $hasError = $this->validateData($request);



        if($verifyBusiness){
          
            $updation = $this->updateCustomer($data,$businessVerificationId);
            //dd($updation);
            if($updation == null){
                return $this->respondError("problem in updation");
            }else{
                return response()->json(['success' => true, 'order_hash' => $orderHash, 'message' => 'successfully updated']);    

            }   
        }else{
            $customerInserted=$this->create($data);

            if(!$customerInserted){
                return $this->respondError("problem in creating a customer");
            }else{
                return response()->json(['success' => true, 'customer' => $customerInserted]);
            }    
        }    
    }

    private function addExtraData($data)
    {
        $data += [
            'billing_start'           => date('y-m-d'),
            'billing_end'             => date('y-m-d'),
            'subscription_start_date' => date('d-m-y',strtotime('+30 days')),
            'primary_payment_method'  => 'card',
            'account_suspended'       => 0
        ];

        return $data;   
    }

    public function subscription_list(Request $request){

        $output = ['success' => false , 'message' => ''];

        $company = \Request::get('company');
        $customer_id = $request->input(['customer_id']);
        $validation = Validator::make($request->all(),[
         'customer_id'=>'numeric|required']);

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


    protected function validateData($request) {
        return $this->validate_input($request->all(), [ 
            // 'primary_payment_method' => 'required|numeric', 
            'primary_payment_card'   => 'required|numeric|digits_between:15,25',
            'billing_address1'       => 'required|string',
            'billing_address2'       => 'required|string',
            'billing_city'           => 'required|string',
            'billing_state_id'       => 'required|string|max:2',
            'shipping_address1'      => 'required|string',
            'shipping_address2'      => 'required|string',
            'shipping_city'          => 'required|string',
            'shipping_state_id'      => 'required|string|max:2',
            'hash'                   => 'required|exists:order,hash'
        ]);
    }


    protected function updateCustomer($data,$businessVerificationId)
    {   
        unset($data['company_id']);

        return $updateCustomer = Customer::where('business_verification_id',$businessVerificationId)
        ->update($data,['hash' => sha1(time())]);

    }


    protected function create($data)
    {           
        $data['hash'] = sha1(time());
        return $customer = Customer::create($data);   
    }

    protected function getBusinessId($orderHash)
    {   
        $orderId=Order::where('hash',$orderHash)->first()->id;
        $orders=Order::find($orderId);
        return $bizVerificationId=$orders->bizVerification()->first()->id;    
    }



}
