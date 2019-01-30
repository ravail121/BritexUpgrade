<?php

namespace App\Http\Controllers\Api\V1;

use App\Model\Order;
use Illuminate\Http\Request;
use App\Model\CustomerCreditCard;
use App\Services\Payment\UsaEpay;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;
use App\libs\Constants\ConstantInterface;

class CardController extends BaseController implements ConstantInterface
{
    public $tran;
    public $response;


    public function __construct(UsaEpay $tran)
    {
        $this->tran = $tran;
    }


    /**
     * This function fetches all credit cards of particular customer
     * 
     * @param  int       $customer_id
     * @return Response
     */
    public function getCustomerCards(Request $request)
    {
        if ($request->customer_hash) {
        	$customerCreditCard = CustomerCreditCard::where([
                'api_key' => $request->api_key,
                'hash'    =>  $request->customer_hash
            ])->get();

        } elseif ($request->customer_id) {
            $customerCreditCard = CustomerCreditCard::where([
                'api_key'     => $request->api_key,
                'customer_id' =>  $request->customer_id
            ])->get();

        }

        foreach ($customerCreditCard as $card) {
            $card->expiration = $card->addPrefixSlash();
            $card->last4      = $card->last_four;
        }

        return response()->json($customerCreditCard);
    }




    /**
     * This function inserts data to customer_credit_cards table
     * 
     * @param  Request $request
     * @return Response
     */
    public function addCard(Request $request)
    {
        $this->setConstantData($request);
        $request->amount = self::TRAN_AMOUNT;
        $validation = $this->validateCredentials($request);

        if ($validation->fails()) {
            return response()->json([
                'message' => $validation->getMessageBag()->all()
            ]);
        }

        return $this->processTransaction($request);

    }




    /**
     * [chargeCard description]
     * 
     * @param  Request $request
     * @return Response
     */
    public function chargeCard(Request $request)
    {
        $validation = $this->validateData($request);
        if ($validation) {
            return $this->respondError($validation);
        }
        $this->setConstantData($request);

        $creditCard = CustomerCreditCard::find($request->credit_card_id);
        $this->creditCardData($creditCard, $request);
        
        return $this->processTransaction($request);
    }


    protected function creditCardData($card, $request)
    {
        $request->payment_card_holder =  $card->cardholder;
        $request->payment_card_no     =  $card->number;
        $request->expires_mmyy        =  $card->expiration;
        $request->payment_cvc         =  $card->cvc;
        $request->shipping_address1   =  $card->customer->shipping_address1;    
        $request->zip                 =  $card->customer->shipping_zip;         
        $request->fname               =  $card->customer->fname;
        $request->lname               =  $card->customer->lname;
        $request->company_name        =  $card->customer->company_name;
        $request->billing_address1    =  $card->billing_address1;
        $request->billing_city        =  $card->billing_city;
        $request->billing_state_id    =  $card->billing_state_id;
        $request->billing_zip         =  $card->billing_zip;
        $request->primary_contact     =  $card->customer->phone;
        $request->email               =  $card->customer->email;

        return $request;
    
    }



    protected function processTransaction($request)
    {
        $order = Order::hash($request->order_hash)->first();
        $this->tran = $this->setUsaEpayData($this->tran, $request);

        if($this->tran->Process()) {

            $this->response = $this->transactionSuccessful($request, $this->tran);
      
        } else {
            $this->response = $this->transactionFail($order->id, $this->tran);
        }

        return $this->response;
    }




    /**
     * Validates if all fields are required
     * 
     * @param  Request   $request
     * @return Response
     */
    protected function validateData($request)
    {
        return $this->validate_input($request->all(), [
            'amount'         => 'required',
            'credit_card_id' => 'required',
            'order_hash'     => 'required',
        ]);
    }





    /**
    * This function sets the variable with constant values
    * 
    * @param  array    $array
    * @return boolean
    */
    protected function setConstantData($request)
    {
        $request->key         = env('SOURCE_KEY');
        $request->usesandbox  = self::TRAN_TRUE;
        $request->invoice     = self::TRAN_INVOICE;
        $request->isrecurring = self::TRAN_TRUE; 
        $request->savecard    = self::TRAN_TRUE; 
        $request->billcountry = self::TRAN_BILLCOUNTRY;

        return $request;
    }


}
