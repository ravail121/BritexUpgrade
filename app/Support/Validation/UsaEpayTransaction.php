<?php

namespace App\Support\Validation;

use Validator;
use App\Model\Order;
use App\Model\Credit;
use App\Model\Customer;
use App\Model\PaymentLog;
use App\Model\CustomerCreditCard;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\CreditCardRequest;

trait UsaEpayTransaction 
{

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




    /**
    * This function validates the Credit Card Credentials
    * 
    * @param  object    $tran    UsaEpay Object
    * @param  Request   $request
    * @return boolean
    */
    protected function setUsaEpayData($tran, $request)
    {
        $this->stringReplacement($request);

        $tran->key         = $request->key;
        $tran->usesandbox  = $request->usesandbox;    
        $tran->card        = $request->payment_card_no; 
        $tran->exp         = $request->expires_mmyy;
        $tran->cvv2        = $request->payment_cvc;
        $tran->amount      = $request->amount;           
        $tran->invoice     = $request->invoice;        
        $tran->cardholder  = $request->payment_card_holder;
        $tran->street      = $request->shipping_address1;    
        $tran->zip         = $request->zip;         
        $tran->isrecurring = $request->isrecurring; 
        $tran->savecard    = $request->savecard; 
        $tran->billfname   = $request->fname;
        $tran->billlname   = $request->lname;
        $tran->billcompany = $request->company_name;
        $tran->billstreet  = $request->billing_address1;
        $tran->billcity    = $request->billing_city;
        $tran->billstate   = $request->billing_state_id;
        $tran->billzip     = $request->billing_zip;
        $tran->billcountry = $request->billcountry;
        $tran->billphone   = $request->primary_contact;
        $tran->email       = $request->email;

        flush();

        return $tran;
    }




    /**
     * This function inserts data to payment_logs, credits and customer_credit_cards table
     * 
     * @param  Order     $order
     * @param  Request   $request
     * @return Response
     */
    protected function transactionSuccessful($request, $tran)
    {
        $responce = $this->createCustomerCard($request, $tran);
        $order = $responce['order'];
        if ($order) {
            $this->createPaymentLogs($order, $tran, 1);
            $card = $this->createCredits($order->customer_id, $tran);
            $response = response()->json(['success' => true, 'card' => $responce['card']]);
            
        } else {
            $response = response()->json(['message' => 'unsuccessful']);
        }

        return $response;
    }



    /**
     * This function inserts data to payment_logs table and returns error message
     * 
     * @param  Order     $order
     * @param  UsaEpay   $tranFail
     * @return string
     */
    protected function transactionFail($order, $tranFail)
    {
        $this->createPaymentLogs($order, $tranFail, 0);
        return response()->json(['message' => 'Card Declined: (' . $tranFail->result . '). Reason: '. $tranFail->error]);
    }



    /**
     * This function inserts data to payment_logs table
     * 
     * @param  int  $order_id
     * @return Response
     */
    protected function createPaymentLogs($order, $tran, $response)
    {
        return PaymentLog::create([
            'customer_id'            => $order->customer_id, 
            'order_id'               => $order->id,
            // 'invoice_id'             => $order->invoice_id, 
            'transaction_num'        => $tran->authcode, 
            'processor_customer_num' => $tran->card, 
            'status'                 => $response,
            'error'                  => $tran->error,
            'exp'                    => $tran->exp,
            'last4'                  => substr($tran->last4, -4),
            'card_type'              => $tran->cardType,
            'amount'                 => $tran->amount,
            'card_token'             => $tran->cardref,
        ]);
    }



    /**
     * This function inserts data to credits table
     * 
     * @param  Order       $order
     * @param  Request     $request
     * @return Response
     */
    protected function createCredits($customerId, $tran)
    {
        return Credit::create([
            'customer_id' => $customerId,
            'amount'      => $tran->amount,
            'date'        => date("Y/m/d"),
            'description' => $tran->cardType . ' '.substr($tran->last4, -4),
        ]);
    }




    /**
     * This function inserts data to customer_credit_cards table
     * 
     * @param  Request $request
     * @return Order object
     */
    protected function createCustomerCard($request, $tran)
    {
        if ($request->customer_id) {
            $order = Order::where('customer_id', $request->customer_id)->first();

        } elseif ($request->order_hash) {
            $order = Order::where('hash', $request->order_hash)->first();
            
        } else {
            return false;
        }
        

        $found = CustomerCreditCard::where('cardholder', $request->payment_card_holder)
                                    ->where('number', $request->payment_card_no)
                                    ->where('expiration', $request->expires_mmyy)
                                    ->where('cvc', $request->payment_cvc)
                                    ->first();

        if (!$found) {
            $inserted = CustomerCreditCard::create([
                'token'            => $tran->cardref,
                'api_key'          => $order->company->api_key, 
                'customer_id'      => $order->customer_id, 
                'cardholder'       => $request->payment_card_holder,
                'number'           => $request->payment_card_no,
                'expiration'       => $request->expires_mmyy,
                'last4'            => $tran->last4,
                'card_type'        => $tran->cardType,
                'cvc'              => $request->payment_cvc,
                'billing_address1' => $request->billing_address1, 
                'billing_address2' => $request->billing_address2, 
                'billing_city'     => $request->billing_city, 
                'billing_state_id' => $request->billing_state_id, 
                'billing_zip'      => $request->billing_zip,
            ]);

            $customer = Customer::find($order->customer_id);

            if ($customer->billing_address1 == null) {
                $customer->update([
                    'billing_address1' => $request->billing_address1, 
                    'billing_address2' => $request->billing_address2, 
                    'billing_city'     => $request->billing_city, 
                    'billing_state_id' => $request->billing_state_id, 
                    'billing_zip'      => $request->billing_zip,

                ]);
            }

            if (!$inserted) {
                $order = false;
            }

        }

        return ['order' => $order, 'card' => $inserted ];
    }


    /**
    * This function replaces characters with blank
    * 
    * @param  Request    $request
    * @return string               [Replaced string]
    */
    protected function stringReplacement($request)
    {
        $request->payment_card_no   = str_replace(' ', '', $request->payment_card_no);
        $request->expires_mmyy      = str_replace('/', '', $request->expires_mmyy);
        $request->primary_contact   = str_replace(['(', ')', ' ', '-'], '', $request->primary_contact);
        $request->secondary_contact = str_replace(['(', ')', ' ', '-'], '', $request->secondary_contact);

        return true;
    }



}