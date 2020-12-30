<?php

namespace App\Http\Controllers\Api\V1\Traits;

use PDF;
use App\Model\Order;
use App\Model\Plan;
use App\Model\Addon;
use App\Model\Device;
use App\Model\Sim;
use App\Model\Invoice;
use App\Model\InvoiceItem;
use App\Model\Subscription;
use App\Events\InvoiceGenerated;
use App\Events\UpgradeDowngradeInvoice;
use Carbon\Carbon;
use App\Model\PendingCharge;
use App\Model\Customer;
use App\Model\CreditToInvoice;
use App\Model\PaymentRefundLog;
use App\Events\SendRefundInvoice;
use App\Model\Coupon;
use Exception;

/**
 * Trait InvoiceTrait
 *
 * @package App\Http\Controllers\Api\V1\Traits
 */
trait InvoiceTrait
{
	/**
	 * @param      $subscription
	 * @param      $invoice
	 * @param      $isTaxable
	 * @param null $order
	 * @param null $autoGeneratedOrder
	 *
	 * @return mixed
	 */
	public function addRegulatorFeesToSubscription($subscription, $invoice, $isTaxable, $order = null, $autoGeneratedOrder = null)
    {
        $amount = 0;
        $plan   = $subscription->plan;
        
        if ($plan->regulatory_fee_type == Plan::REGULATORY_FEE_TYPES['fixed_amount']) {
            $amount = $plan->regulatory_fee_amount;

        } elseif ($plan->regulatory_fee_type == Plan::REGULATORY_FEE_TYPES['percentage_of_plan_cost']) {
            if ($invoice->type != Invoice::TYPES['monthly']) {
                if($autoGeneratedOrder == 1){
                    $planAmount = $plan->amount_recurring;
                }else if($subscription->upgrade_downgrade_status == null){
                    $proratedAmount = $order ? $order->planProRate($plan->id) : null;
                    $planAmount = $proratedAmount == null ? $plan->amount_recurring : $proratedAmount;
                }else{
                    $planAmount = $plan->amount_recurring - $subscription->oldPlan->amount_recurring;
                }
            } else {
                $planAmount = $plan->amount_recurring;
            }
            $regulatoryAmount   = $plan->regulatory_fee_amount/100;
            $subscriptionAmount = $planAmount;

            $amount = $regulatoryAmount * $subscriptionAmount;
        }
        
        return $subscription->invoiceItemDetail()->create([
            'invoice_id'   => $invoice->id,
            'product_type' => '',
            'product_id'   => null,
            'type'         => InvoiceItem::INVOICE_ITEM_TYPES['regulatory_fee'],
            'start_date'   => $invoice->start_date,
            'description'  => "(Regulatory Fee) - {$plan->company->regulatory_label}",
            'amount'       => number_format($amount, 2),
            'taxable'      => $isTaxable,
        ]);
    }


	/**
	 * @param $subscription
	 * @param $invoice
	 * @param $isTaxable
	 * @param $coupons
	 */
	public function addTaxesToSubscription($subscription, $invoice, $isTaxable, $coupons)
    {
        $taxPercentage = isset($invoice->customer->stateTax->rate) ? $invoice->customer->stateTax->rate / 100 : 0;
        if ($taxPercentage > 0) {
            $taxableItems = $subscription->invoiceItemDetail->where('taxable', 1);


            $taxAmount = $taxableItems->sum('amount');
            if ($coupons) {
                $taxData = $this->couponTax($taxableItems, $coupons);
//                if ($taxData) {
                    $taxAmount = $taxData;
//                }
                // If coupon tax amount = 0, use original.
            }
            if ($taxAmount > 0) {
                $subscription->invoiceItemDetail()->create(
                    [
                        'invoice_id'   => $invoice->id,
                        'product_type' => '',
                        'product_id'   => null,
                        'type'         => InvoiceItem::INVOICE_ITEM_TYPES['taxes'],
                        'start_date'   => $invoice->start_date,
                        'description'  => "(Taxes)",
                        'amount'       => number_format($taxPercentage * $taxAmount, 2),
                        'taxable'      => $isTaxable,            
                    ]
                );
            }
        }
    }

