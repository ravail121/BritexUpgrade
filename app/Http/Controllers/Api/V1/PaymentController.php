<?php

namespace App\Http\Controllers\Api\V1;

use PDF;
use Carbon\Carbon;
use App\Model\Order;
use App\Model\Credit;
use App\Model\Invoice;
use App\Model\Customer;
use App\Model\PaymentLog;
use App\Model\InvoiceItem;
use App\Services\Eye4Fraud;
use Illuminate\Http\Request;
use App\Events\PaymentFailed;
use App\Model\CreditToInvoice;
use App\Model\PaymentRefundLog;
use App\Model\CustomerCreditCard;
use App\Services\Payment\UsaEpay;
use App\Http\Controllers\BaseController;
use App\libs\Constants\ConstantInterface;
use App\Http\Controllers\Api\V1\Traits\InvoiceTrait;

/**
 * Class PaymentController
 *
 * @package App\Http\Controllers\Api\V1
 */
class PaymentController extends BaseController implements ConstantInterface
{
    use InvoiceTrait;

	/**
	 *
	 */
	const DEFAULT_VALUE = 2;
	/**
	 *
	 */
	const DEFAULT_DUE   = '0.00';

	/**
	 * @var UsaEpay
	 */
	public $tran;

	/**
	 * @var Carbon
	 */
	public $carbon;

	/**
	 * PaymentController constructor.
	 *
	 * @param UsaEpay $tran
	 * @param Carbon  $carbon
	 */
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

			if($order->invoice_id) {
				$invoice = Invoice::find( $order->invoice_id );
			} else {
				$invoice = null;
			}

			$credit = $msg[ 'credit' ];

	        /**
	         * @internal If the invoice is already created from Bulk Order System, Don't create a new invoice
	         */
			if(!$invoice) {
				$data    = $this->setInvoiceData( $order, $credit, $request );
				$invoice = Invoice::create( $data );

				if($invoice) {
					$orderCount = Order::where( [
						[ 'status', 1 ],
						[ 'company_id', $order->company_id ]
					] )->max( 'order_num' );
					$order->update( [
						'status'         => 1,
						'invoice_id'     => $invoice->id,
						'order_num'      => $orderCount + 1,
						'date_processed' => Carbon::today()
					] );
				}
			} else {
				$invoice->update([
					'status'            => CardController::DEFAULT_VALUE,
					'subtotal'          => $credit->amount,
					'payment_method'    => $credit->payment_method,
				]);
				$order->update( [
					'status'         => 1,
					'date_processed' => Carbon::today()
				] );
			}
            
            $this->addCreditToInvoiceRow($invoice, $credit, $this->tran);

