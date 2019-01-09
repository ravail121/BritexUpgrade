<?php

namespace App\Http\Controllers\Api\V1;

use Validator;
use App\Model\Order;
use App\Model\Customer;
use App\Model\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Model\SubscriptionAddon;
use App\Model\CustomerCreditCard;
use App\Services\Payment\UsaEpay;
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
    // $hasError = $this->validateData($request);
    // if($hasError){
    //   return $hasError;
    // }

    $requestedData = $request->all();
    $data = $this->getConstantData($requestedData);

    $orderHash = $request->order_hash;

    $companyId = Order::where('hash',$orderHash)->first()->company_id;

    $data['company_id'] = $companyId;

    $businessVerificationId = $this->getBusinessId($orderHash);

    $data['business_verification_id'] = $businessVerificationId;
     
    $verifyBusiness = Customer::where('business_verification_id',$businessVerificationId)->first();

    if($verifyBusiness) {
      
      $updation = $this->updateCustomer($data,$businessVerificationId);
      if($updation == null) {
        return $this->respondError("problem in updation");
      } else {
        return response()->json(['success' => true, 'order_hash' => $orderHash, 'message' => 'successfully updated']);    

      }   
    } else {
      $customerInserted = $this->create($data);

      if(!$customerInserted) {
        return $this->respondError("problem in creating a customer");
        
      } else {
        return response()->json(['success' => true, 'customer' => $customerInserted]);
      }    
    }    
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
   * This function inserts data to customer_credit_card table
   * 
   * @param  Request    $request 
   * @return string     Json Response
   */
  public function createCreditCard(Request $request)
  { 
    $tran = new UsaEpay;

    $this->getStaticData($tran, $request);

    if($tran->Process()) {

      $creditCard = CustomerCreditCard::create([
        'api_key'          => 'alar324r23423', 
        'customer_id'      => 1, 
        'cardholder'       => $request->payment_card_holder,
        'number'           => str_replace(' ', '', $request->payment_card_no),
        'expiration'       => str_replace('/', '', $request->expires_mmyy),
        'cvc'              => $request->payment_cvc,
        'billing_address1' => $request->billing_address1, 
        'billing_zip'      => $request->billing_zip,
      ]);
      
    } else {
      return response()->json(['message' => 'Card Declined: (' . $tran->result . '). Reason: '. $tran->error]);

    }


    return $creditCard; 
  }


  /**
   * This function validates the Billing info
   * 
   * @param  Request $data
   * @return Respnse
   */
  // protected function validateCredentials($data)
  // {
  //   return $this->validate_input($data->all(), [
  //     'api_key'          => 'required',
  //     'customer_id'      => 'required',
  //     'cardholder'       => 'required',
  //     'number'           => 'required|numeric',
  //     'expiration'       => 'required|numeric',
  //     'cvc'              => 'required|numeric',
  //     'billing_address1' => 'required',
  //     'billing_zip'      => 'required||numeric',
  //   ]);
  // }



  protected function getStaticData($tran, $request)
  {
      $cardNumber = str_replace(' ', '', $request->payment_card_no);
      $expiryDate = str_replace('/', '', $request->expires_mmyy);

      $tran->key="qjyphvd6E6hO4gJ1tuVjqTNTa6g1zHbr ";

      $tran->usesandbox=false;    
      $tran->card        = $cardNumber; 
      $tran->exp         = $expiryDate;
      $tran->cvv2        = $request->payment_cvc;
      $tran->amount      = $request->amount;           
      $tran->invoice     = "GORIN-TEST1";           
      $tran->cardholder  = $request->payment_card_holder;
      $tran->street      = $request->shipping_address1;    
      $tran->zip         = $request->zip;         
      $tran->isrecurring = true; 
      $tran->savecard    = true; 
      $tran->billfname   = $request->fname;
      $tran->billlname   = $request->lname;
      $tran->billcompany = $request->company_name;
      $tran->billstreet  = $request->billing_address1;
      $tran->billcity    = $request->billing_city;
      $tran->billstate   = $request->billing_state_id;
      $tran->billzip     = $request->billing_zip;
      $tran->billcountry = "USA";
      $tran->billphone   = "8636667611";
      $tran->email       = $request->email;
      flush();
      return true;
  }


  protected function validateData($request) { 
    
    return $this->validate_input($request->all(), [ 
    
        'primary_payment_method' => 'required|numeric', 
        'primary_payment_card'   => 'required|numeric|digits_between:15,25',
        'account_suspended'      => 'required',
        'billing_address1'       => 'required|string',
        'billing_address2'       => 'required|string',
        'billing_city'           => 'required|string',
        'billing_state_id'       => 'required|string|max:2',
        'shipping_address1'      => 'required|string',
        'shipping_address2'      => 'required|string',
        'shipping_city'          => 'required|string',
        'shipping_state_id'      => 'required|string|max:2',
        'order_hash'             => 'required|exists:order_hash'
    ]);
  }


  protected function updateCustomer($data,$businessVerificationId)
  {   
    unset($data['company_id']);

    return Customer::where('business_verification_id', $businessVerificationId)
                ->update($data, [
                    'hash' => sha1(time())
                ]);

  }


  protected function create($data)
  {           
    $data['hash'] = sha1(time());
    return Customer::create($data);   
  }

  protected function getBusinessId($orderHash)
  {   
    // $orderId=Order::where('hash',$orderHash)->first()->id;
    // $orders=Order::find($orderId);
    $order = Order::whereHash($orderHash)->first();
    return $order->bizVerification->id;    
  }

  public function getConstantData($data)
  {
    $date = date('y-m-d');

    unset($data['order_hash']);
    $data['subscription_start_date'] = date('d-m-y',strtotime('+30 days'));
    $data['billing_start']           = $date;
    $data['billing_end']             = $date;
    $data['primary_payment_method']  = 1;
    $data['primary_payment_card']    = 1;
    $data['account_suspended']       = 0; 
    $data['billing_address1']        = 'null'; 
    $data['billing_address2']        = 'null'; 
    $data['billing_city']            = 'null'; 
    $data['billing_state_id']        = 'AS'; 

    return $data;  
  }
}
