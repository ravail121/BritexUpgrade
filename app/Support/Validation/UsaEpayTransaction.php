<?php

namespace App\Support\Validation;

use Validator;
use Carbon\Carbon;
use App\Model\Order;
use App\Model\Coupon;
use App\Model\Credit;
use App\Model\Invoice;
use App\Model\Customer;
use App\Model\PaymentLog;
use App\Model\OrderCoupon;
use App\Model\Subscription;
use App\Model\CustomerCreditCard;
use App\Events\AccountUnsuspended;
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
   protected function setUsaEpayData($tran, $request, $command = null)
   {
        $orderhash = $request->order_hash;
        $order = Order::whereHash($orderhash)->first();
        $data = $this->couponData($order, $request);
        $this->stringReplacement($request);

        if($command){
            $tran->command = $command;
        }
       $tran->key         = $data['key'];
       $tran->pin         = env('UsaEpay_PIN');
       $tran->usesandbox  = $data['usesandbox'];
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

   public function couponData($order, $request)
   {
    //IN case of adding a new card $order = null commented
        if($order == null){
            $orderCoupon = null;
        }else{    
            $orderCoupon = OrderCoupon::where('order_id', $order->id)->first();
        }

        if($orderCoupon) {

           $coupon = Coupon::where('id', $orderCoupon->coupon_id)->first();

           if($coupon->code == env('COUPON_CODE')) {

                $data['key'] = env('SOURCE_KEY_SANDBOX');
                $data['usesandbox'] = \Request::get('company') ?\Request::get('company')->usaepay_live_formatted : $order->company->usaepay_live_formatted;

                return $data;
           }

        }
        $defaultData['key'] = \Request::get('company') ?\Request::get('company')->usaepay_api_key : $order->company->usaepay_api_key;
        
        $defaultData['usesandbox'] = \Request::get('company') ?\Request::get('company')->usaepay_live_formatted : $order->company->usaepay_live_formatted;
        
        
        return $defaultData;
   }

    private function getOrder($request)
    {
        $order = null;

        if ($request->order_hash) {
            $order = Order::where('hash', $request->order_hash)->first();
            
        } elseif ($request->customer_id) {
            $order = Order::where('customer_id', $request->customer_id)->first();

        }

        return $order;
    }


    /**
     * This function inserts data to payment_logs, credits and customer_credit_cards table
     * 
     * @param  Order     $order
     * @param  Request   $request
     * @return Response
     */
    protected function transactionSuccessful($request, $tran, $invoice = null)
    {
        $data = ['success' => false];
        $order = $this->getOrder($request);

        if(!$order){
            return ['success' => false];
        }
        $data['card'] = $this->createCustomerCard($request, $order, $tran);
        
        if(!$tran->command == 'authonly'){
            $data['payment_log'] = $this->createPaymentLogs($order, $tran, 1, $data['card']['card']);
            $data['credit']      = $this->createCredits($order, $tran, $invoice);
        }

        $data['success'] = true;
        
        return $data;
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
        $credit = $this->createCredits($request, $tran, $request->description);
        $tranAmount = $tran->amount;
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
        if($invoices[0]){
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
        $this->accountSuspendedAccount( $request->customer_id);
        }

        $response = response()->json(['success' => true, 'transaction' => $tran]);

        return $response;
    }

    protected function accountSuspendedAccount($customerId)
    {
        $customer = Customer::find($customerId);
        if($customer->account_suspended){
            $count = Invoice::where([
                ['customer_id', $customer->id],
                ['status', '!=', Invoice::INVOICESTATUS['closed']]
            ])->count();

            if($count == 0){
                $this->updateSub($customer);
            }
        }
    }

    protected function updateSub($customer)
    {
        $subscription = Subscription::where('customer_id', $customer->id)->get();
        $suspendedSubscriptions = $subscription->where('status', Subscription::STATUS['suspended']);
        $pastDueSubscriptions = $subscription->where('sub_status', Subscription::SUB_STATUSES['account-past-due']);

        foreach ($suspendedSubscriptions as $key => $suspendedSubscription) {
            $suspendedSubscription->update([
                'status'     => Subscription::STATUS['active'],
                'sub_status' => Subscription::SUB_STATUSES['for-restoration'],
            ]); 
        }

        foreach ($pastDueSubscriptions as $key => $pastDueSubscription) {
            $pastDueSubscription->update([
                'sub_status' => '',
            ]);
        }

        $customer->update([
            'account_suspended' => 0,
        ]);
        event(new AccountUnsuspended($customer));
    }


    protected function updateInvoice($invoice ,$amount, $status)
    {
        $invoice->update( [
            'total_due' => $amount,
            'status'    => $status,
        ] );
    }

    protected function updateCredit($applied ,$credit)
    {
        $credit->update( [
            'applied_to_invoice' => $applied,
            'description' => "$credit->description (One Time New Invoice)",
        ] );
    }

    public function addCreditToInvoiceRowNonOrder($invoice, $credit, $amount)
    {        
        return $credit->usedOnInvoices()->create([
            'invoice_id'  => $invoice->id,
            'amount'      => $amount,
            'description' => "{$invoice->subtotal} applied on invoice id {$invoice->id}",
        ]);

        return Credit::create($credit);
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
        return ['message' => $tranFail->error];
    }



    /**
     * This function inserts data to payment_logs table
     * 
     * @param  int  $order_id
     * @return Response
     */
    protected function createPaymentLogs($order, $tran, $response, $card = null)
    {
        return PaymentLog::create([
            'customer_id'            => $order->customer_id, 
            'order_id'               => $order->id,
            // 'invoice_id'             => $order->invoice_id, 
            'transaction_num'        => $tran->refnum, 
            'processor_customer_num' => $tran->authcode, 
            'status'                 => $response,
            'error'                  => $tran->error,
            'exp'                    => $tran->exp,
            'last4'                  => $card ? substr($card->last4, -4) : null,
            'card_type'              => $card ? $card->card_type : null,
            'amount'                 => $tran->amount,
            'card_token'             => $card ? $card->token : null,
        ]);
    }



    /**
     * This function inserts data to credits table
     * 
     * @param  Order       $data
     * it recives order instance but in cade admin does manual payment it recives Request unstance
     * @return Response
     */
    protected function createCredits($data, $tran, $invoice)
    {
        $creditData = [
            'customer_id' => $data->customer_id,
            'amount'      => $tran->amount,
            'date'        => date("Y/m/d"),
            'description' => $tran->cardType . ' '.substr($tran->last4, -4),
        ];

        if(isset($data->without_order)){
            $creditData ['staff_id'] = $data->staff_id;
            $creditData ['applied_to_invoice'] = '0';
            $creditData ['description'] = $creditData ['description']. ' '.$data->description;
        }else{
            $creditData ['order_id'] = $data->id;
        }

        $credit = Credit::create($creditData);

        // Some attributes are set via default() method
        // and are not returned in create()
        return Credit::find($credit->id);
    }

    public function addCreditToInvoiceRow($invoice, $credit, $tran)
    {
        $credit->update( [
            'applied_to_invoice' => true,
            'description' => "$credit->description (One Time New Invoice)",
        ] );
        
        return $credit->usedOnInvoices()->create([
            'invoice_id'  => $invoice->id,
            'amount'      => $tran->amount,
            'description' => "{$invoice->subtotal} applied on one-time-new-invoice id {$invoice->id}",
        ]);

        return Credit::create($credit);
    }



    /**
     * This function inserts data to customer_credit_cards table
     * 
     * @param  Request $request
     * @return Order object
     */
    protected function createCustomerCard($request, $order, $tran)
    {   
        if (!$request->card_id) {
            $customerCreditCard = CustomerCreditCard::where('customer_id', $order->customer_id)->get();
            if(isset($customerCreditCard[0])){
                $count = $customerCreditCard
                ->where('expiration', $request->expires_mmyy)
                ->where('cvc', $request->payment_cvc)
                ->count();
                $default = 0;
            }else{
                $count = 0;
                $default = 1;
            }
                
            if ($count == 0) {    
                $customerCreditCard = CustomerCreditCard::create([
                    'token'            => $tran->cardref,
                    'api_key'          => $order->company->api_key, 
                    'customer_id'      => $order->customer_id, 
                    'cardholder'       => $request->payment_card_holder,
                    'expiration'       => $request->expires_mmyy,
                    'last4'            => $tran->last4,
                    'default'          => $default,
                    'card_type'        => $tran->cardType,
                    'cvc'              => $request->payment_cvc,
                    'billing_address1' => $request->billing_address1, 
                    'billing_address2' => $request->billing_address2, 
                    'billing_city'     => $request->billing_city, 
                    'billing_state_id' => $request->billing_state_id, 
                    'billing_zip'      => $request->billing_zip,
                ]);
                $customer = Customer::find($order->customer_id);
                if($request->auto_pay){
                    $customer->update(['auto_pay' => '1']);
                }else{
                    $customer->update(['auto_pay' => '0']);
                }
                if ($customer->billing_address1 == 'N/A') {
                    $customer->update([
                        'billing_fname'    => $request->billing_fname, 
                        'billing_lname'    => $request->billing_lname, 
                        'billing_address1' => $request->billing_address1, 
                        'billing_address2' => $request->billing_address2, 
                        'billing_city'     => $request->billing_city, 
                        'billing_state_id' => $request->billing_state_id, 
                        'billing_zip'      => $request->billing_zip,
                    ]);
                }
                return ['card' => $customerCreditCard];
            }
        }
        $customerCreditCard = CustomerCreditCard::find($request->card_id);
        return ['card' => $customerCreditCard];
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


    protected function setUsaEpayDataForRefund($tran, $request)
    {
        $couponData = $this->couponData(null, $request);

        $tran->key         = $couponData['key'];
        $tran->usesandbox  = $couponData['usesandbox'];
        $tran->command     = 'cc:refund';
        $tran->refnum      = $request->refnum;
        $tran->amount      = $request->amount;
        $tran->invoice     = $request->invoice;
        $tran->email       = $request->email;

        flush();

       return $tran;
   }



}