            if ($invoice) {
                $paymentLog = PaymentLog::where('order_id', $order->id);
                $paymentLog->update(['invoice_id' => $invoice->id]);

                /* start send to Ey4Fraud */
                if($order->company_id == 3){
                    try{
                        $has_device = false;
                        foreach ($order->allOrderGroup as $og) {
                            if($og->device_id > 0){
                                $has_device = true;
                                break;
                            }
                        }
                        if($has_device){
                            $response = Eye4Fraud::send_order($order, $creditCard, $paymentLog->first());
                            $success = true;
                        }
                    }catch(\Exception $err){
                        \Log::info(["Eye4Fraud error ", $err]);
                    }
                }
                /* end send to Ey4Fraud */

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

	/**
	 * @param $card
	 * @param $request
	 *
	 * @return mixed
	 */
	protected function creditCardData($card, $request)
    {
        $request->card_id             =  $card->id;
        $request->payment_card_holder =  $card->cardholder;
        $request->payment_card_no     =  $card->token;
        $request->expires_mmyy        =  $card->expiration;
        $request->payment_cvc         =  $card->cvc;
        $request->customer_id         =  $card->customer->id;
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
        	$invoiceStartDate = $this->getInvoiceDates($customer);
        	$invoiceEndDate = $this->getInvoiceDates($customer, 'end_date');
        	$invoiceDueDate = $this->getInvoiceDates($customer, 'due_date');
            $arr = [
                'customer_id'             => $customer->id,
                'type'                    => self::DEFAULT_VALUE,
                'status'                  => self::DEFAULT_VALUE,
                'start_date'              => $invoiceStartDate,
                'end_date'                => $invoiceEndDate,
                'due_date'                => $invoiceDueDate,
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
        $request->usesandbox  = \Request::get('company')->usaepay_live_formatted;
        $request->invoice     = self::TRAN_INVOICE;
        $request->isrecurring = self::TRAN_TRUE; 
        $request->savecard    = self::TRAN_TRUE; 
        $request->billcountry = self::TRAN_BILLCOUNTRY;

        return true;
    }


	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
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
        \Log::info(["pl", $data, $paymentLog->customer_id, $paymentLog->invoice_id ]);
        $customer   = Customer::find($paymentLog->customer_id);
        $request->headers->set('authorization', $customer->company->api_key);
        $msg = "failed";
        $paymentRefundLog = array();
        if($this->tran->Process()) {
            try{
                $status = PaymentRefundLog::STATUS['success'];
                $amount = $this->tran->amount;
                $invoice = null;
                $invoice_id = null;
                try{
                    $invoice = $this->createRefundInvoice($customer->id, $amount, $request);
                    sleep(1);
                }catch(\Exception $ex){
                    \Log::info(array("refund b","invoice unable to create", $ex));
                }
                if($invoice){
                    $invoice_id = $invoice->id;
                    $InvoiceItem = $this->createRefundInvoiceItem($invoice, $this->tran->amount, ['refund', 'Refund']);
                    sleep(1);
                    $msg = "success";
                    if($request->credit == '1'){
                        $credit = $this->createRefundCredit($invoice, $this->tran->amount, $request->staff_id );
                        $InvoiceItem = $this->createRefundInvoiceItem($invoice, $this->tran->amount, ['manual', 'Manual-Credit']);
                        if(!$credit){
                           $msg = "Refund Processed but fail to add credits"; 
                        }
                    }
                    $paymentRefundLog = $this->createPaymentRefundLog($paymentLog, $status, $invoice_id);
                    try{
                        $this->generateRefundInvoice($invoice, $paymentLog, false);
                    }catch(Exception $ex){
                        \Log::info(["refund generate refund invoice error ", $msg]);
                    }
                }else{
                    $msg = "Refund Processed Invoice not Created because Old Invoice not Found";
                }
            }catch(\Exception $ex){
                $msg = $ex->getMessage();
                \Log::info(["refund error", $msg]);
            }
        }else {
            
            $status = PaymentRefundLog::STATUS['fail'];
            $msg = $this->tran->error;
            $paymentRefundLog = $this->createPaymentRefundLog($paymentLog, $status);
            // failed Mail event if want
        }

        \Log::info(["refund finished.", $data['refnum'], $msg]);
        return $this->respond([
            'paymentRefundLog'  => $paymentRefundLog,
            'message'           => $msg
        ]); 
    }

	/**
	 * @param      $paymentLog
	 * @param      $status
	 * @param null $invoiceId
	 *
	 * @return mixed
	 */
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

	/**
	 * @param $customer_id
	 * @param $amount
	 * @param $request
	 *
	 * @return mixed
	 */
	protected function createRefundInvoice($customer_id, $amount, $request)
    {
        $total_due = self::DEFAULT_DUE;
        $credit = $request->credit;
        if($credit == '0'){
            $total_due = $amount;
        }
        $invoiceData = [
            'type'                      => Invoice::TYPES['one-time'],
            'status'                    => self::DEFAULT_VALUE,
            'staff_id'                  => $request->staff_id,
            'subtotal'                  => $amount,
            'total_due'                 => $total_due,
            'prev_balance'              => self::DEFAULT_DUE,
            'start_date'                => $this->carbon->toDateString(),
            'end_date'                  => $this->carbon->toDateString(),
            'due_date'                  => $this->carbon->toDateString(),
            'payment_method'            => '',
            'notes'                     => '',
            'billing_fname'             => '',
            'billing_lname'             => '',
            'billing_address_line_1'    => '',
            'billing_city'              => '',
            'billing_state'             => '',
            'billing_zip'               => '',
            'shipping_fname'            => '',
            'shipping_lname'            => '',
            'shipping_address_line_1'   => '',
            'shipping_city'             => '',
            'shipping_state'            => '',
            'shipping_zip'              => '',
            'customer_id'               => $customer_id,
        ];
        //\Log::info($invoiceData);
        return Invoice::create($invoiceData);
        
    }

	/**
	 * @param $invoice
	 * @param $amount
	 * @param $staffId
	 *
	 * @return mixed
	 */
	protected function createRefundCredit($invoice, $amount, $staffId)
    {
        $credit =  Credit::create([  
            'customer_id'       =>  $invoice->customer_id,
            'applied_to_invoice'=>  1,
            'amount'            =>  $amount,
            'type'              =>  '2',
            'date'              =>  Carbon::now(),
            'payment_method'    =>  '1',
            'description'       =>  'refund-credit',
            'staff_id'          =>  $staffId,
        ]);


        CreditToInvoice::create([
            'credit_id'     => $credit->id,
            'invoice_id'    => $invoice->id,
            'amount'        => $amount,
            'description'   => "{$invoice->subtotal} applied on invoice id {$invoice->id}",
        ]);

        return $credit;
    }

	/**
	 * @param $invoice
	 * @param $amount
	 * @param $type
	 *
	 * @return mixed
	 */
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

	/**
	 * @param Request $request
	 */
	public function paymentFailed(Request $request)
    {
        $customer = Customer::whereId($request->id)->with('company')->first();
        $request->headers->set('authorization', $customer->company->api_key);
        event(new PaymentFailed($customer));
    }
}