	/**
	 * @param      $orderId
	 * @param      $isTaxable
	 * @param      $item
	 * @param null $coupons
	 */
	public function addTaxesToStandalone($orderId, $isTaxable, $item, $coupons=null)
    {

        $invoice = Order::find($orderId)->invoice;
        $taxPercentage = isset($invoice->customer->stateTax->rate) ? $invoice->customer->stateTax->rate / 100 : 0;
        
        if ($taxPercentage > 0) {
            $taxesWithoutSubscriptions  = $invoice->invoiceItem
                                            ->where('subscription_id', null)
                                            ->where('product_type', $item)
                                            ->where('taxable', 1);
            $taxAmount =  $taxesWithoutSubscriptions->sum('amount');
            if ($coupons) {
                $taxAmount = $this->couponTax($taxesWithoutSubscriptions, $coupons);
            }
            
            if ($taxAmount > 0) {
                $invoice->invoiceItem()->create(
                    [
                        'invoice_id'   => $invoice->id,
                        'subscription_id' => null,
                        'product_type' => '',
                        'product_id'   => null,
                        'type'         => InvoiceItem::INVOICE_ITEM_TYPES['taxes'],
                        'start_date'   => $invoice->start_date,
                        'description'  => "(Taxes)",
                        'amount'       => number_format($taxPercentage * $taxAmount, 2),
                        'taxable'      => $isTaxable,    
                    ]
                );
            }  
        }
    }

	/**
	 * @param $subscription
	 * @param $invoice
	 * @param $description
	 */
	public function addActivationCharges($subscription, $invoice, $description)
    {
        $plan = $subscription->plan;
        $activationFee = $plan->amount_onetime;
        if ($activationFee > 0) {
            $subscription->invoiceItemDetail()->create([
                'invoice_id'   => $invoice->id,
                'product_type' => '',
                'product_id'   => null,
                'type'         => InvoiceItem::INVOICE_ITEM_TYPES['one_time_charges'],
                'start_date'   => $invoice->start_date,
                'description'  => $description,
                'amount'       => $activationFee,
                'taxable'      => $plan->taxable, 
            ]);

        }
    }

	/**
	 * @param      $invoice
	 * @param      $isTaxable
	 * @param null $subscriptionId
	 */
	public function addTaxesToUpgrade($invoice, $isTaxable, $subscriptionId = null)
    {
        $taxPercentage = isset($invoice->customer->stateTax->rate) ? $invoice->customer->stateTax->rate  / 100 : 0;

        $taxesWithoutSubscriptions  = $invoice->invoiceItem
                                        ->where('taxable', 1)
                                        ->sum('amount');

        if ($taxesWithoutSubscriptions > 0) {
            $invoice->invoiceItem()->create(
                [
                    'invoice_id'   => $invoice->id,
                    'subscription_id' => $subscriptionId,
                    'product_type' => '',
                    'product_id'   => null,
                    'type'         => InvoiceItem::INVOICE_ITEM_TYPES['taxes'],
                    'start_date'   => $invoice->start_date,
                    'description'  => "(Taxes)",
                    'amount'       => number_format($taxPercentage * $taxesWithoutSubscriptions, 2),
                    'taxable'      => $isTaxable,            
                ]
            );
        }
    }

	/**
	 * @param       $order
	 * @param false $mail
	 * @param null  $request
	 *
	 * @return string
	 */
	public function generateInvoice($order, $mail = false, $request = null)
    {
        $request ? $request->headers->set('authorization', $order->company->api_key) : null;
        $order = Order::find($order->id);
        if ($order && $order->invoice && $order->invoice->invoiceItem) {
            $data = $this->dataForInvoice($order);
            if ($order->invoice->type == Invoice::TYPES['one-time']) {
                $planChange = $this->ifUpgradeOrDowngradeInvoice($order);
                if ($planChange) {
                    $generatePdf = PDF::loadView('templates/onetime-invoice', compact('data', 'planChange'));
//                     return View('templates/onetime-invoice', compact('data', 'planChange'));
                    $request && $mail ? event(new UpgradeDowngradeInvoice($order, $generatePdf)) : null;
                    return $generatePdf->download('Invoice.pdf');
                } else {
//                     return View('templates/onetime-invoice', compact('data'));
                    $generatePdf = PDF::loadView('templates/onetime-invoice', compact('data'));
                }
            } else {   
                $subscriptions = $this->subscriptionData($order);
                
                if (!$subscriptions) {
                    return 'Api error: missing subscriptions data';
                }
//                 return View('templates/monthly-invoice', compact('data', 'subscriptions'));
                $generatePdf = PDF::loadView('templates/monthly-invoice', compact('data', 'subscriptions'))->setPaper('letter', 'portrait');
            }
            !isset($planChange) && $request && $mail ? event(new InvoiceGenerated($order, $generatePdf)) : null;
            try {

            } catch (Exception $e) {
                \Log::info($e->getMessage());
                return 'Order placed but invoice was not generated, please try again later.';
            }
            return $generatePdf->download('Invoice.pdf');
        } else {
            return 'Sorry, we could not find the details for your invoice';
        }
        return 'Sorry, something went wrong please try again later......';
    }

