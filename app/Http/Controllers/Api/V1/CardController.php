<?php

namespace App\Http\Controllers\Api\V1;

use Carbon\Carbon;
use App\Model\Order;
use App\Model\Invoice;
use App\Model\Customer;
use Illuminate\Http\Request;
use App\Events\InvoiceAutoPaid;
use App\Model\CustomerCreditCard;
use App\Services\Payment\UsaEpay;
use App\Http\Controllers\Controller;
use App\Events\FailToAutoPaidInvoice;
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
        if ($request->hash) {
            $customer = Customer::where('hash', $request->hash)->first();
            $customerCreditCard =  $customer->customerCreditCards;

        } elseif ($request->customer_id) {
            $customerCreditCard = CustomerCreditCard::where([
                'api_key'     => $request->api_key,
                'customer_id' =>  $request->customer_id
            ])->get();

        }else{
             return $this->respond(['message' => 'CustomerId or customer_hash request']);
        }

        if (!$customerCreditCard) {
            return $this->respond(['message' => 'no cards available']);

        } 

        foreach ($customerCreditCard as $card) {
            $card->expiration = $card->addPrefixSlash();
            $card->last4      = $card->last_four;
            $card->info       = $card->card_info;
        }


        return $this->respond($customerCreditCard);
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
        $request['amount'] = self::TRAN_AMOUNT;
        $validation = $this->validateCredentials($request);

        if ($validation->fails()) {
            return $this->respond([
                'message' => $validation->getMessageBag()->all()
            ]);
        }

        return $this->processTransaction($request, 'authonly');

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
        $request->card_id             =  $card->id;
        $request->payment_card_holder =  $card->cardholder;
        $request->payment_card_no     =  $card->token;
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



    protected function processTransaction($request, $command = null)
    {
        $order = Order::where('customer_id', $request->customer_id)->first();
        if ($order) {
            $this->tran = $this->setUsaEpayData($this->tran, $request, $command);

            if($this->tran->Process()) {
                if($request->without_order){
                    $this->response = $this->transactionSuccessfulWithoutOrder($request, $this->tran);
                }else{
                    $this->response = $this->transactionSuccessful($request, $this->tran);
                }
          
            } else {
                if($request->without_order){
                    return response()->json(['message' => 'Card  ' . $this->tran->result . ', Because '. $this->tran->error]);
                }else{
                    $this->response = $this->transactionFail($order, $this->tran);
                }
            }
        } else {
            $this->response = $this->transactionFail(null, $this->tran);
        }

        return $this->respond($this->response);
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

    public function removeCard(Request $request)
    {
        $data=$request->validate([
            'customer_credit_card_id'   => 'required',
        ]);

        if($this->validateCardId($data)){
            CustomerCreditCard::whereId($data['customer_credit_card_id'])->delete();
            return $this->respond(['details' => 'Card Sucessfully Deleted']);
        }
        else{
            return $this->respondError("Card Not Found");
        } 
    }

    protected function validateCardId($data)
    {
        return CustomerCreditCard::whereId($data['customer_credit_card_id'])->exists();
    }

    public function primaryCard(Request $request)
    {
        $data=$request->validate([
            'customer_credit_card_id'   => 'required',
            'id'                        => 'required'
        ]);

        if($customerCreditCard = CustomerCreditCard::find($data['customer_credit_card_id'])){
            CustomerCreditCard::whereCustomerId($data['id'])->update(['default' => '0']); 
            $customerCreditCard->update(['default' => '1']);
            return $this->respond(['details' => 'Card Sucessfully Updated']); 
        }
        else{
            return $this->respondError("Card Not Found");
        } 
    }


    public function autoPayInvoice()
    {
        $date = Carbon::today()->addDays(1);

        $customers = Customer::where([
            ['billing_end', '<=', $date], ['auto_pay', Customer::AUTO_PAY['enable']
        ]])->with('unpaidMounthlyInvoice')->get()->toArray();

        $customersB = Customer::where([
            ['billing_start', '<=', Carbon::today()], ['auto_pay', Customer::AUTO_PAY['enable']
        ]])->with('unpaidAndClosedMounthlyInvoice')->get()->toArray();

        $customers = array_merge($customers, $customersB);

        foreach ($customers as $key => $customer) {
            if(isset($customer['unpaid_mounthly_invoice'][0]) || isset($customer['unpaid_and_closed_mounthly_invoice'][0]) ){

                if(isset($customer['unpaid_mounthly_invoice'][0])){
                    $customer['mounthlyInvoice'] = $customer['unpaid_mounthly_invoice'][0]; 
                }else{
                    $customer['mounthlyInvoice'] = $customer['unpaid_and_closed_mounthly_invoice'][0];
                }

                $card = CustomerCreditCard::where(
                    'customer_id', $customer['id']
                )->orderBy('default', 'desc')->first();

                if($card){
                    $request = new Request;
                    $request->replace([
                        'credit_card_id' => $card->id,
                        'amount'         => $customer['mounthlyInvoice']['subtotal'],
                        'customer_id'    => $customer['id'],
                    ]);
                    $response = $this->chargeCard($request);
                    if(isset($response->getData()->success) && $response->getData()->success =="true") {
                            Invoice::find($customer['mounthlyInvoice']['id'])->update([
                            'status' => Invoice::INVOICESTATUS['closed']
                        ]);
                        event(new InvoiceAutoPaid($customer));
                    }else{
                        event(new FailToAutoPaidInvoice($customer, $response->getData()->message));
                    }
                }else{
                    event(new FailToAutoPaidInvoice($customer, 'No Saved Card Found in our Record'));
                }
            }
        }
    }
}




