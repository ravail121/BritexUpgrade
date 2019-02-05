<?php

namespace App\Http\Controllers\Api\V1;

use Carbon\Carbon;
use App\Model\Order;
use App\Model\Credit;
use App\Model\Invoice;
use App\Model\Customer;
use App\Model\OrderGroup;
use App\Model\InvoiceItem;
use App\Model\Subscription;
use Illuminate\Http\Request;
use App\Services\Payment\UsaEpay;
use App\Model\CustomerCreditCard;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;
use App\libs\Constants\ConstantInterface;

class PaymentController extends BaseController implements ConstantInterface
{

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
        $validation = $this->validateCredentials($request);

        if ($validation->fails()) {
            return $this->respond([
                'message' => $validation->getMessageBag()->all()
            ]);
        }

        if (!$request->order_hash) {
            return $this->respond([
                'message' => 'order_hash is required'
            ]);
        }


        $order = Order::hash($request->order_hash)->first();
        $this->tran = $this->setUsaEpayData($this->tran, $request);

        if($this->tran->Process()) {
            $msg     = $this->transactionSuccessful($request, $this->tran);
            $data    = $this->setInvoiceData($order);
            $invoice = Invoice::updateOrCreate($data);

            if (!$invoice) {
                $msg = $this->respond([
                    'message' => 'Failed to generate invoice.'
                ]);
            }

            $order->update([
                'status'     => 1, 
                'invoice_id' => $invoice->id
            ]);
            $created = $this->createInvoiceItem($order);
            
            if (!$created) {
                $msg = $this->respond([
                    'message' => 'Failed to generate invoice items.'
                ]);

            }
            
      
        } else {
            $msg = $this->transactionFail($order->id, $this->tran);
        }

        return $msg; 
    }



    protected function createInvoiceItem($order)
    {
        $subscriptions = Subscription::where('order_id', $order->id)->get();
        foreach ($subscriptions as $subscription) {
            $input = [
                'invoice_id'      => $order->invoice_id, 
                'subscription_id' => $subscription->id,
                'type'            => 1, 
                'start_date'      => $order->invoice->start_date, 
                'description'     => 'Activation Fee', 
                'amount'          => '30.00', 
                'taxable'         => 1,
            ];
            if ($subscription->device_id !== null) {
                $array = [
                    'product_type' => 'device',
                    'product_id'   => $subscription->device_id,
                ];
            } elseif ($subscription->plan_id !== null) {
                $array = [
                    'product_type' => 'plan',
                    'product_id'   => $subscription->plan_id,
                ];

            } else {
                $array = [
                    'product_type' => 'null',
                    'product_id'   => 0,
                ];

            }
            $invoiceItem = InvoiceItem::create(array_merge($input, $array));
        }


        return $invoiceItem;
        
    }


    /**
     * Sets data for `invoice` table
     * 
     * @param Order $order
     */
    protected function setInvoiceData($order)
    {
        $arr = [];
        $customer = Customer::find($order->customer_id);
        $credit = Credit::where('customer_id', $customer->id)->latest()->first();
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
                'billing_fname'           => $customer->fname, 
                'billing_lname'           => $customer->lname, 
                'billing_address_line_1'  => $card->billing_address1, 
                'billing_address_line_2'  => $card->billing_address2, 
                'billing_city'            => $card->billing_city, 
                'billing_state'           => $card->billing_state_id, 
                'billing_zip'             => $card->billing_zip, 
                'shipping_fname'          => $customer->fname, 
                'shipping_lname'          => $customer->lname, 
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
        $request->key         = env('SOURCE_KEY');
        $request->usesandbox  = self::TRAN_TRUE;
        $request->invoice     = self::TRAN_INVOICE;
        $request->isrecurring = self::TRAN_TRUE; 
        $request->savecard    = self::TRAN_TRUE; 
        $request->billcountry = self::TRAN_BILLCOUNTRY;

        return true;
    }

}