	/**
	 * @param $order
	 *
	 * @return array
	 */
	public function dataForInvoice($order)
    {                                    
        $invoice = [
            'order'                         =>  $order,
            'invoice'                       =>  $order->invoice,
                                                //Had to use this because $order->invoice->invoiceItem is excluding shipping fee.
            'standalone_items'              =>  Invoice::find($order->invoice_id)->invoiceItem->where('subscription_id', null),
            'previous_bill'                 =>  $this->previousBill($order),
            'credits'                       =>  $order->invoice->creditsToInvoice
        ];
        return $invoice;
    }


	/**
	 * @param $order
	 *
	 * @return array|false
	 */
	public function subscriptionData($order)
    {
        $subscriptionIds = $order->invoice->invoiceItem->pluck('subscription_id')->toArray();
        $subscriptions   = [];

        foreach (array_unique($subscriptionIds) as $id) {
            $subscriptionsExists = Subscription::find($id);
            $subscriptionsExists ? array_push($subscriptions, $subscriptionsExists) : null;
        }
        
        if (!count($subscriptions)) { return false; }
        return $subscriptions;
    }


	/**
	 * @param $order
	 *
	 * @return array
	 */
	public function previousBill($order)
    {
        $lastInvoiceId  = $order->customer->invoice
                                ->where('type', Invoice::TYPES['monthly'])
                                ->where('id', '!=', $order->invoice_id)
                                ->max('id');
        if ($lastInvoiceId) {

            $lastInvoice        = Invoice::find($lastInvoiceId);

            $previousTotalDue   = $lastInvoice->subtotal;
            $amountPaid         = $lastInvoice->creditsToInvoice->sum('amount');
            $pending            = $previousTotalDue > $amountPaid ? $previousTotalDue - $amountPaid : 0;

            return [
                'previous_amount'    => number_format($previousTotalDue, 2),
                'previous_payment'   => number_format($amountPaid, 2),
                'previous_pending'   => number_format($pending, 2)
            ];
        }
    }

	/**
	 * @param $order
	 *
	 * @return array|false
	 */
	public function ifUpgradeOrDowngradeInvoice($order)
    {
        $subscriptionId = $order->invoice->invoiceItem->pluck('subscription_id');
        if ($subscriptionId->count()) {
            $samePlan = Invoice::onlyAddonItems($order->invoice->id);
            $subscription = Subscription::find($subscriptionId[0]);
            if ($subscription && $subscription->upgrade_downgrade_status || $samePlan) {
                $addonsChange  = $subscription->subscriptionAddon->whereIn('status', ['for-adding', 'removed', 'removal-scheduled']);
                $addonsInvoiceItem = $order->invoice->invoiceItem->where('type', InvoiceItem::TYPES['feature_charges']);
                $addonsRemoved = $addonsInvoiceItem->filter(function($addon) {
                    return !$addon->amount;
                });
                $addonsAdded = $addonsInvoiceItem->filter(function($addon) {
                    return $addon->amount;
                });
                $customer = $subscription->customerRelation;
                $nextMonthInvoice = $customer->advancePaidInvoiceOfNextMonth;
                $addonsRemoved = $addonsRemoved ? $addonsRemoved->pluck('product_id')->toArray() : false;
                $newAddons   = $addonsAdded ? $addonsAdded->pluck('amount', 'product_id')->toArray() : false;
                $addons = [];
                if ($addonsRemoved) {
                    foreach (array_unique($addonsRemoved) as $id) {
                        $addons[] = ['name' => Addon::find($id)->name, 'amount' => 0];
                    }
                }
                if ($newAddons) {
                    foreach (array_unique($newAddons) as $id => $amount) {
                        $addons[] = ['name' => Addon::find($id)->name, 'amount' => $amount * $addonsAdded->where('product_id', $id)->count()];
                    }
                }
                return [
                    'subscription' => $subscription,
                    'order' => $order,
                    'addons' => $addons,
                    'same_plan' => $samePlan,
                    'next_month_charges' => $nextMonthInvoice->count()
                ];
            }
        } else {
            return false;
        }
        
    }

