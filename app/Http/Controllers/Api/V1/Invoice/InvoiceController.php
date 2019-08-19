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
use App\Model\Credit;
use App\Model\InvoiceItem;
use App\Model\OrderGroup;
use App\Model\OrderGroupAddon;
use App\Model\Subscription;
use App\Model\PendingCharge;
use App\Model\CustomerCoupon;
use App\Model\PaymentRefundLog;
use App\Model\SubscriptionAddon;
use App\Model\SubscriptionCoupon;
use App\Model\CustomerStandaloneSim;
use App\Model\CustomerStandaloneDevice;
use App\Http\Controllers\Api\V1\CronJobs\InvoiceTrait;
use App\libs\Constants\ConstantInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Events\InvoiceGenerated;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;
use App\Classes\GenerateMonthlyInvoiceClass;
use App\Model\CreditToInvoice;

class InvoiceController extends BaseController implements ConstantInterface
{
    use InvoiceTrait;

    const DEFAULT_INT = 1;
    const DEFAULT_ID  = 0;
    const SIM_TYPE    = 'sim';
    const PLAN_TYPE   = 'plan';
    const ADDON_TYPE  = 'addon';
    const DEVICE_TYPE = 'device';
    const DESCRIPTION = 'Activation Fee';
    const SHIPPING    = 'Shipping Fee';
    const ONETIME     = 3;
    const TAXES       = 7;
    const COUPONS     = 6;

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
            // \Log::info('Invoice: -'.$invoice);
            if (isset($invoice['subscription_id'])) {
                $subscription = Subscription::find($invoice['subscription_id'][0]);

                $order       = $this->updateCustomerDates($subscription);

                $invoiceItem  = $this->subscriptionInvoiceItem($invoice['subscription_id']);

                $msg = (!$invoiceItem) ? 'Subscription Invoice item was not generated' : 'Invoice item generated successfully.'; 

                $order = Order::where('hash', $request->hash)->first();

                $currentInvoice =  $order->invoice;
                
            }
            if(isset($invoice['same_subscription_id'])){
                $invoiceItem  = $this->samePlanUpgradeInvoiceItem($invoice['same_subscription_id'], $request->order_id);
            }
            if (isset($invoice['customer_standalone_device_id'])) {
               
                $orderId = CustomerStandaloneDevice::find($invoice['customer_standalone_device_id'])->first()->order_id;

                $deviceId = is_array($invoice['customer_standalone_device_id']) ? $invoice['customer_standalone_device_id'][0] : $invoice['customer_standalone_device_id'];

                $standaloneDevice = CustomerStandaloneDevice::find($deviceId);
                
                $order = $this->updateCustomerDates($standaloneDevice);
               
                $invoiceItem = $this->standaloneDeviceInvoiceItem($invoice['customer_standalone_device_id']);
               
                $taxes = $this->addTaxesToStandalone(Order::find($orderId)->invoice, self::TAX_FALSE, self::DEVICE_TYPE);
               
                $msg = (!$invoiceItem) ? 'Standalone Device Invoice item was not generated' : 'Invoice item generated successfully.' ;
            }
            if (isset($invoice['customer_standalone_sim_id'])) {

                $orderId = CustomerStandaloneSim::find($invoice['customer_standalone_sim_id'])->first()->order_id;

                $standaloneSim = CustomerStandaloneSim::find($invoice['customer_standalone_sim_id'][0]);

                $order = $this->updateCustomerDates($standaloneSim);
                        
                $invoiceItem = $this->standaloneSimInvoiceItem($invoice['customer_standalone_sim_id']);

                $taxes = $this->addTaxesToStandalone(Order::find($orderId)->invoice, self::TAX_FALSE, self::SIM_TYPE);

                $msg = (!$invoiceItem) ? 'Standalone Sim Invoice item was not generated' : 'Invoice item generated successfully.';

            }

            $order = Order::where('hash', $request->hash)->first();
            
            $currentInvoice =  $order->invoice;

