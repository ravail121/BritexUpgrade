<?php

namespace App\Http\Controllers\Api\V1\Invoice;

use PDF;
use Validator;
use Carbon\Carbon;
use App\Model\Tax;
use App\Model\Sim;
use App\Model\Plan;
use App\Model\Order;
use App\Model\Addon;
use App\Model\Device;
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
use App\Model\CustomerStandaloneSim;
use App\Model\CustomerStandaloneDevice;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Events\InvoiceGenerated;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;
use App\Classes\GenerateMonthlyInvoiceClass;


class InvoiceController extends BaseController
{
    const DEFAULT_INT = 1;
    const DEFAULT_ID  = 0;
    const SIM_TYPE    = 'sim';
    const PLAN_TYPE   = 'plan';
    const ADDON_TYPE  = 'addon';
    const DEVICE_TYPE = 'device';
    const DESCRIPTION = 'Activation Fee';

    /**
     * Date-Time variable
     * 
     * @var $carbon
     */
    public $carbon;


    public $input;



    /**
     * Sets current date variable
     * 
     * @param Carbon $carbon
     */
    public function __construct(Carbon $carbon)
    {
        $this->carbon = $carbon;
        $this->input  = [];
    }





    // /**
    //  * []
    //  *  
    //  * @param  Request $request
    //  * @return void
    //  */
    // public function startBilling(Request $request)
    // {
    //     $customer = null;

    //     return $this->respond($customer);
    // }





    /**
     * Creates invoice_item and sends email with invoice.pdf attachment
     * 
     * @param  Request    $request
     * @return Response
     */
    public function oneTimeInvoice(Request $request)
    {
        $msg = '';
        if ($request->data_to_invoice) {
            $invoice = $request->data_to_invoice;
            if (isset($invoice['subscription_id'])) {


                $subscription = Subscription::find($invoice['subscription_id'][0]);
                $order        = $this->updateCustomerDates($subscription);

                $invoiceItem = $this->subscriptionInvoiceItem($invoice['subscription_id']);

                $msg = (!$invoiceItem) ? 'Subscription Invoice item was not generated' : 'Invoice item generated successfully.'; 
                
            }
            if (isset($invoice['customer_standalone_device_id'])) {


                $standaloneDevice = CustomerStandaloneDevice::find($invoice['customer_standalone_device_id'][0]);

                $order = $this->updateCustomerDates($standaloneDevice);
                        
                $invoiceItem = $this->standaloneDeviceInvoiceItem($invoice['customer_standalone_device_id']);

                $msg = (!$invoiceItem) ? 'Standalone Device Invoice item was not generated' : 'Invoice item generated successfully.' ;


            }

            if (isset($invoice['customer_standalone_sim_id'])) {


                $standaloneSim = CustomerStandaloneSim::find($invoice['customer_standalone_sim_id'][0]);
                $order = $this->updateCustomerDates($standaloneSim);
                        
                $invoiceItem = $this->standaloneSimInvoiceItem($invoice['customer_standalone_sim_id']);

                $msg = (!$invoiceItem) ? 'Standalone Sim Invoice item was not generated' : 'Invoice item generated successfully.';

            }
        }

        if($order) {
            event(new InvoiceGenerated($order));
        }

        return $this->respond($msg);
    }







    /**
     * Generates the Invoice template and downloads the invoice.pdf file
     * 
     * @param  Request    $request
     * @return Response
     */
    public function get(Request $request)
    {
        $order = Order::hash($request->order_hash)->first();
        if ($order) {
            if ($order->invoice->type == 2) {

                $serviceCharges = $order->invoice->cal_service_charges;
                $taxes          = $order->invoice->cal_taxes;
                $credits        = $order->invoice->cal_credits;
                $totalCharges   = $order->invoice->cal_total_charges;

                $data = $this->setInvoiceData($order);

                $invoice = [
                    'service_charges' => self::formatNumber($serviceCharges),
                    'taxes'           => self::formatNumber($taxes),
                    'credits'         => self::formatNumber($credits),
                    'total_charges'   => self::formatNumber($totalCharges),
                ];

                $invoice = array_merge($data, $invoice);


                $pdf = PDF::loadView('templates/onetime-invoice', compact('invoice'))->setPaper('letter', 'portrait');

                return $pdf->download('invoice.pdf');
            }

        }
        return 'Sorry, something went wrong please try again later......';
        
    }




    protected function setInvoiceData($order)
    {

        $arr = [
            'invoice_num'           => $order->invoice->id,
            'subscriptions'         => [],
            'start_date'            => $order->invoice->start_date,
            'end_date'              => $order->invoice->end_date,
            'due_date'              => $order->invoice->due_date,
            'total_due'             => $order->invoice->total_due,
            'subtotal'              => $order->invoice->subtotal,
            'today_date'            => $this->carbon->toFormattedDateString(),
            'customer_name'         => $order->customer->full_name,
            'customer_address'      => $order->customer->shipping_address1,
            'customer_zip_address'  => $order->customer->zip_address,
        ];

        if ($order->subscriptions) {
            foreach ($order->subscriptions as $subscription) {
                $planCharges    = $subscription->cal_plan_charges;
                $onetimeCharges = $subscription->cal_onetime_charges;

                $subscriptionData = [
                    'subscription_id' => $subscription->id,
                    'plan_charges'    => self::formatNumber($planCharges),
                    'onetime_charges' => self::formatNumber($onetimeCharges),
                ];

                \Log::info($subscriptionData);


                array_push($arr['subscriptions'], $subscriptionData);
            }
        }

        return $arr;


    }




