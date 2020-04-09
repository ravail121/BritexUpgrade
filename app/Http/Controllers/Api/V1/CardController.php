<?php

namespace App\Http\Controllers\Api\V1;

use PDF;
use Exception;
use Carbon\Carbon;
use App\Model\Order;
use App\Model\Credit;
use App\Model\Invoice;
use App\Model\Customer;
use App\Model\PaymentLog;
use App\Model\InvoiceItem;
use App\Events\InvoiceEmail;
use Illuminate\Http\Request;
use App\Events\InvoiceAutoPaid;
use App\Model\CustomerCreditCard;
use App\Services\Payment\UsaEpay;
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
                $this->response = $this->transactionFail($order, $this->tran);
                if($request->without_order){
                    return response()->json(['message' => ' Card  ' . $this->tran->result . ', '. $this->tran->error, 'transaction' => $this->tran]);
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
        // $request->key         = env('SOURCE_KEY');
        // $request->usesandbox  = \Request::get('company')->usaepay_live_formatted;
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
            $customerCreditCard = CustomerCreditCard::find($data['customer_credit_card_id']);
            if($customerCreditCard->default){
                $customerCreditCard->delete();
                $leftCustomerCreditCard = CustomerCreditCard::where('customer_id', $customerCreditCard->customer_id)->get()->last();
                if($leftCustomerCreditCard){
                    $leftCustomerCreditCard->update(['default' => true ]);
                }
            }else{
                $customerCreditCard->delete();
            }
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


    public function autoPayInvoice(Request $request)
    {
        $date = Carbon::today()->addDays(1);
        $customers = Customer::where([
            ['billing_end', '<=', $date], ['auto_pay', Customer::AUTO_PAY['enable']
        ]])->with('unpaidMounthlyInvoice')->get()->toArray();

        $customersB = Customer::where([
            ['billing_start', '=', Carbon::today()], ['auto_pay', Customer::AUTO_PAY['enable']
        ]])->with('unpaidAndClosedMounthlyInvoice')->get()->toArray();

        $customers = array_merge($customers, $customersB);

        foreach ($customers as $key => $customer) {
            \Log::info(array($customer["id"], $customer["email"]));
            try {
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
                        $invoice = Invoice::where('id', $customer['mounthlyInvoice']['id'])->with('order')->first();
                        \Log::info($invoice);

                        $order_hash = "";
                        $api_key = $invoice->customer->company->api_key;
                        if($invoice->order){
                            $order_hash = $invoice->order->hash;
                            $api_key = $invoice->order->company->api_key;
                        }

                        $request->replace([
                            'credit_card_id' => $card->id,
                            'amount'         => $customer['mounthlyInvoice']['total_due'],
                            'order_hash'    => $order_hash,
                        ]);

                        if($invoice->order == null){
                            $request->replace([
                                'key' => $invoice->customer->company->usaepay_api_key,
                                'usesandbox' => $invoice->customer->company->usaepay_live_formatted,
                            ]);
                        }

                        $response = $this->chargeCard($request);
                        if(isset($response->getData()->success) && $response->getData()->success =="true") {
                            $invoice->update([
                                'status' => Invoice::INVOICESTATUS['closed'],
                                'total_due' => 0
                            ]);
                            $request->headers->set('authorization', $api_key);
                            event(new InvoiceAutoPaid($customer));
                            \Log::info("AutoPaid for customer ".$customer['id']);
                            $customer->account_suspended ? $customer->update(['account_suspended' => 0]) && event(new AccountUnsuspended($customer)) : null;
                        }else{
                            $request->headers->set('authorization', $api_key);
                            event(new FailToAutoPaidInvoice($customer, $response->getData()->message));
                            $paymentLog = $this->createPaymentLogs($invoice->order, $this->tran, 0, $card, $invoice);
                        }
                        
                        if($invoice->order){
                            PaymentLog::where('order_id', $invoice->order->id)->update(['invoice_id' => $invoice->id ]);
                        }
                    }else{
                        $request->headers->set('authorization', $api_key);
                        event(new FailToAutoPaidInvoice($customer, 'No Saved Card Found in our Record'));
                    }
                }
            } catch (Exception $e) {
                \Log::info($e->getMessage().' on line '.$e->getLine().' in CardController');
            }
        }
    }

    /**
     * This function charges now card without order which is done by admin
     * from admin portal
     * inserts data to credits and customer_credit_cards table
     * 
     * @param  Order     $order
     * @param  Request   $request
     * @return Response
     */

    protected function transactionSuccessfulWithoutOrder($request, $tran)
    {
        $credit = $this->createCredits($request, $tran);
        $invoice = $this->processCreditInvoice($request, $tran, $credit);

        $this->payUnpaiedInvoice($tran->amount, $request, $credit);
        

        $response = response()->json(['success' => true, 'transaction' => $tran]);

        return $response;
    }

    public function payCreditToInvoice(Request $request)
    {
        $credit = Credit::find($request->creditId);
        $this->payUnpaiedInvoice($credit->amount, $credit, $credit);
    }

    protected function payUnpaiedInvoice($tranAmount, $request, $credit)
    {
        $invoices = Invoice::where([
            ['customer_id', $request->customer_id],
            ['status', Invoice::INVOICESTATUS['open']]
        ])->get();

        $date = Carbon::today()->subDays(31)->endOfDay();
        $closedInvoice = Invoice::where([
            ['customer_id', $request->customer_id],
            ['status', Invoice::INVOICESTATUS['closed&upaid']]
        ])->whereBetween('start_date', [$date, Carbon::today()->startOfDay()])->get()->last();

        if($closedInvoice){
            $invoices->push($closedInvoice);
        }
        if(isset($invoices[0]) && $invoices[0]){
            foreach ($invoices as $key => $invoice) {
                if($invoice->total_due == $tranAmount){
                    $this->updateCredit(1 ,$credit);
                    $this->addCreditToInvoiceRowNonOrder($invoice, $credit, $tranAmount);
                    $this->updateInvoice($invoice ,0, Invoice::INVOICESTATUS['closed&paid']);

                    break;
                }else if($invoice->total_due < $tranAmount){
                    $this->updateCredit(0 ,$credit);
                    $this->addCreditToInvoiceRowNonOrder($invoice, $credit, $invoice->total_due);
                    $tranAmount -= $invoice->total_due;
                    $this->updateInvoice($invoice ,0, Invoice::INVOICESTATUS['closed&paid']);

                }else if($invoice->total_due > $tranAmount){
                    $this->updateCredit(1 ,$credit);
                    $this->addCreditToInvoiceRowNonOrder($invoice, $credit, $tranAmount);
                    $leftAmount = $invoice->total_due - $tranAmount;
                    $this->updateInvoice($invoice ,$leftAmount, $invoice->status);
                    break;
                }
            }
        $this->accountSuspendedAccount($request->customer_id);
        }
    }

    protected function processCreditInvoice($data, $tran, $credit)
    {
        $card = CustomerCreditCard::find($data['credit_card_id']);
        $customer = Customer::find($card->customer_id);
        $invoice = [
            'staff_id'                  => $data->staff_id ?: null,
            'customer_id'               =>  $card->customer_id,
            'type'                      =>  '2',
            'start_date'                =>  $customer->billing_start,
            'end_date'                  =>  $customer->billing_end,
            'status'                    =>  '2',
            'subtotal'                  =>  $data['amount'],
            'total_due'                 =>  '0',
            'prev_balance'              => '0',
            'payment_method'            => '1',
            'notes'                     => '',
            'due_date'                  =>  Carbon::parse($customer->billing_start)->subDays(1),
            'business_name'             => $customer->company_name,
            'billing_fname'             => $customer->fname,
            'billing_lname'             => $customer->lname,
            'billing_address_line_1'    => $card->billing_address1,
            'billing_address_line_2'    => $card->billing_address2,
            'billing_city'              => $card->billing_city,
            'billing_state'             => $card->billing_state_id,
            'billing_zip'               => $card->billing_zip,
            'shipping_fname'            => $customer->shipping_fname,
            'shipping_lname'            => $customer->shipping_lname,
            'shipping_address_line_1'   => $customer->shipping_address1,
            'shipping_address_line_2'   => $customer->shipping_address2,
            'shipping_city'             => $customer->shipping_city,
            'shipping_state'            => $customer->shipping_state_id,
            'shipping_zip'              => $customer->shipping_zip,
            
        ];

        $newInvoice = Invoice::create($invoice);
        $credit->update(['invoice_id' => $newInvoice->id]);

        $invoiceItem = [
            'invoice_id'     => $newInvoice->id,
            'product_type'   => $data['payment_type']?: 'Manual Payment',
            'type'           => '9',
            'subscription_id'=> $data['subscription_id'],
            'start_date'     => Carbon::today(),
            'description'    => $data['description']?: 'Manual Payment',
            'amount'         => $data['amount'],
            'taxable'        => '0',
        ];

        $newInvoiceItem = InvoiceItem::create($invoiceItem);
        $paymentLog = $this->createPaymentLogs(null, $tran, 1, $card, $newInvoice);
        if($data['payment_type'] == 'Custom Charge'){
            $invoice = $newInvoice;
            $pdf = PDF::loadView('templates/custom-charge-invoice', compact('invoice'));
            event(new InvoiceEmail($invoice, $pdf, 'custom-charge'));
        }

        return $newInvoice;
    }
}




