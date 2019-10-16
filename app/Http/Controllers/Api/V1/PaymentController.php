<?php

namespace App\Http\Controllers\Api\V1;

use PDF;
use Carbon\Carbon;
use App\Model\Order;
use App\Model\Credit;
use App\Model\Invoice;
use App\Model\Customer;
use App\Model\OrderGroup;
use App\Model\PaymentLog;
use App\Model\InvoiceItem;
use App\Model\Subscription;
use Illuminate\Http\Request;
use App\Events\PaymentFailed;
use App\Model\PaymentRefundLog;
use App\Services\Payment\UsaEpay;
use App\Model\CustomerCreditCard;
use App\Model\SystemGlobalSetting;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;
use App\libs\Constants\ConstantInterface;
use App\Http\Controllers\Api\V1\Traits\InvoiceTrait;

class PaymentController extends BaseController implements ConstantInterface
{
    use InvoiceTrait;

    const DEFAULT_VALUE = 2;
    const DEFAULT_DUE   = '0.00';

    public $tran;

    public $carbon;

    public function __construct(UsaEpay $tran, Carbon $carbon)
    {
        $this->tran   = $tran;
        $this->carbon = $carbon;
    }



    /**
    * This function inserts data to customer_credit_card table
    * 
    * @param  Request    $request 
    * @return string     Json Response
    */
    public function chargeNewCard(Request $request)
    {
        $this->setConstantData($request);
        if($request->customer_card == 'customer_card' || !$request->customer_card){
            $validation = $this->validateCredentials($request);

            if ($validation->fails()) {
                return $this->respond([
                    'message' => $validation->getMessageBag()->all()
                ]);
            }
        }else{
            $creditCard = CustomerCreditCard::find($request->customer_card);
            if(!$creditCard){
                return $this->respond([
                    'message' => 'Sorry Card not Found'
                ]);
            }
            $this->creditCardData($creditCard, $request);
        }

        if (!$request->order_hash) {
            return $this->respond([
                'message' => 'order_hash is required'
            ]);
        }


        $order = Order::hash($request->order_hash)->first();
        
        $this->tran = $this->setUsaEpayData($this->tran, $request);

        if($this->tran->Process()) {
            
            $msg = $this->transactionSuccessful($request, $this->tran);

            $data    = $this->setInvoiceData($order, $msg['credit'], $request);
            $invoice = Invoice::create($data);
            
            $this->addCreditToInvoiceRow($invoice, $msg['credit'], $this->tran);

            if ($invoice) {
                
                $orderCount = Order::where([['status', 1],['company_id', $order->company_id]])->max('order_num');
                $order->update([
                    'status'     => 1, 
                    'invoice_id' => $invoice->id,
                    'order_num'  => $orderCount+1,
                    'date_processed' => Carbon::today()
                ]);

                PaymentLog::where('order_id', $order->id)->update(['invoice_id' => $invoice->id]);
            } else {
                $msg = $this->respond([
                    'invoice' => 'Failed to generate invoice.'
                ]);
            }
        } else {
            $msg = $this->transactionFail($order, $this->tran); 
        }

        return $this->respond($msg); 
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




    /**
     * Sets data for `invoice` table
     * 
     * @param Order $order
     */
    protected function setInvoiceData($order, $credit, $request)
    {
        $arr = [];
        $customer = Customer::find($order->customer_id);
        if (!$customer) {
            return $arr;
        }

        $card = CustomerCreditCard::where('customer_id', $customer->id)->latest()->first();

        if ($customer || $credit || $card) {
            $arr = [
                'customer_id'             => $customer->id,
                'type'                    => self::DEFAULT_VALUE,
                'status'                  => self::DEFAULT_VALUE,
                'start_date'              => $this->carbon->toDateString(),
                'end_date'                => $this->carbon->addMonth()->subDay()->toDateString(),
                'due_date'                => $this->carbon->subDay()->toDateString(),
                'subtotal'                => $credit->amount,
                'total_due'               => self::DEFAULT_DUE,
                'prev_balance'            => self::DEFAULT_DUE, 
                'payment_method'          => $credit->payment_method, 
                'notes'                   => 'notes', 
                'business_name'           => $customer->company_name, 
                'billing_fname'           => $request->billing_fname, 
                'billing_lname'           => $request->billing_lname, 
                'billing_address_line_1'  => $card->billing_address1, 
                'billing_address_line_2'  => $card->billing_address2, 
                'billing_city'            => $card->billing_city, 
                'billing_state'           => $card->billing_state_id, 
                'billing_zip'             => $card->billing_zip, 
                'shipping_fname'          => $customer->shipping_fname, 
                'shipping_lname'          => $customer->shipping_lname, 
                'shipping_address_line_1' => $customer->shipping_address1, 
                'shipping_address_line_2' => $customer->shipping_address2, 
                'shipping_city'           => $customer->shipping_city, 
                'shipping_state'          => $customer->shipping_state_id, 
                'shipping_zip'            => $customer->shipping_zip,
            ];
        }

        return $arr;
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
        $request->usesandbox  = \Request::get('company')->usaepay_live_formatted;
        $request->invoice     = self::TRAN_INVOICE;
        $request->isrecurring = self::TRAN_TRUE; 
        $request->savecard    = self::TRAN_TRUE; 
        $request->billcountry = self::TRAN_BILLCOUNTRY;

        return true;
    }


    public function processRefund(Request $request)
    {
        $data = $request->validate([
            'refnum'     => 'required',
            'amount'     => 'required|numeric',
            'credit'     => 'required',
            'staff_id'   => 'required',
        ]); 
        
        $this->setConstantData($request);
        $this->tran = $this->setUsaEpayDataForRefund($this->tran, $request);
        $paymentLog = PaymentLog::where('transaction_num' , $data['refnum'])->first();
        $customer   = Customer::find($paymentLog->customer_id);
        $request->headers->set('authorization', $customer->company->api_key);
        if($this->tran->Process()) {
            $status = PaymentRefundLog::STATUS['success'];
            $amount = $this->tran->amount;
            $invoice = $this->createRefundInvoice($paymentLog->invoice_id, $amount, $request->staff_id);
            if($invoice){
                $InvoiceItem = $this->createRefundInvoiceItem($invoice, $this->tran->amount, ['refund', 'Refund']);
                $msg = "success";
                if($request->credit == '1'){
                    $credit = $this->createRefundCredit($invoice, $this->tran->amount, $request->staff_id );
                    $InvoiceItem = $this->createRefundInvoiceItem($invoice, $this->tran->amount, ['manual', 'Manual-Credit']);
                    if(!$credit){
                       $msg = "Refund Processed but fail to add credits"; 
                    }
                }
            }else{
                $msg = "Refund Processed Invoice not Created because Old Invoice not Found";
            }
            $paymentRefundLog = $this->createPaymentRefundLog($paymentLog, $status, $invoice->id);
            $this->generateRefundInvoice($invoice, $paymentLog, false);
        }else {
            
            $status = PaymentRefundLog::STATUS['fail'];
            $msg = $this->tran->error;
            $paymentRefundLog = $this->createPaymentRefundLog($paymentLog, $status);
            // failed Mail event if want
        }

        return $this->respond([
            'paymentRefundLog'=> $paymentRefundLog,
            'message' => $msg
        ]); 
    }

    protected function createPaymentRefundLog($paymentLog, $status, $invoiceId = null)
    {
        return PaymentRefundLog::create([
            'invoice_id'      =>  $invoiceId,
            'payment_log_id'  =>  $paymentLog->id,
            'transaction_num' =>  $this->tran->refnum ?: null,
            'error'           =>  $this->tran->error,
            'amount'          =>  $this->tran->amount,
            'status'          =>  $status,
        ]);
    }

    protected function createRefundInvoice($invoiceId, $amount, $staffId)
    {
        $invoiceData = Invoice::find($invoiceId)->toArray();
        if($invoiceData){
            unset ($invoiceData['id'], $invoiceData['created_at'], $invoiceData['updated_at']);
            $invoiceData['type']         = Invoice::TYPES['one-time'];
            $invoiceData['status']       = self::DEFAULT_VALUE;
            $invoiceData['staff_id']     = $staffId;
            $invoiceData['subtotal']     = $amount;
            $invoiceData['total_due']    = self::DEFAULT_DUE;
            $invoiceData['prev_balance'] = self::DEFAULT_DUE;
            return Invoice::create($invoiceData);
        }
    }

    protected function createRefundCredit($invoice, $amount, $staffId)
    {
        return Credit::create([  
            'customer_id'       =>  $invoice->customer_id,
            'amount'            =>  $amount,
            'type'              =>  '2',
            'date'              =>  Carbon::now(),
            'payment_method'    =>  '1',
            'description'       =>  'refund-credit',
            'staff_id'          =>  $staffId,
        ]);
    }

    public function createRefundInvoiceItem($invoice, $amount, $type)
    {
        return InvoiceItem::create([
            'invoice_id'      =>  $invoice->id,
            'product_type'    =>  "refund",
            'type'            =>  InvoiceItem::TYPES[$type['0']],
            'amount'          =>  $this->tran->amount,
            'description'     =>  'Refund',
            'taxable'         =>  '0'
        ]);
    }

    public function paymentFailed(Request $request)
    {
        $customer = Customer::whereId($request->id)->with('company')->first();
        event(new PaymentFailed($customer));
    }
}

