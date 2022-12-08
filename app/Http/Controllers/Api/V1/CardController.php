<?php

namespace App\Http\Controllers\Api\V1;

use PDF;
use Exception;
use Carbon\Carbon;
use App\Model\Order;
use App\Model\Credit;
use App\Model\Invoice;
use App\Model\Customer;
use App\Model\InvoiceItem;
use App\Events\InvoiceEmail;
use Illuminate\Http\Request;
use App\Events\InvoiceAutoPaid;
use App\Model\CustomerCreditCard;
use App\Services\Payment\UsaEpay;
use App\Events\AccountUnsuspended;
use App\Events\FailToAutoPaidInvoice;
use App\Http\Controllers\BaseController;
use App\libs\Constants\ConstantInterface;
use App\Http\Controllers\Api\V1\Traits\CronLogTrait;

/**
 * Class CardController
 *
 * @package App\Http\Controllers\Api\V1
 */
class CardController extends BaseController implements ConstantInterface
{
	use CronLogTrait;
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
	 * @var
	 */
	public $response;

	/**
	 * @var Carbon
	 */
	public $carbon;

	/**
	 * CardController constructor.
	 *
	 * @param UsaEpay $tran
	 * @param Carbon  $carbon
	 */
	public function __construct(UsaEpay $tran, Carbon $carbon)
    {
        $this->tran = $tran;
        $this->carbon = $carbon;
    }