	/**
	 * @param $order
	 */
	public function ifTotalDue($order)
    {
        $totalAmount    = $order->invoice->cal_total_charges;
        $paidAmount     = $order->invoice->creditsToInvoice->sum('amount');
        $totalDue       = $totalAmount > $paidAmount ? $totalAmount - $paidAmount : 0;
        if ($totalDue > 0) {
            $order->invoice->update([
                    'total_due'  => str_replace(',', '',number_format($totalDue, 2)),
                    'status' => 1 
                ]);
            PendingCharge::create([
                'customer_id' => $order->customer_id,
                'subscription_id' => 0,
                'invoice_id' => $order->invoice_id,
                'type'  => 3,
                'amount' => str_replace(',', '',number_format($totalDue)),
                'description' => 'Pending one time payment'
            ]);
        }
  
    }

	/**
	 * @param $orderId
	 *
	 * @return bool
	 */
	public function addShippingCharges($orderId)
    {
        $order = Order::find($orderId);
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
        return true;
    }

	/**
	 * @param $id
	 */
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

	/**
	 * @param      $invoice
	 * @param      $paymentLog
	 * @param bool $stopMail
	 *
	 * @return string
	 */
	public function generateRefundInvoice($invoice, $paymentLog, $stopMail = true)
    {
        $invoice = Invoice::where('id', $invoice->id)->with('customer', 'invoiceItem')->first();
        if (!$invoice) {
            return 'Error: Missing data';
        }
        $paymentRefundLog = PaymentRefundLog::where('invoice_id', $invoice->id)->with('paymentLog')->first();
        if($paymentRefundLog){
            $pdf = PDF::loadView('templates/refund-invoice', compact('invoice', 'paymentRefundLog'));
            !$stopMail ? event(new SendRefundInvoice($paymentLog, $invoice, $pdf)) : null;
            return $pdf->download('Invoice.pdf');
        }

        $pdf = PDF::loadView('templates/custom-charge-invoice', compact('invoice'));
        
        return $pdf->download('Invoice.pdf');
    }

	/**
	 * @param $items
	 * @param $coupons
	 *
	 * @return float|int
	 */
	protected function couponTax($items, $coupons)
    {
        $amount = [0];
	    $eligible_products = [];
        if ($coupons) {
            foreach ($items as $item) {
                if ($item->product_type == InvoiceItem::PRODUCT_TYPE['device']) {
                    $itemType = Coupon::PRODUCT_TYPE['device'];
                } elseif ($item->product_type == InvoiceItem::PRODUCT_TYPE['sim']) {
                    $itemType = Coupon::PRODUCT_TYPE['sim'];
                } elseif ($item->product_type == InvoiceItem::PRODUCT_TYPE['plan']) {
                    $itemType = Coupon::PRODUCT_TYPE['plan'];
                } elseif ($item->product_type == InvoiceItem::PRODUCT_TYPE['addon']) {
                    $itemType = Coupon::PRODUCT_TYPE['addon'];
                }
                $couponTaxData = $this->getCouponDiscount($coupons, $item, $itemType);
                $amount[] = $couponTaxData['amount'];
                $eligible_products[] = $couponTaxData['eligible_product'];
            }
        }
        $eligibleIds = [];
        foreach ($eligible_products as $ep) {
            foreach ($ep as $id) {
                $eligibleIds[] = $id;
            }
        }
        $amount[] = $items->whereNotIn('id', $eligibleIds)->sum('amount');
        
        return array_sum($amount);
    }

	/**
	 * @param $couponData
	 * @param $item
	 * @param $itemType
	 *
	 * @return array
	 */
	protected function getCouponDiscount($couponData, $item, $itemType)
    {
        if($couponData) {
        	$couponDiscount = 0;
        	$eligibleProduct = [];
	        foreach($couponData as $coupon) {
		        $type = $coupon[ 'coupon_type' ];
		        if ( $type == 1 ) { // Applied to all
			        $appliedTo = isset($coupon['applied_to']['applied_to_all']) ? $coupon['applied_to']['applied_to_all'] : [];
		        } elseif ( $type == 2 ) { // Applied to types
			        $appliedTo = isset($coupon['applied_to']['applied_to_types']) ? $coupon['applied_to']['applied_to_types'] : [];
		        } elseif ( $type == 3 ) { // Applied to products
			        $appliedTo = isset($coupon['applied_to']['applied_to_products']) ? $coupon['applied_to']['applied_to_products'] : [];
		        }
		        if ( count( $appliedTo ) ) {
			        foreach ( $appliedTo as $product ) {
				        if ($product['order_product_type'] == $itemType && $product['order_product_id'] == $item->product_id) {
				        	$couponDiscount += $item->amount - $product['discount'];
					        $eligibleProduct = [$item->id];
				        }
			        }
		        }
	        }
	        return [
		        'amount'            => $couponDiscount,
		        'eligible_product'  => $eligibleProduct
	        ];
        } else {
	        return ['amount' => 0, 'eligible_product' => []];
        }
    }

}