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
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\CreditCardRequest;
use App\Http\Controllers\BaseController;



class CustomerController extends BaseController
{
  const TRAN_KEY         = "qjyphvd6E6hO4gJ1tuVjqTNTa6g1zHbr";
  const TRAN_INVOICE     = "GORIN-TEST1";
  const TRAN_BILLCOUNTRY = "USA";
  const TRAN_FALSE       = false;
  const TRAN_TRUE        = true;

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
    $order        = $this->getOrderClass($data['order_hash']);
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
   * This function inserts data to customer_credit_card table
   * 
   * @param  Request    $request 
   * @return string     Json Response
   */
  public function createCreditCard(Request $request)
  { 
    $validation = $this->validateCredentials($request);
    if ($validation->fails()) {
      return response()->json($validation->getMessageBag()->all());

    }
    $tran = new UsaEpay;

    $this->getStaticData($tran, $request);

    $order = $this->getOrderClass($request->order_hash);

    if($tran->Process()) {

      $creditCard = CustomerCreditCard::create([
        'token'            => Hash::make(time()),
        'api_key'          => $order->company->api_key, 
        'customer_id'      => $order->customer_id, 
        'cardholder'       => $request->payment_card_holder,
        'number'           => str_replace(' ', '', $request->payment_card_no),
        'expiration'       => str_replace('/', '', $request->expires_mmyy),
        'cvc'              => $request->payment_cvc,
        'billing_address1' => $request->billing_address1, 
        'billing_address2' => $request->billing_address2, 
        'billing_city'     => $request->billing_city, 
        'billing_state_id' => $request->billing_state_id, 
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
  protected function validateCredentials($data)
  {
    $credCardRequest = new CreditCardRequest($data['payment_card_no']);
    $rules           = $credCardRequest->rules();
    return Validator::make($data->all(), $rules);
  }



  protected function getStaticData($tran, $request)
  {
      $cardNumber = $this->stringReplacement([' '], $request->payment_card_no);
      $expiryDate = $this->stringReplacement(['/'], $request->expires_mmyy);
      $phone      = $this->stringReplacement(['(', ')', ' ', '-'], $request->primary_contact);

      $tran->key         = self::TRAN_KEY;
      $tran->usesandbox  = self::TRAN_FALSE;    
      $tran->card        = $cardNumber; 
      $tran->exp         = $expiryDate;
      $tran->cvv2        = $request->payment_cvc;
      $tran->amount      = $request->amount;           
      $tran->invoice     = self::TRAN_INVOICE;           
      $tran->cardholder  = $request->payment_card_holder;
      $tran->street      = $request->shipping_address1;    
      $tran->zip         = $request->zip;         
      $tran->isrecurring = self::TRAN_TRUE; 
      $tran->savecard    = self::TRAN_TRUE; 
      $tran->billfname   = $request->fname;
      $tran->billlname   = $request->lname;
      $tran->billcompany = $request->company_name;
      $tran->billstreet  = $request->billing_address1;
      $tran->billcity    = $request->billing_city;
      $tran->billstate   = $request->billing_state_id;
      $tran->billzip     = $request->billing_zip;
      $tran->billcountry = self::TRAN_BILLCOUNTRY;
      $tran->billphone   = $phone;
      $tran->email       = $request->email;
      flush();
      return true;
  }


  protected function setData($order, $data)
  {
    unset($data['order_hash']);
    $data['business_verification_id'] = $order->bizVerification->id;
    $data['company_id']               = $order->company_id;
    $data['hash']                     = sha1(time());
    $data['password']                 = Hash::make($data['password']);
    $data['pin']                      = Hash::make($data['pin']);

    return $data;
  }


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


  protected function stringReplacement($array, $string)
  {
    return str_replace($array, '', $string);
  }


  protected function getOrderClass($hash)
  {
    return Order::hash($hash)->first();
  }

}