    /**
     * Formats the amount to USA standard style
     * 
     * @param  float    $amount
     * @return float         
     */
    public static function formatNumber($amount)
    {
        return number_format($amount, 2);
    }




    /**
     * Updates the customer subscription_date, billing_start and billing_end if null
     * 
     * @param  Object  $obj   Subscription, CustomerStandaloneDevice, CustomerStandaloneSim
     * @return Object  $order
     */
    protected function updateCustomerDates($obj)
    {
        $customer = Customer::find($obj->customer_id);
        $order    = Order::find($obj->order_id);


        if ($customer->subscription_start_date == null && $customer->billing_start == null  && $customer->billing_end == null) {

            $customer->update([
                'subscription_start_date' => $this->carbon->toDateString(),
                'billing_start'           => $this->carbon->toDateString(),
                'billing_end'             => $this->carbon->addMonth()->subDay()->toDateString()
            ]);
        }
        $this->input = [
            'invoice_id'  => $order->invoice_id, 
            'type'        => self::DEFAULT_INT, 
            'start_date'  => $order->invoice->start_date, 
            'description' => self::DESCRIPTION, 
            'taxable'     => self::DEFAULT_INT,

        ];
        return $order;
    }





    /**
     * Creates inovice_item for subscription
     * 
     * @param  Order      $order
     * @param  int        $subscriptionIds
     * @return Response
     */
    protected function subscriptionInvoiceItem($subscriptionIds)
    {
        $invoiceItem = null;

        foreach ($subscriptionIds as $index => $subscriptionId) {
            $subscription = Subscription::find($subscriptionId);

            $subarray = [
                'subscription_id' => $subscription->id,

            ];
                
            if ($subscription->device_id !== null) {

                $array = [
                    'product_type'    => self::DEVICE_TYPE,
                    'product_id'      => $subscription->device_id,
                ];

                if ($subscription->device_id === 0) {
                    $array = array_merge($array, [
                        'amount' => '0',
                    ]);
                } else {
                    $device = Device::find($subscription->device_id);
                    $array = array_merge($array, [
                        'amount' => $device->amount_w_plan,
                    ]);
                    
                }

                $array = array_merge($subarray, $array);
                $invoiceItem = InvoiceItem::create(array_merge($this->input, $array));
                
            }

            if ($subscription->plan_id != null) {
                $plan = Plan::find($subscription->plan_id);
                $array = [
                    'product_type' => self::PLAN_TYPE,
                    'product_id'   => $subscription->plan_id,
                    'amount'       => $plan->amount_recurring, 
                ];

                $array = array_merge($subarray, $array);

                $invoiceItem = InvoiceItem::create(array_merge($this->input, $array));
            }

            if ($subscription->sim_id != null) {
                $sim = Sim::find($subscription->sim_id);
                $array = [
                    'product_type' => self::SIM_TYPE,
                    'product_id'   => $subscription->sim_id,
                    'amount'       => $sim->amount_w_plan, 
                ];

                $array = array_merge($subarray, $array);

                $invoiceItem = InvoiceItem::create(array_merge($this->input, $array));
            }


            if ($subscription->subscriptionAddon) {
                foreach ($subscription->subscriptionAddon as $subAddon) {
                    $addon = Addon::find($subAddon->addon_id);
                    $array = [
                        'product_type' => self::ADDON_TYPE,
                        'product_id'   => $addon->id,
                        'amount'       => $addon->amount_recurring, 
                    ];
                    $array = array_merge($subarray, $array);


                    $invoiceItem = InvoiceItem::create(array_merge($this->input, $array));
                }
            }
        }


        return $invoiceItem;
        
    }





    /**
     * Creates inovice_item for customer_standalone_device
     * 
     * @param  Order      $order
     * @param  int        $deviceIds
     * @return Response
     */
    protected function standaloneDeviceInvoiceItem($standaloneDeviceIds)
    {
        $invoiceItem = null;
        $subArray = [
            'subscription_id' => 0,
            'product_type'    => self::DEVICE_TYPE,
        ];

        foreach ($standaloneDeviceIds as $index => $standaloneDeviceId) {
            $standaloneDevice = CustomerStandaloneDevice::find($standaloneDeviceId);
            $device           = Device::find($standaloneDevice->device_id);

            $array = array_merge($subArray, [
                'product_id' => $device->id,
                'amount'     => $device->amount,
            ]);
            $invoiceItem = InvoiceItem::create(array_merge($this->input, $array));
                
        }

        return $invoiceItem;   
    }





    /**
     * Creates inovice_item for customer_standalone_sim
     * 
     * @param  Order      $order
     * @param  int        $simIds
     * @return Response
     */
    protected function standaloneSimInvoiceItem($standaloneSimIds)
    {
        $invoiceItem = null;
        $subArray = [
            'subscription_id' => 0,
            'product_type'    => self::SIM_TYPE,
        ];

        foreach ($standaloneSimIds as $index => $standaloneSimId) {
            $standaloneSim = CustomerStandaloneSim::find($standaloneSimId);
            $sim           = Sim::find($standaloneSim->sim_id);

            $array = array_merge($subArray, [
                'product_id' => $sim->id,
                'amount'     => $sim->amount_alone,
            ]);

            $invoiceItem = InvoiceItem::create(array_merge($this->input, $array));
                
        }

        return $invoiceItem;   
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


}