            $this->storeCoupon($request->couponAmount, $request->couponCode, $currentInvoice);

        }else if($request->status == 'Without Payment'){
            $this->createInvoice($request);
            return $this->respond($msg);
        }


        $order = Order::where('hash', $request->hash)->first();

        $this->addShippingCharges($order);
    
        if ($request->customer_id) {
            $this->availableCreditsAmount($request->customer_id);
        }

        $this->ifTotalDue($order);

        if ($order->invoice->status === 2){
            $startDate = $order->invoice->start_date;
            $order->invoice->update(
                [
                    'due_date' => $startDate
                ]
            );
        }

        $updateDevicesWithNoId =  $order->invoice->invoiceItem->where('product_type', 'device')->where('product_id', 0);

        foreach ($updateDevicesWithNoId as $item) {
            $item->update(
                [
                'description' => '',
                'type'  => 3,
                'taxable' => 0
                ]
            );
        }

        if (isset($order)) {
            $request->headers->set('authorization', $order->company->api_key);
            event(new InvoiceGenerated($order));
        }

        return $this->respond($msg);
    }

    public function getTax(Request $request)
    {
        $order      = Order::where('hash', $request->hash)->first();
        $taxes      = $order->invoice->invoiceItem->where('type', self::TAXES)->sum('amount');
        $shipping   = $order->invoice->invoiceItem->where('description', self::SHIPPING)->sum('amount');
        return ['taxes' => $taxes, 'shipping' => $shipping];
    }

    public function getCoupons(Request $request)
    {
        $coupons = Order::where('hash', $request->hash)->first()->invoice->invoiceItem->where('type', self::COUPONS)->sum('amount');
        return ['coupons' => $coupons];
    }
   
    public function storeCoupon($couponAmount, $couponCode, $invoice)
    {
        \Log::info('code: '.$couponCode);
        if ($couponCode) {
            //store coupon in invoice_items.
            if ($couponAmount) {
                $invoice->invoiceItem()->create(
                    [
                        'subscription_id' => null,
                        'product_type'    => '',
                        'product_id'      => null,
                        'type'            => InvoiceItem::TYPES['coupon'],
                        'description'     => "(Coupon) ".$couponCode,
                        'amount'          => $couponAmount,
                        'start_date'      => $invoice->start_date,
                        'taxable'         => self::TAX_FALSE,
                    ]
                );
            }

            $couponNumUses   = Coupon::where('code', $couponCode)->pluck('num_uses')->first();

            Coupon::where('code', $couponCode)->update([
                'num_uses' => $couponNumUses + 1 
            ]);

            //store coupon in customer_coupon table if eligible
            $coupon         = Coupon::where('code', $couponCode)->first();
            $couponCycles   = $coupon->num_cycles;
            $couponId       = $coupon->id;

            $customerCoupon = [
                'customer_id'       => $invoice->customer_id,
                'coupon_id'         => $couponId,
                
            ];

            $customerCouponInfinite = [
                'cycles_remaining'  => -1
            ];

            $customerCouponFinite = [
                'cycles_remaining'  => $couponCycles - 1
            ];

            if ($couponCycles > 0) {

                $data = array_merge($customerCoupon, $customerCouponFinite);
                CustomerCoupon::create($data);

            } elseif ($couponCycles == 0) {
                
                $data = array_merge($customerCoupon, $customerCouponInfinite);
                CustomerCoupon::create($data);

            }
        }
    }

    protected function ifTotalDue($order)
    {
        $totalAmount    = $order->invoice->subtotal;
        $paidAmount     = $order->invoice->creditsToInvoice->sum('amount');
        $totalDue       = $totalAmount > $paidAmount ? $totalAmount - $paidAmount : 0;
       
        $order->invoice->update(
            [
                'total_due'     => $totalDue
            ]
        );
    }

    /**
     * Generates the Invoice template and downloads the invoice.pdf file
     * 
     * @param  Request    $request
     * @return Response
     */
    public function get(Request $request)
    {
        if($request->refundInvoiceId){
            
            $invoice = Invoice::where('id', $request->refundInvoiceId)->with('customer', 'invoiceItem')->first();

            $paymentRefundLog = PaymentRefundLog::where('invoice_id', $invoice->id)->with('paymentLog')->first();

            if($paymentRefundLog){
                // return view('templates/refund-invoice', compact('invoice', 'paymentRefundLog'));

                $pdf = PDF::loadView('templates/refund-invoice', compact('invoice', 'paymentRefundLog'));
                return $pdf->download('invoice.pdf');
            }else{
                 return 'Sorry, we could not find any refund Invoice';
            }
        }else{
            $order = Order::hash($request->order_hash)->first();
        }
        return $this->generateInvoice($order);
    }

    public static function formatNumber($amount)
    {
        return number_format($amount, 2);
    }


    /**
     * Updates the customer subscription_date, baddRegulatorFeesToSubscriptiontart and billing_end if null
     * 
     * @param  Object  $obj   Subscription, CustaddRegulatorFeesToSubscriptiondaloneDevice, CustomerStandaloneSim
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
        
        $paidInvoice = 0;
        $invoiceItem = null;
        $order = Order::where('invoice_id', $this->input['invoice_id'])->first();
        foreach ($subscriptionIds as $index => $subscriptionId) {
            $subscription = Subscription::find($subscriptionId);

            $subarray = [
                'subscription_id' => $subscription->id,

            ];
                
            if ($subscription->device_id !== null && $subscription->upgrade_downgrade_status == null) {
                
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
                        'type'   => 3,
                        'amount' => $device->amount_w_plan,
                        'description' => '',
                        'taxable' => $device->taxable
                    ]);
                    
                }

                $array = array_merge($subarray, $array);
                $invoiceItem = InvoiceItem::create(array_merge($this->input, $array));
                
            }else{
                $date = Carbon::today()->addDays(6)->endOfDay();
                $invoice = Invoice::where([['customer_id', $subscription->customerRelation->id],['type', '1'],['status','2']])->whereBetween('start_date', [Carbon::today()->startOfDay(), $date])->first();
                if(isset($invoice)){
                    $paidInvoice = 1;
                }
            }

            if ($subscription->plan_id != null) {
                $plan = Plan::find($subscription->plan_id);

                if($subscription->upgrade_downgrade_status == null){
                    $proratedAmount = $order->planProRate($plan->id);
                    $amount = $proratedAmount == null ? $plan->amount_recurring : $proratedAmount;
                }else{
                    $amount = $plan->amount_recurring - $subscription->oldPlan->amount_recurring;
                }

                $array = [
                    'product_type' => self::PLAN_TYPE,
                    'product_id'   => $subscription->plan_id,
                    'amount'       => $amount,
                    'taxable'      => $plan->taxable,
                    'description'  => ''
                ];
                if($subscription->upgrade_downgrade_status =='for-upgrade'){
                    
                    $array['description'] = 'Upgrade from plan '.$subscription->old_plan_id.' to plan '.$subscription->plan_id;  
                }
                
                $array = array_merge($subarray, $array);

                $invoiceItem = InvoiceItem::create(array_merge($this->input, $array));

                if($subscription->upgrade_downgrade_status == null){
                    //add REGULATORY FEE charges in invoice-item table
                    $regulatoryFee = $this->addRegulatorFeesToSubscription(
                        $subscription,
                        $invoiceItem->invoice,
                        self::TAX_FALSE,
                        $order
                    );

                    //add activation charges in invoice-item table
                    $this->addActivationCharges(
                        $subscription, 
                        $invoiceItem->invoice,
                        self::DESCRIPTION,
                        self::TAX_FALSE
                    );
                }elseif($paidInvoice == 1){
                    $invoiceItem = InvoiceItem::create(array_merge($this->input, $array));
                }   
            }

            if ($subscription->sim_id != null && $subscription->upgrade_downgrade_status == null) {
                $sim = Sim::find($subscription->sim_id);
                $array = [
                    'product_type' => self::SIM_TYPE,
                    'product_id'   => $subscription->sim_id,
                    'type'         => 3,
                    'amount'       => $sim->amount_w_plan, 
                    'taxable'      => $sim->taxable,
                    'description'  => ''
                ];

                $array = array_merge($subarray, $array);

                $invoiceItem = InvoiceItem::create(array_merge($this->input, $array));
            }
            
            $order = Order::where('invoice_id', $this->input['invoice_id'])->first();
            if($subscription->upgrade_downgrade_status != null){
                $orderGroupId = OrderGroup::whereOrderId($order->id)->pluck('id');
                $orderGroupAddonId = OrderGroupAddon::whereIn('order_group_id', $orderGroupId)->where('subscription_id', $subscription->id)->pluck('addon_id');


                $subscriptionAddons = $subscription->subscriptionAddon->whereIn('addon_id', $orderGroupAddonId);  
            }else{
                $subscriptionAddons = $subscription->subscriptionAddon;
            }
            

            if ($subscriptionAddons) {
                foreach ($subscriptionAddons as $subAddon) {

                    $addon = Addon::find($subAddon->addon_id);

                    if($subscription->upgrade_downgrade_status == null){
                        $isProrated = $order->orderGroup->where('plan_prorated_amt', '!=', null);

                        if ($isProrated) {
                            $proratedAmount = $order->calProRatedAmount($addon->amount_recurring);
                        }
                        $addonAmount    = $proratedAmount >= 0 ? $proratedAmount : $addon->amount_recurring; 
                    }else{
                        if($subAddon->status == 'removal-scheduled' || $subAddon->status == 'removed' ){
                            $addonAmount = 0; 
                        }else{
                            $addonAmount = $addon->amount_recurring;
                        }
                    }
                                       

                    $array = [
                        'product_type' => self::ADDON_TYPE,
                        'product_id'   => $addon->id,
                        'type'         => 2,
                        'amount'       => $addonAmount,
                        'taxable'      => $addon->taxable,
                        'description'  => ''
                    ];

                    $array = array_merge($subarray, $array);

                    $invoiceItem = InvoiceItem::create(array_merge($this->input, $array));

                    if($paidInvoice == 1 && $subscription->upgrade_downgrade_status != null){
                        $invoiceItem = InvoiceItem::create(array_merge($this->input, $array));
                    }
                }
            }

            if($subscription->upgrade_downgrade_status == null){
                //add taxes to subscription items only
                $taxes = $this->addTaxesToSubscription(
                    $subscription, 
                    $invoiceItem->invoice, 
                    self::TAX_FALSE
                );
            }else{
                $this->addTaxesToUpgrade($order->invoice, self::TAX_FALSE, $subscription->id);
            }


        }
       
        return $invoiceItem;
        
    
    }

    /**
     * Creates inovice_item for samePlanSubcription
     * 
     * @param  Order      $order
     * @param  int        $subscriptionIds
     * @return Response
     */
    protected function samePlanUpgradeInvoiceItem($subscriptionIds, $orderId)
    {
        $invoiceItem = null;
        $order = Order::find($orderId);

        foreach ($subscriptionIds as $index => $subscriptionId) {
            $subscription = Subscription::find($subscriptionId);

            $paidInvoice = 0;
            $date = Carbon::today()->addDays(6)->endOfDay();
            $mounthlyInvoice = Invoice::where([['customer_id', $subscription->customerRelation->id],['type', '1'],['status','2']])->whereBetween('start_date', [Carbon::today()->startOfDay(), $date])->first();
            if(isset($invoice)){
                $paidInvoice = 1;
            }
            
            $orderGroupId = OrderGroup::whereOrderId($order->id)->pluck('id');
            $orderGroupAddonId = OrderGroupAddon::whereIn('order_group_id', $orderGroupId)->where('subscription_id', $subscription->id)->pluck('addon_id');


            $subscriptionAddons = $subscription->subscriptionAddon->whereIn('addon_id', $orderGroupAddonId);  
            

            if ($subscriptionAddons) {
                
                foreach ($subscriptionAddons as $subAddon) {

                    $addon = Addon::find($subAddon->addon_id);

                    if($subAddon->status == 'removal-scheduled' || $subAddon->status == 'removed' ){
                        $addonAmount = 0; 
                    }else{
                        $addonAmount = $addon->amount_recurring;
                    }
                                       

                    $array = [
                        'product_type'    => self::ADDON_TYPE,
                        'product_id'      => $addon->id,
                        'type'            => 2,
                        'amount'          => $addonAmount,
                        'taxable'         => $addon->taxable,
                        'subscription_id' => $subscription->id,
                        'invoice_id'      => $order->invoice_id,
                        'start_date'      => $order->invoice->start_date,
                        'description'     => self::DESCRIPTION,
                    ];

                    $invoiceItem = InvoiceItem::create($array);

                    if($paidInvoice == 1){
                        $invoiceItem = InvoiceItem::create($array);
                    }
                }

                $this->addTaxesToUpgrade($order->invoice, self::TAX_FALSE, $subscription->id);
            }
        }   
        return $invoiceItem;    
    }

    public function availableCreditsAmount($id)
    {
        $customer = Customer::find($id);

        $credits  = $customer->creditsNotAppliedCompletely;
        
        foreach ($credits as $credit) {

            $availableCredits[] = ['id' => $credit->id, 'amount' => $credit->amount];
            
        }
        
        if (isset($availableCredits)) {

            foreach ($availableCredits as $key => $credit) {

                $notFullUsedCredit = CreditToInvoice::where('credit_id', $credit['id'])->sum('amount');

                if ($notFullUsedCredit && $notFullUsedCredit < $credit['amount']) {

                    $totalUsableCredits = $credit['amount'] - $notFullUsedCredit;
                    
                    $openInvoices = $customer->invoice->where('status', Invoice::INVOICESTATUS['open']);
                    $this->applyCreditsToInvoice($credit['id'], $totalUsableCredits, $openInvoices);
                    

                } else if (!$notFullUsedCredit) {

                    $totalUsableCredits = $credit['amount'];
                    $openInvoices = $customer->invoice->where('status', Invoice::INVOICESTATUS['open']);
                    $this->applyCreditsToInvoice($credit['id'], $totalUsableCredits, $openInvoices);

                } 
                

                
            }
        }
        
    }

    public function applyCreditsToInvoice($creditId, $amount, $openInvoices)
    {
        
        foreach ($openInvoices as $invoice) {
            
            $totalDue       = $invoice->total_due;
            $updatedAmount  = $totalDue >= $amount ? $totalDue - $amount : 0;
            $amount         = $totalDue >= $amount ? $amount : $totalDue;
            if ($totalDue > $amount) {

                Credit::where('id', $creditId)
                ->update(
                    [
                        'applied_to_invoice'  => 1
                    ]
                );
            }
            if ($totalDue != 0) {
                $invoice->update(
                    [
                        'total_due' => $updatedAmount
                    ]
                );

                $invoice->creditToInvoice()->create(
                    [
                        'credit_id'     => $creditId,
                        'invoice_id'    => $invoice->id,
                        'amount'        => $amount,
                        'description'   => "{$amount} applied to invoice id {$invoice->id}"
                    ]
                );

            }
        }
    }


    public function addShippingCharges($order)
    {

        $items = $order->invoice->invoiceItem;

        $itemWithShippingCharges  = [];

        foreach ($items as $item) {

            if ($item->product_type == InvoiceItem::PRODUCT_TYPE['device'] && $item->product_id) {

                $shippingFee        = Device::find($item->product_id)->shipping_fee;

                if ($shippingFee) { $itemWithShippingCharges[] = [
                    'amount'            => $shippingFee, 
                    'subscription_id'   => $item->subscription_id, 
                    'taxable'           => 0,
                    'invoice_id'        => $item->invoice_id,
                    'start_date'        => Carbon::today()
                ]; }

            } elseif ($item->product_type == InvoiceItem::PRODUCT_TYPE['sim']) {

                $shippingFee        = Sim::find($item->product_id)->shipping_fee;

                if ($shippingFee) { $itemWithShippingCharges[] = [
                    'amount'            => $shippingFee, 
                    'subscription_id'   => $item->subscription_id, 
                    'taxable'           => 0, 
                    'invoice_id'        => $item->invoice_id,
                    'start_date'        => Carbon::today()
                ]; }

            }

        }

        $defaultValuesToInsert = [
            'product_type' => '',
            'type'         => InvoiceItem::TYPES['one_time_charges'],
            'description'  => 'Shipping Fee',
        ];

        foreach ($itemWithShippingCharges as $items) {
            
            InvoiceItem::create(array_merge($items,$defaultValuesToInsert));

        }

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
                'type'       => 3,
                'amount'     => $device->amount,
                'taxable'    => $device->taxable,
                'description'  => ''
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
                'type'       => 3,
                'amount'     => $sim->amount_alone,
                'taxable'    => $sim->taxable,
                'description'  => ''
            ]);

            $invoiceItem = InvoiceItem::create(array_merge($this->input, $array));
                
        }

        return $invoiceItem;   
    }




    protected function add_regulatory_fee($params, $plan)
    {

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

    public function addTaxesToSubtotal($invoice)
    {
        
        $taxAmount              = $invoice->invoiceItem()->where('type', InvoiceItem::TYPES['taxes'])->sum('amount');
        $subTotalByInvoiceItems = $invoice->invoiceItem->sum('amount');
        $subTotal               = $invoice->subtotal;
        
        if ($subTotal != $subTotalByInvoiceItems && $subTotalByInvoiceItems - $subTotal == $taxAmount) {
            $invoice->update(
                [
                    'subtotal' => $taxAmount + $subTotal
                ]
            );
        }

    }



    protected function addTax($tax_rate, $params)
    {
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

            if ( ($subscription->status == 'shipping' || $subscription->status == 'for-activation') ||  ($subscription->status == 'active' || $subscription->upgrade_downgrade_status == 'downgrade-scheduled')  ) {

                $plan = $subscription->plan;

            } else if ($subscription->status == 'active' || $subscription->upgrade_downgrade_status = 'downgrade-scheduled') {
                $plan = $subscription->new_plan;

            } else {
                continue;
            }

            $params['product_id'] = $plan['id'];
            $params['description'] = $plan['description'];
            $params['amount'] = $plan['amount_recurring'];

            $params['taxable'] = $plan['taxable'];

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
            if($pending_charg->invoice_id == 0){
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
       
        //add activation charges

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
                'customer_id'  => $customer->id,
                'end_date'     => $start_date,
                'start_date'   => $end_date,
                'due_date'     => $due_date,
                'type'         => 1,
                'status'       => 1,
                'subtotal'     => 0,
                'total_due'    => 0,
                'prev_balance' => 0
            ]);
            $this->generate_customer_invoice($invoice, $customer);
            
        }
    }

    public function createInvoice(Request $request)
    {
        $data=$request->validate([
            'customer_id'    => 'required',
            'order_hash'     => 'required',
            'order_groups'   => 'required',
        ]);

        $customer = Customer::find($data['customer_id']);
        $end_date = Carbon::parse($customer->billing_end)->addDays(1);
        $order = Order::whereHash($data['order_hash'])->first();
                
        $invoice = Invoice::create([
            'customer_id'             => $customer->id,
            'end_date'                => $end_date,
            'start_date'              => $customer->billing_start,
            'due_date'                => $customer->billing_end,
            'type'                    => 2,
            'status'                  => 1,
            'subtotal'                => 0,
            'total_due'               => 0,
            'prev_balance'            => 0,
            'payment_method'          => 1,
            'notes'                   => 'notes',
            'business_name'           => $customer->company_name, 
            'billing_fname'           => $customer->billing_fname, 
            'billing_lname'           => $customer->billing_lname, 
            'billing_address_line_1'  => $customer->billing_address1, 
            'billing_address_line_2'  => $customer->billing_address2, 
            'billing_city'            => $customer->billing_city, 
            'billing_state'           => $customer->billing_state_id, 
            'billing_zip'             => $customer->billing_zip, 
            'shipping_fname'          => $order->shipping_fname, 
            'shipping_lname'          => $order->shipping_lname, 
            'shipping_address_line_1' => $order->shipping_address1, 
            'shipping_address_line_2' => $order->shipping_address2, 
            'shipping_city'           => $order->shipping_city, 
            'shipping_state'          => $order->shipping_state_id, 
            'shipping_zip'            => $order->shipping_zip,
        ]);

        $orderCount = Order::where([['status', 1],['company_id', $customer->company_id]])->max('order_num');
        

        $order->update([
            'invoice_id' => $invoice->id,
            'status' => '1',
            'order_num' => $orderCount + 1,
        ]);

        $this->createInvoiceItem($data['order_groups'], $invoice);
    }

    private function createInvoiceItem($orderGroups, $invoice)
    {
        foreach ($orderGroups as $orderGroup) {
            $subscription = Subscription::find($orderGroup['subscription']['id']);
            if($subscription->upgrade_downgrade_date_submitted == "for-upgrade"){
                $description = 'Upgrade from '.$subscription['old_plan_id'].' to '.$subscription['new_plan_id'];
            }else{
                $description = 'Downgrade from '.$subscription['old_plan_id'].' to '.$subscription['new_plan_id'];
            }
            $data = [
                'invoice_id'      => $invoice->id,
                'subscription_id' => $subscription['id'],
                'product_type'    => self::PLAN_TYPE,
                'product_id'      => $orderGroup['plan']['id'],
                'amount'          => 0,
                'start_date'      => $invoice->start_date,
                'type'            => 1,
                'taxable'         => $orderGroup['plan']['taxable'],
                'description'     => $description,
            ];
            InvoiceItem::create($data);
            if(isset($orderGroup['addons'])){
                $addonData = [
                    'invoice_id'      => $invoice->id,
                    'subscription_id' => $subscription['id'],
                    'product_type'    => self::ADDON_TYPE,
                    'amount'          => 0,
                    'type'            => 1,
                    'start_date'      => $invoice->start_date,
                    'description'     => $description,
                ];

                foreach ($orderGroup['addons'] as $addon) {

                    $addonData['product_id'] = $addon['id'];
                    $addonData['taxable'] = $addon['taxable'];

                    InvoiceItem::create($addonData);
                }
            }
        }
    }

    public function amountPaid(Request $request)
    {
        return Order::where('hash', $request->order_hash)->first()->invoice->creditsToInvoice->sum('amount');
    }
}