	/**
	 * This function fetches all credit cards of particular customer
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
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
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
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
	 * @param      $request
	 * @param null $command
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
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
	                $data    = $this->setInvoiceData($order, $request);
	                $invoice = Invoice::create($data);

	                if ($invoice) {
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
                }
            } else {
                $this->response = $this->transactionFail($order, $this->tran);
                if($request->without_order){
                    return response()->json(['message' => ' Card  ' . $this->tran->result . ', '. $this->tran->error, 'transaction' => $this->tran]);
                }
            }
        } else {
            $this->response = $this->transactionFail(null, $this->tran);
	        if($request->without_order){
		        return response()->json(['message' => ' Card  ' . $this->tran->result . ', '. $this->tran->error, 'transaction' => $this->tran]);
	        }
        }
        return $this->respond($this->response);
    }


	/**
	 * Validates if all fields are required
	 * @param $request
	 *
	 * @return false|\Illuminate\Http\JsonResponse
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
	 * @param $request
	 *
	 * @return mixed
	 */
    protected function setConstantData($request)
    {
        $request->invoice     = self::TRAN_INVOICE;
        $request->isrecurring = self::TRAN_TRUE; 
        $request->savecard    = self::TRAN_TRUE; 
        $request->billcountry = self::TRAN_BILLCOUNTRY;

        return $request;
    }

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
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

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	protected function validateCardId($data)
    {
        return CustomerCreditCard::whereId($data['customer_credit_card_id'])->exists();
    }

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function primaryCard(Request $request)
    {
        $data = $request->validate([
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

	/**
	 * @param Request $request
	 */
	public function autoPayInvoice(Request $request)
    {
        $date = Carbon::today()->addDays(1);
        $customers = Customer::where([
            ['billing_end', '<=', $date], ['auto_pay', Customer::AUTO_PAY['enable']
        ]])->with('unpaidMounthlyInvoice', 'company')->get()->toArray();

        $customersB = Customer::where([
            ['billing_start', '=', Carbon::today()], ['auto_pay', Customer::AUTO_PAY['enable']
        ]])->with('unpaidAndClosedMounthlyInvoice', 'company')->get()->toArray();

        $customers = array_merge($customers, $customersB);

        foreach ($customers as $key => $customer) {
            $customer_obj = Customer::find($customer["id"]);
	        $invoice = Invoice::where([['customer_id', $customer_obj->id], ['status', Invoice::INVOICESTATUS['open'] ],['type', Invoice::TYPES['monthly']]])->first();
	        $amount_due = $customer_obj->amount_due;
	        $customer['mounthlyInvoice'] = [
		        'total_due'     => $amount_due,
		        'subtotal'      => $amount_due,
		        'start_date'    => $invoice ? $invoice->start_date : $customer_obj->billing_start,
		        'end_date'      => $invoice ? $invoice->end_date : $customer_obj->billing_end
	        ];
            if($amount_due <= 0){
                continue;
                \Log::info(array("skipping autopaying...", $customer["id"], $customer["email"], $amount_due));
            }
            \Log::info(array("autopaying...", $customer["id"], $customer["email"], $amount_due));
            $api_key = $customer["company"]["api_key"];
            
            try {
                if($amount_due){

                    $card = CustomerCreditCard::where(
                        'customer_id', $customer['id']
                    )->orderBy('default', 'desc')->first();

                    if($card){
                        $order_hash = "";

                        $request_params = array(
			                'customer_id'    => $customer_obj->id,
                            'credit_card_id' => $card->id,
                            'amount'         => $amount_due,
                            'order_hash'     => $order_hash,
                            'staff_id'       => 5,
		      	            'without_order'  => true,
                            'key'            => $customer_obj->company->usaepay_api_key,
                            'usesandbox'     => $customer_obj->company->usaepay_live_formatted
                        );


                        $request->replace($request_params);
                        $response = $this->chargeCard($request);
                        if(isset($response->getData()->original->success) && $response->getData()->original->success =="true") {
                            $request->headers->set('authorization', $api_key);
                            event(new InvoiceAutoPaid($customer));
                            \Log::info("AutoPaid for customer ".$customer['id']);
                            $customer_obj->account_suspended ? $customer_obj->update(['account_suspended' => 0]) && event(new AccountUnsuspended($customer)) : null;
	                        $logEntry = [
		                        'name'      => 'Auto Pay',
		                        'status'    => 'success',
		                        'payload'   => json_encode($customer),
		                        'response'  => 'Auto Paid Successfully for customer ' .$customer['id']
	                        ];

	                        $this->logCronEntries($logEntry);
                        }else{
                            $request->headers->set('authorization', $api_key);
                            $message = $response;
                            try{
                                $message = $response->getData()->message;
                            }catch(Exception $e){

                            }

                            \Log::info($message);
                            
                            event(new FailToAutoPaidInvoice($customer, $message));
                            $order = new Order();
                            $order->customer_id = $customer['id'];
                            $paymentLog = $this->createPaymentLogs($order, $this->tran, 0, $card, null);
                            \Log::info("AutoPaid Failed for customer ".$customer['id']);
	                        $logEntry = [
		                        'name'      => 'Auto Pay',
		                        'status'    => 'error',
		                        'payload'   => json_encode($customer),
		                        'response'  => 'Auto Paid failed for customer ' .$customer['id']
	                        ];

	                        $this->logCronEntries($logEntry);
                        }
                    }else{
                        $request->headers->set('authorization', $api_key);
                        event(new FailToAutoPaidInvoice($customer, 'No Saved Card Found in our Record'));
	                    $logEntry = [
		                    'name'      => 'Auto Pay',
		                    'status'    => 'error',
		                    'payload'   => json_encode($customer),
		                    'response'  => 'No Saved Card Found in our record for customer ' .$customer['id']
	                    ];

	                    $this->logCronEntries($logEntry);
                    }
                }
            } catch (Exception $e) {
	            $logEntry = [
		            'name'      => 'Auto Pay',
		            'status'    => 'error',
		            'payload'   => json_encode($request->all()),
		            'response'  => $e->getMessage().' on line '.$e->getLine().' in CardController'
	            ];

	            $this->logCronEntries($logEntry);
                \Log::info($e->getMessage().' on line '.$e->getLine().' in CardController');
            }
        }
    }

	/**
	 * This function charges now card without order which is done by admin
	 * from admin portal
	 * inserts data to credits and customer_credit_cards table
	 * @param $request
	 * @param $tran
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
    protected function transactionSuccessfulWithoutOrder($request, $tran)
    {
        $credit = $this->createCredits($request, $tran);
        $invoice = $this->processCreditInvoice($request, $tran, $credit);
        $this->payUnpaiedInvoice($tran->amount, $request, $credit);

        $response = response()->json(['success' => true, 'transaction' => $tran]);
        return $response;
    }

	/**
	 * @param Request $request
	 */
	public function payCreditToInvoice(Request $request)
    {
        $credit = Credit::find($request->creditId);
        $this->payUnpaiedInvoice($credit->amount, $credit, $credit);
    }

	/**
	 * @param $tranAmount
	 * @param $request
	 * @param $credit
	 */
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

	/**
	 * @param $data
	 * @param $tran
	 * @param $credit
	 *
	 * @return null
	 */
	public function processCreditInvoice($data, $tran, $credit)
    {
        $card = CustomerCreditCard::find($data['credit_card_id']);
        $customer = Customer::find($card->customer_id);
        // create invoice other than payment_type is "Manual Payment"
        if(!empty($data->get('payment_type')) && $data->get('payment_type') != 'Manual Payment') {
            $staff_id = $data->staff_id;
            if($staff_id){

            }else{
                $staff_id = null;
            }
            $invoiceStartDate = $this->getInvoiceDates($customer);
            $invoiceEndDate = $this->getInvoiceDates($customer, 'end_date');
            $invoiceDueDate = $this->getInvoiceDates($customer, 'due_date', true);
            $invoice = [
                'staff_id'                  => $staff_id,
                'customer_id'               => $card->customer_id,
                'type'                      => '2',
                'start_date'                => $invoiceStartDate,
                'end_date'                  => $invoiceEndDate,
                'status'                    => '2',
                'subtotal'                  => $data['amount'],
                'total_due'                 => '0',
                'prev_balance'              => '0',
                'payment_method'            => '1',
                'notes'                     => '',
                'due_date'                  => $invoiceDueDate,
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
                'shipping_zip'              => $customer->shipping_zip
            ];

            $newInvoice = Invoice::create($invoice);
            $credit->update(['invoice_id' => $newInvoice->id]);

            $type = '9';
            $product_type = $data['payment_type'] ?: 'Manual Payment';
            $description = $data['description'] ?: 'Manual Payment';
            if ($data['payment_type'] == 'Custom Charge') {
                $product_type = '';
                $type = '3';
                $credit->update(['applied_to_invoice' => 1]);
                /**
                 * Add to credit_to_invoice table
                 */
                $credit->usedOnInvoices()->create([
                    'invoice_id'    => $newInvoice->id,
                    'amount'        => $newInvoice->subtotal,
                    'description'   => "{$newInvoice->subtotal} applied on invoice id {$newInvoice->id}",
                ]);
            }

            $invoiceItem = [
                'invoice_id'        => $newInvoice->id,
                'product_type'      => $product_type,
                'type'              => $type,
                'subscription_id'   => $data['subscription_id'],
                'start_date'        => Carbon::today(),
                'description'       => $description,
                'amount'            => $data['amount'],
                'taxable'           => '0',
            ];

            $newInvoiceItem = InvoiceItem::create($invoiceItem);
            $paymentLog = $this->createPaymentLogs(null, $tran, 1, $card, $newInvoice);
            if ($data['payment_type'] == 'Custom Charge') {
                $invoice = $newInvoice;
                $pdf = PDF::loadView('templates/custom-charge-invoice', compact('invoice'));
                event(new InvoiceEmail($invoice, $pdf, 'custom-charge'));
            }

            return $newInvoice;
        }
        $order = new Order();
        $order->customer_id = $card->customer_id;
        $paymentLog = $this->createPaymentLogs($order, $tran, 1, $card, null);
        return null;
    }

	/**
	 * Sets data for `invoice` table
	 * @param $order
	 * @param $credit
	 * @param $request
	 *
	 * @return array
	 */
	protected function setInvoiceData($order, $request, $credit=null)
	{
		$arr = [];
		$costumer = Customer::find($order->customer_id);
		if (!$costumer) {
			return $arr;
		}

		$card = CustomerCreditCard::where('customer_id', $costumer->id)->latest()->first();

		if ($card) {
			$invoiceStartDate = $this->getInvoiceDates($costumer);
			$invoiceEndDate = $this->getInvoiceDates($costumer, 'end_date');
			$invoiceDueDate = $this->getInvoiceDates($costumer, 'due_date');
			$arr = [
				'customer_id'             => $costumer->id,
				'type'                    => self::DEFAULT_VALUE,
				'status'                  => self::DEFAULT_VALUE,
				'start_date'              => $invoiceStartDate,
				'end_date'                => $invoiceEndDate,
				'due_date'                => $invoiceDueDate,
				'subtotal'                => $credit ? $credit->amount : 0,
				'total_due'               => self::DEFAULT_DUE,
				'prev_balance'            => self::DEFAULT_DUE,
				'payment_method'          => $credit ? $credit->payment_method : 'USAePay',
				'notes'                   => 'notes',
				'business_name'           => $costumer->company_name,
				'billing_fname'           => $request->billing_fname ?? $costumer->fname,
				'billing_lname'           => $request->billing_lname ?? $costumer->lname,
				'billing_address_line_1'  => $card->billing_address1,
				'billing_address_line_2'  => $card->billing_address2,
				'billing_city'            => $card->billing_city,
				'billing_state'           => $card->billing_state_id,
				'billing_zip'             => $card->billing_zip,
				'shipping_fname'          => $costumer->shipping_fname,
				'shipping_lname'          => $costumer->shipping_lname,
				'shipping_address_line_1' => $costumer->shipping_address1,
				'shipping_address_line_2' => $costumer->shipping_address2,
				'shipping_city'           => $costumer->shipping_city,
				'shipping_state'          => $costumer->shipping_state_id,
				'shipping_zip'            => $costumer->shipping_zip,
			];
		}
		return $arr;
	}
}




