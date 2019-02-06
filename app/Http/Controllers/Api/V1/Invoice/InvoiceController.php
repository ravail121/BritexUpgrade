<?php

namespace App\Http\Controllers\Api\V1\Invoice;

use PDF;
use Validator;
use App\Model\Tax;
use Carbon\Carbon;
use App\Model\Order;
use App\Model\Coupon;
use App\Model\Company;
use App\Model\Invoice;
use App\Model\Customer;
use App\Model\InvoiceItem;
use App\Model\Subscription;
use App\Model\PendingCharge;
use App\Model\CustomerCoupon;
use App\Model\SubscriptionAddon;
use App\Model\SubscriptionCoupon;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Events\InvoiceGenerated;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;
use App\Classes\GenerateMonthlyInvoiceClass;


class InvoiceController extends BaseController
{

    /**
     * Carbon Date-Time variable
     * 
     * @var DATE
     */
    public $carbon;



    /**
     * Sets current date variable
     * 
     * @param Carbon $carbon
     */
    public function __construct(Carbon $carbon)
    {
        $this->carbon = $carbon;
    }





    /**
     * Updates the customer table with curent_date & (curent_date + 1 Month - 1 Day) and then generates invoice
     *  
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function startBilling(Request $request)
    {
        if ($request->customer_id) {
            $customer = Customer::find($request->customer_id);
            $order    = Order::where('customer_id', $request->customer_id)->first();
            
        } elseif ($request->hash) {
            $order = Order::hash($request->hash)->first();
            $customer = Customer::find($order->customer_id);
        }

        if (!$customer) {
            return $this->respond(['message' => 'Customer not found']);
        }
        $customer->update([
            'subscription_start_date' => $this->carbon->toDateString(),
            'billing_start'           => $this->carbon->toDateString(),
            'billing_end'             => $this->carbon->addMonth()->subDay()->toDateString()
        ]);

        event(new InvoiceGenerated($order));

        return $this->respond($customer);
    }




    protected function add_regulatory_fee($params, $plan){

        $params['product_type'] = '';
        $params['product_id'] = null;
        $params['type'] = 5;
        $params['taxable'] = 0;

        $fee_type = $plan->regulatory_fee_type;
        $amount = $plan->regulatory_fee_amount;

        if($fee_type ==  2){
          $amount = $amount * $params['amount'];
        }
        $params['amount'] = $amount;
        $invoice_item = InvoiceItem::create($params);

        return $amount;

    }

    protected function addTax($tax_rate, $params){
        $taxes = 0;
        //echo 'Rate :: ' . $tax_rate;
        //print_r($params);
        if($tax_rate && $params['taxable'] && in_array($params['type'], [2,3,4])){
            //echo ' Amount :: ' . $params['amount'];
            $taxes =  $tax_rate * $params['amount'];
        }
        //echo ' Taxes :: '. $taxes;
        return $taxes;
            
    }

    protected function getCustomerDue($customer_id){
        $dues = 0;
        $invoices = Invoice::where('customer_id', $customer_id)->where('status', 1);
        foreach($invoices as $invoice){
          $dues += $invoice->total_due;
        }
        return $dues;
    }

    protected function generate_customer_invoice($invoice, $customer)
    {
        $debt_amount = 0;
        $taxes = 0;
        $discounts = 0;
        $invoice_items = [];

        $tax_rate = 0;
        $tax = Tax::where('state', $customer->billing_state_id)->where('company_id', $customer->company_id)->first();
        if($tax){
          $tax_rate = ($tax->rate)/100;
        }


        $subscriptions = $customer->subscription;
        foreach($subscriptions as $subscription){

            $plan = [];

            $params = [
                'invoice_id' => $invoice->id,
                'subscription_id' => $subscription->id,
                'product_type' => 'plan',
                'type' => 1,
                'start_date' => $invoice->start_date

            ];

            if ( ($subscription->status == 'shipping' || $subscription->status == 'for-activation') ||  ($subscription->status == 'active' || $subscription->upgrade_downgrade_status == 'downgrade-scheduled')  ) {

                $plan = $subscription->plan;

                $params['product_id'] = $plan['id'];
                $params['description'] = $plan['description'];
                $params['amount'] = $plan['amount_recurring'];
                $params['taxable'] = $plan['taxable'];


            } else if ($subscription->status == 'active' || $subscription->upgrade_downgrade_status = 'downgrade-scheduled') {
                $plan = $subscription->new_plan;

                $params['product_id'] = $plan['id'];
                $params['description'] = $plan['description'];
                $params['amount'] = $plan['amount_recurring'];
                $params['taxable'] = $plan['taxable'];

            } else {
                continue;
            }

            $taxes += $this->addTax($tax_rate, $params);
            $debt_amount += $params['amount'];

            $invoice_item = InvoiceItem::create($params);
            array_push($invoice_items, ['item' => $invoice_item, 'taxes'=>$tax_rate ] );
            $debt_amount += $this->add_regulatory_fee($params, $plan);
           
        

            $subscription_addons = $subscription->subscription_addon;
            $addon = [];
            foreach($subscription_addons as $s_addon){

                if($s_addon['status'] == 'removal-scheduled' || $s_addon['status'] == 'for-removal'){
                    continue;
                }
                $addon = $s_addon->addon;
                $params = [
                    'invoice_id' => $invoice->id,
                    'subscription_id' => $s_addon['subscription_id'],
                    'product_type' => 'addon',
                    'product_id' => $addon['id'],
                    'type' => 2,
                    'description' => $addon['description'],
                    'amount' => $addon['amount_recurring'],
                    'start_date' => $invoice->start_date,
                    'taxable' => $s_addon->subscription->plan['taxable'] // Replace this with this subscription->plan->taxable
                ];

                $taxes += $this->addTax($tax_rate, $params);
                $debt_amount += $params['amount'];
                $invoice_item = InvoiceItem::create($params);
                array_push($invoice_items, ['item' => $invoice_item, 'taxes'=>$tax_rate ] );

            }

        }


        foreach($customer->pending_charge as $pending_charg){
            if($pending_charg->invoice_id ==0){
                $params = [
                    'type'=>$pending_charg['type'],
                    'amount'=>$pending_charg['amount'],
                    'description'=>$pending_charg['description']
                ];

                $taxes += $this->addTax($tax_rate, $params);
                $debt_amount += $params['amount'];
                $invoice_item = InvoiceItem::create($params);
                array_push($invoice_items, ['item' => $invoice_item, 'taxes'=>$tax_rate ] );
           
            }
        
            //update pending charge
            $pendingcharge = $pending_charg->update(['invoice_id'=>$invoice->id]);
        }


        //lookup coupons

        $coupon_discounts = 0;
        // $customer_coupon = CustomerCoupon::where('customer_id', $customer->id)->where('cycles_remaining','>' ,0)->get();

        // for($customer_coupon as $cc){

        // }
     

        //add tax
        $params = [
            'invoice_id' => $invoice->id,
            'product_type' => 'taxes',
            'type' => 7,
            'amount' => $taxes,
            'description' => 'all taxes'
        ];
        $invoice_item = InvoiceItem::create($params);
   

   
        $subtotal = $debt_amount - $coupon_discounts + $taxes;

        $dues = $this->getCustomerDue($customer->id);

        $invoice->update([
            'total_due'=>$dues,
            'subtotal'=>$subtotal
        ]);


        $billing_date = strtotime($invoice->start_date);
   
        $invoice = [
            'billing_date' => [ 
                'year' => date("y", $billing_date),
                'month' => date("m", $billing_date),
                'day' => date("d", $billing_date),
            ],
            'company' => $customer->company,
            'number' => $invoice->id,
            'period_beginning' => $invoice->start_date,
            'period_ending' => $invoice->end_date,
            'due_date' => $invoice->due_date,
            'subtotal' => $subtotal,
            'total_due' => $dues,
            'items' => $invoice_items
        ];

        $pdf = PDF::loadView('templates/invoice', compact('invoice'))->setPaper('a4', 'landscape');
        $pdf->save('invoice/invoice.pdf');

    }

    protected function generate_new_invoice($customers){
    
        foreach ($customers as $customer) {

            // check invoice type and start_date
            $invoice_type_1 = false;

            if(count($customer->invoice)){
                foreach($customer->invoice as $invoice){
                    if($invoice->type == 1 && $invoice->start_date > $customer->billing_end){
                        $invoice_type_1 = true;
                        break;
                    }
                }   
            }

            if($invoice_type_1){ continue; }

            // Add row to invoice
            $_enddate = $customer->end_date;
            $start_date = date ("Y-m-d", strtotime ($_enddate ."+1 days"));
            $end_date = date ("Y-m-d", strtotime ( $start_date ."+1 months"));
            $due_date = $customer->billing_end;
            $invoice = Invoice::create([
                'customer_id' => $customer->id,
                'end_date'=>$start_date,
                'start_date'=>$end_date,
                'due_date'=>$due_date,
                'type'=>1,
                'status'=>1,
                'subtotal'=>0,
                'total_due'=>0,
                'prev_balance'=>0
            ]);
            $this->generate_customer_invoice($invoice, $customer);
        }
    }


    public function get(Request $request){

        $order = Order::hash($request->order_hash)->first();
        $data = Invoice::find($order->invoice_id);


        // PDF::loadFile(public_path().'/templates/invoice.html')->save('templates/invoice.pdf')->stream('download.pdf');
        
         // $data = Invoice::find($request->invoice_id);
         $invoice = [
            'start_date' => $data->start_date,
            'end_date'   => $data->end_date,
            'due_date'   => $data->due_date,
            'total_due'  => $data->total_due,
            'subtotal'   => $data->subtotal,
         ];
        $pdf = PDF::loadView('templates/invoice', compact('invoice'))->setPaper('a4', 'landscape');
        $pdf->setPaper([0, 0, 1000, 1000], 'landscape');
        return $pdf->download('invoice.pdf');
        // $pdf->save('invoice/invoice.pdf');

        // return $this->respondError(['No such invoice']);

        // $invoice_id =  $request->input('invoice_id');
        // $invoice = false;
        // if($invoice_id == null){
        //     $today = date("y-m-d");
        //     $fivedayaftertoday = date('Y-m-d', strtotime($today. ' + 5 days'));

        //     $customers = Customer::with(['company', 'subscription', 'subscription.plan', 'subscription.new_plan', 'subscription.subscription_addon', 'subscription.subscription_addon.addon', 'subscription.subscription_addon.subscription.plan', 'pending_charge', 'invoice' , 'coupon'])->where(
        //             [
        //               ['billing_end' , '<=' , $fivedayaftertoday],
        //               ['billing_end', '>=', $today]
        //             ]
        //         )->whereHas('subscription', function($query)  { 
        //             $query->whereIn('status', ['active', 'shipping', 'for-activation']);
        //         })->orWhereHas('pending_charge', function($query) {
        //             $query->where('invoice_id', null);
        //         })->get();

        //     //print_r($customers);
        //     //echo count($customers);
        //     $this->generate_new_invoice($customers);

        // } else {

        //     $invoice = Invoice::find($invoice_id);
        //     if(count($invoice) < 1){
        //         return $this->respondError(['No such invoice']);
        //     }
        //     $customer = Customer::with(['company', 'subscription', 'subscription.plan', 'subscription.new_plan', 'subscription.subscription_addon', 'subscription.subscription_addon.subscription.plan', 'pending_charge', 'invoice' , 'coupon'])->find($invoice->customer_id);
        //     $this->generate_customer_invoice($invoice, $customer);

        // }
    
     
        // return $this->respond(['Done']);
        
    }


}
