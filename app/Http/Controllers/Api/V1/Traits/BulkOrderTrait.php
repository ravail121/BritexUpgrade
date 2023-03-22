<?php

namespace App\Http\Controllers\Api\V1\Traits;

use App\Model\Sim;
use App\Model\Tax;
use Carbon\Carbon;
use App\Model\Plan;
use App\Model\Order;
use App\Model\Addon;
use App\Model\Device;
use App\Model\Invoice;
use App\Model\Customer;
use App\Model\OrderGroup;
use App\Model\PlanToAddon;
use App\Model\InvoiceItem;
use App\Model\Subscription;
use Illuminate\Http\Request;
use App\Model\SubscriptionLog;
use App\Model\OrderGroupAddon;
use App\Model\SubscriptionAddon;
use App\Model\CustomerStandaloneSim;
use App\Model\CustomerStandaloneDevice;
use App\Http\Controllers\Api\V1\CardController;
use App\Http\Controllers\Api\V1\Invoice\InvoiceController;
use App\Http\Controllers\Api\V1\StandaloneRecordController;

/**
 * @author Prajwal Shrestha
 * Trait BulkOrderTrait
 *
 * @package App\Http\Controllers\Api\V1\Traits
 */
trait BulkOrderTrait
{
	use InvoiceTrait, ApiConnect;

	/**
	 * @param Request $request
	 * @param         $orderItems
	 * @param         $applySurcharge
	 *
	 * @return string
	 */
	protected function totalPriceForPreview(Request $request, $orderItems, $applySurcharge=true)
	{
		$customer = Customer::find($request->get('customer_id'));
		$price[] = $this->subTotalPriceForPreview($request, $orderItems);
 		$price[] = $this->calRegulatoryForPreview($request, $orderItems);
		$price[] = $this->getPlanActivationPricesForPreview($orderItems);
		$price[] = $this->calTaxesForPreview($request, $orderItems);
		$total = array_sum($price);
		if($applySurcharge && $customer->surcharge > 0) {
			$surcharge = ($total * $customer->surcharge) / 100;
			$total += $surcharge;
		}
		return $this->convertToTwoDecimals($total, 2);

	}

	/**
	 * @param Request $request
	 * @param         $orderItems
	 *
	 * @return string
	 */
	protected function subTotalPriceForPreview(Request $request, $orderItems)
	{
		$price[] = $this->calDevicePricesForPreview($request, $orderItems);
		$price[] = $this->getPlanPricesForPreview($request, $orderItems);
		$price[] = $this->getSimPricesForPreview($request, $orderItems);
		$price[] = $this->getAddonPricesForPreview($request, $orderItems);
		$subTotal = array_sum($price);

		return $this->convertToTwoDecimals($subTotal, 2);
	}

	/**
	 * @param          $subTotal
	 * @param Customer $customer
	 *
	 * @return string
	 */
	protected function getSurchargeAmountForPreview($subTotal, Customer $customer)
	{
		if($customer->surcharge > 0) {
			$surcharge = ( $subTotal * $customer->surcharge ) / 100;
			return $this->convertToTwoDecimals( $surcharge, 2 );
		}
	}

	/**
	 * @param         $orderItems
	 *
	 * @return float|int
	 */
	protected function calMonthlyChargeForPreview($orderItems)
	{
		$prices[] = $this->getOriginalPlanPriceForPreview($orderItems);
		$prices[] = $this->getOriginalAddonPriceForPreview($orderItems);
		return $this->convertToTwoDecimals($prices ? array_sum($prices) : 0, 2);
	}

	/**
	 * @param         $orderItems
	 *
	 * @return float|int
	 */
	protected function getOriginalPlanPriceForPreview($orderItems)
	{
		$prices = [];
		foreach ($orderItems as $orderItem) {
			if(isset($orderItem['plan_id'])){
				$plan = Plan::find($orderItem['plan_id']);
				$prices[] = $plan->amount_recurring;
			}
		}
		return $this->convertToTwoDecimals($prices ? array_sum($prices) : 0, 2);
	}

	/**
	 * @param $orderItems
	 *
	 * @return float|int
	 */
	protected function getOriginalAddonPriceForPreview($orderItems)
	{
		$prices = [];
		foreach ($orderItems as $orderItem) {
			if (isset($orderItem['addon_id'])) {
				foreach ($orderItem['addon_id'] as $addon) {
					$addon = Addon::find($addon);
					if ($addon['subscription_addon_id'] != null || $addon['is_one_time']) {
						$prices[] = [];
					} else {
						$prices[] = $addon['amount_recurring'];
					}
				}
			}
		}
		return $this->convertToTwoDecimals($prices ? array_sum($prices) : 0, 2);
	}

	/**
	 * @param Request $request
	 * @param         $orderItems
	 *
	 * @return float|int
	 */
	protected function calTaxesForPreview(Request $request, $orderItems)
	{
		$taxes = [];
		foreach ($orderItems as $orderItem) {
			$taxes[] = $this->convertToTwoDecimals($this->calTaxableItemsForPreview($request, $orderItem), 2);
		}
		return $this->convertToTwoDecimals($taxes ? array_sum($taxes) : 0, 2);
	}

	/**
	 * @param Request $request
	 * @param         $orderItem
	 *
	 * @return float|int
	 */
	protected function calTaxableItemsForPreview(Request $request, $orderItem)
	{
		$customer = Customer::find($request->input('customer_id'));
		$taxRate    = $this->taxRateForPreview($request, $customer->billing_state_id);
		$taxRate    = $taxRate ?: 0;
		$taxPercentage  = $taxRate / 100;
		$devices        = isset($orderItem['device_id']) && !$request->plan_activation ? $this->addTaxesDevicesForPreview($orderItem, $taxPercentage) : 0;
		$sims           = isset($orderItem['sim_id']) && !$request->plan_activation ? $this->addTaxesSimsForPreview($orderItem, $taxPercentage) : 0;
		$plans          = isset($orderItem['plan_id']) ? $this->addTaxesToPlansForPreview($request, $orderItem, $taxPercentage) : 0;
		$addons         = isset($orderItem['addon_id']) ? $this->addTaxesToAddonsForPreview($orderItem, $taxPercentage) : 0;
		return $this->convertToTwoDecimals($devices + $sims + $plans + $addons, 2);
	}

	/**
	 * @param Request $request
	 * @param         $stateId
	 *
	 * @return array
	 */
	protected function taxRateForPreview(Request $request, $stateId)
	{
		$company = $request->get('company');
		$rate = Tax::where('state', $stateId)
		           ->where('company_id', $company->id)
		           ->pluck('rate')
		           ->first();
		return $rate;
	}

	/**
	 * @param $orderItem
	 * @param $taxPercentage
	 *
	 * @return float|int
	 */
	public function addTaxesDevicesForPreview($orderItem, $taxPercentage)
	{
		$itemTax = [];
		$device = Device::find($orderItem['device_id']);
		if ($device->taxable) {
			$amount = isset($orderItem['plan_id']) ? $device->amount_w_plan : $device->amount;
			$itemTax[] = $taxPercentage * $amount;
		}
		return $this->convertToTwoDecimals(!empty($itemTax) ? array_sum($itemTax) : 0, 2);
	}

	/**
	 * @param $orderItem
	 * @param $taxPercentage
	 *
	 * @return float|int
	 */
	public function addTaxesSimsForPreview($orderItem, $taxPercentage)
	{
		$itemTax = [];
		$sim = Sim::find($orderItem['sim_id']);
		if ($sim->taxable) {
			$amount = isset($orderItem['plan_id']) ? $sim->amount_w_plan : $sim->amount_alone;
			$itemTax[] = $taxPercentage * $amount;
		}
		return $this->convertToTwoDecimals(!empty($itemTax) ? array_sum($itemTax) : 0, 2);
	}

	/**
	 * @param $orderItem
	 * @param $taxPercentage
	 *
	 * @return float|int
	 */
	public function addTaxesToAddonsForPreview($orderItem, $taxPercentage)
	{
		$addonTax = [];
		if ($orderItem['addon_id']) {
			foreach ($orderItem['addon_id'] as $addon) {
				$addon = Addon::find($addon);
				if ($addon['taxable'] == 1) {
					$amount = $addon['prorated_amt'] != null ? $addon['prorated_amt'] : $addon['amount_recurring'];
					$addonTax[] = $taxPercentage * $amount;
				}
			}
		}
		return !empty($addonTax) ? array_sum($addonTax) : 0;
	}


	/**
	 * @param Request $request
	 * @param         $orderItem
	 * @param         $taxPercentage
	 *
	 * @return float|int
	 */
	public function addTaxesToPlansForPreview(Request $request, $orderItem, $taxPercentage)
	{
		$planTax = [];
		$customerId = $request->get('customer_id');
		$customer = Customer::find($customerId);
		$plan = Plan::find($orderItem['plan_id']);
		if ($plan->taxable) {
			$proRatedAmount = $this->calProRatedAmount($plan->amount_recurring, $customer);
			$amount = $proRatedAmount ?: $plan->amount_recurring;
			$amount = $plan->amount_onetime ? $amount + $plan->amount_onetime : $amount;
			$planTax[] = $taxPercentage * $amount;
		}
		return $this->convertToTwoDecimals(!empty($planTax) ? array_sum($planTax) : 0, 2);
	}

	/**
	 * @param Request $request
	 * @param         $orderItems
	 *
	 * @return float|int
	 */
	private function calRegulatoryForPreview(Request $request, $orderItems)
	{
		$regulatoryFees = [];
		$customerId = $request->get('customer_id');
		$customer = Customer::find($customerId);
		foreach ($orderItems as $orderItem) {
			if (isset($orderItem['plan_id'])) {
				$plan = Plan::find($orderItem['plan_id']);
				if ($plan->regulatory_fee_type == Plan::REGULATORY_FEE_TYPES['fixed_amount']) {
					$regulatoryFees[] = $plan->regulatory_fee_amount;
				} elseif ($plan->regulatory_fee_type == Plan::REGULATORY_FEE_TYPES['percentage_of_plan_cost']) {
					$planProRatedAmount = $this->calProRatedAmount($plan->amount_recurring, $customer);
					if ($planProRatedAmount) {
						$regulatoryFees[] = $this->convertToTwoDecimals($plan->regulatory_fee_amount * $planProRatedAmount / 100, 2);
					} else {
						$regulatoryFees[] = $this->convertToTwoDecimals($plan->regulatory_fee_amount * $plan->amount_recurring / 100, 2);
					}
				}
			}
		}
		return $this->convertToTwoDecimals($regulatoryFees ? array_sum($regulatoryFees) : 0, 2);
	}

	/**
	 * @param $orderItems
	 *
	 * @return float|int
	 */
	private function getShippingFeeForPreview($orderItems)
	{
		$shippingFees = [];
		foreach ($orderItems as $orderItem) {
			if (isset($orderItem['device_id'])) {
				$device = Device::find($orderItem['device_id']);
				if ($device->shipping_fee) {
					$shippingFees[] = $device->shipping_fee;
				}
			}
			if (isset($orderItem['sim_id'])) {
				$sim = Sim::find($orderItem['sim_id']);
				if ($sim->shipping_fee) {
					$shippingFees[] = $sim->shipping_fee;
				}
			}
		}
		return $this->convertToTwoDecimals($shippingFees ? array_sum($shippingFees) : 0, 2);
	}

	/**
	 * @param Request $request
	 * @param         $orderItems
	 *
	 * @return float|int
	 */
	protected function calDevicePricesForPreview(Request $request, $orderItems)
	{
		$prices = [];
		foreach ($orderItems as $orderItem) {
			if (isset($orderItem['device_id']) && !$request->plan_activation) {
				$device = Device::find($orderItem['device_id']);
				if (isset($orderItem['plan_id'])) {
					$prices[] = $device->amount_w_plan;
				} else {
					$prices[] = $device->amount;
				}
			}
		}
		return $this->convertToTwoDecimals($prices ? array_sum($prices) : 0, 2);
	}

	/**
	 * @param Request $request
	 * @param         $orderItems
	 *
	 * @return float|int
	 */
	protected function getPlanPricesForPreview(Request $request, $orderItems)
	{
		$prices = [];
		$customerId = $request->get('customer_id');
		$customer = Customer::find($customerId);
		foreach ($orderItems as $orderItem) {
			if (isset($orderItem['plan_id'])) {
				$plan = Plan::find($orderItem['plan_id']);
				$planProRatedAmount = $this->calProRatedAmount($plan->amount_recurring, $customer);
				if ($planProRatedAmount) {
					$prices[] = $planProRatedAmount;
				} else {
					$prices[] = $plan->amount_recurring;
				}
			}
		}
		return $this->convertToTwoDecimals($prices ? array_sum($prices) : 0, 2);
	}

	/**
	 * @param $orderItems
	 *
	 * @return float|int
	 */
	protected function getPlanActivationPricesForPreview($orderItems)
	{
		$prices = [];
		foreach ($orderItems as $orderItem) {
			if (isset($orderItem['plan_id'])) {
				$plan = Plan::find($orderItem['plan_id']);
				if ( $plan->amount_onetime > 0 ) {
					$prices[] = $plan->amount_onetime;
				}
			}
		}
		return $this->convertToTwoDecimals($prices ? array_sum($prices) : 0, 2);

	}

	/**
	 * @param Request $request
	 * @param         $orderItems
	 *
	 * @return float|int
	 */
	protected function getSimPricesForPreview(Request $request, $orderItems)
	{
		$prices = [];
		foreach ($orderItems as $orderItem) {
			if(isset($orderItem['sim_id']) && !$request->plan_activation){
				$sim = Sim::find($orderItem['sim_id']);
				if (isset($orderItem['plan_id'])) {
					$prices[] = $sim->amount_w_plan;
				} else {
					$prices[] = $sim->amount_alone;
				}
			}
		}
		return $this->convertToTwoDecimals($prices ? array_sum($prices) : 0, 2);
	}

	/**
	 * @param Request $request
	 * @param         $order
	 * @param         $orderItems
	 * @param         $planActivation
	 * @param         $hasSubscription
	 * @param         $itemStatus
	 * @param         $notes
	 * @param         $numberChange
	 *
	 * @return void
	 */
	public function createInvoice(Request $request,
		$order,
		$orderItems,
		$planActivation,
		$hasSubscription,
		$itemStatus=null,
		$notes=null,
		$numberChange=false)
	{
		$notes = $notes ?: 'Bulk Order | Without Payment';
		$customer = Customer::find($request->get('customer_id'));
		if($hasSubscription) {
			$this->updateCustomerDates( $customer );
		}

		if(!$order->invoice_id) {
			$invoiceStartDate = $this->getInvoiceDates( $customer );
			$invoiceEndDate   = $this->getInvoiceDates( $customer, 'end_date' );
			$invoiceDueDate   = $this->getInvoiceDates( $customer, 'due_date', true );

			$invoice = Invoice::create( [
				'customer_id'             => $customer->id,
				'type'                    => CardController::DEFAULT_VALUE,
				'status'                  => Invoice::INVOICESTATUS[ 'open' ],
				'end_date'                => $invoiceEndDate,
				'start_date'              => $invoiceStartDate,
				'due_date'                => $invoiceDueDate,
				'subtotal'                => $this->totalPriceForPreview( $request, $orderItems ),
				'total_due'               => CardController::DEFAULT_DUE,
				'prev_balance'            => $this->getCustomerDue( $customer->id ),
				'payment_method'          => 'Bulk Order',
				'notes'                   => $notes,
				'business_name'           => $customer->company_name,
				'billing_fname'           => $customer->billing_fname ?: $customer->fname,
				'billing_lname'           => $customer->billing_lname ?: $customer->lname,
				'billing_address_line_1'  => $customer->billing_address1,
				'billing_address_line_2'  => $customer->billing_address2,
				'billing_city'            => $customer->billing_city,
				'billing_state'           => $customer->billing_state_id,
				'billing_zip'             => $customer->billing_zip,
				'shipping_fname'          => $order->shipping_fname ?: $customer->fname,
				'shipping_lname'          => $order->shipping_lname ?: $customer->lname,
				'shipping_address_line_1' => $order->shipping_address1,
				'shipping_address_line_2' => $order->shipping_address2,
				'shipping_city'           => $order->shipping_city,
				'shipping_state'          => $order->shipping_state_id,
				'shipping_zip'            => $order->shipping_zip
			] );

			$orderCount = Order::where( [
				[ 'status', 1 ],
				[ 'company_id', $customer->company_id ]
			] )->max( 'order_num' );

			$order->update( [
				'invoice_id' => $invoice->id,
				'status'     => '1',
				'order_num'  => $orderCount + 1,
			] );
		} else {
			$invoice = Invoice::find($order->invoice_id);
		}

		$this->invoiceItem($orderItems, $invoice, $planActivation, $itemStatus, $numberChange);

		/**
		 * Insert record for surcharge amount
		 */
		if($customer->surcharge > 0) {
			$totalAmountWithoutSurcharge = $this->totalPriceForPreview($request, $orderItems, false);
			$surchargeAmount = ($customer->surcharge * $totalAmountWithoutSurcharge) / 100;
			$this->surchargeInvoiceItem($invoice, $surchargeAmount);

		}
		$updateDevicesWithNoId =  $order->invoice->invoiceItem->where('product_type', 'device')->where('product_id', 0);

		foreach ($updateDevicesWithNoId as $item) {
			$item->update(
				[
					'description'   => '',
					'type'          => 3,
					'taxable'       => 0
				]
			);
		}
	}


	/**
	 * Updates the customer subscription_date, baddRegulatorFeesToSubscriptiontart and billing_end if null
	 * @param $customer
	 */
	protected function updateCustomerDates($customer)
	{
		$carbon = new Carbon();
		if (!($customer->subscription_start_date && $customer->billing_start && $customer->billing_end)) {
			$customer->update([
				'subscription_start_date' => $carbon->toDateString(),
				'billing_start'           => $carbon->toDateString(),
				'billing_end'             => $carbon->addMonthsNoOverflow(1)->subDay()->toDateString()
			]);
		}
	}

	/**
	 * @param $orderItems
	 * @param $invoice
	 * @param $planActivation
	 * @param $itemStatus
	 * @param $numberChange
	 *
	 * @return void
	 */
	protected function invoiceItem($orderItems, $invoice, $planActivation, $itemStatus, $numberChange)
	{
		$subscriptionIds = [];
		$standAloneSims = [];
		$standAloneDevices = [];
		$order = Order::where('invoice_id', $invoice->id)->first();
		foreach($orderItems as $orderItem) {
			if(isset($orderItem['subscription_id'])){
				$subscriptionId = $orderItem['subscription_id'];
				if(!$numberChange) {
					$subscriptionIds[] = $subscriptionId;
				} else {
					$this->addonsInvoiceItem( $orderItem, $invoice, $subscriptionId );
				}
			} else {
				if(isset($orderItem['sim_id']) && !isset($orderItem['device_id']) && !isset($orderItem['plan_id'])){
					$standAloneSims[] = (object) [
						'id'        => $orderItem['sim_id'],
						'sim_num'   => $orderItem['sim_num'] ?? 'null'
					];
				}
				if(isset($orderItem['device_id']) && !isset($orderItem['plan_id']) && !isset($orderItem['sim_id'])){
					$standAloneDevices[] = (object) [
						'id'        => $orderItem['device_id'],
						'imei'      => $orderItem['imei_number'] ?? 'null'
					];
				}
			}
		}
		if(!empty($subscriptionIds)){
			$this->subscriptionInvoiceItem($subscriptionIds, $invoice, $planActivation, $order);
		}
		if(!empty($standAloneSims) && !$planActivation){
			$this->standaloneSimInvoiceItem($standAloneSims, $invoice, $itemStatus);
		}
		if(!empty($standAloneDevices) && !$planActivation){
			$this->standaloneDeviceInvoiceItem($standAloneDevices, $invoice, $itemStatus);
		}
	}

	/**
	 * Create invoice item for subscription
	 * @param $subscriptionIds
	 * @param $invoice
	 * @param $planActivation
	 * @param $order
	 */
	protected function subscriptionInvoiceItem($subscriptionIds, $invoice, $planActivation, $order)
	{
		$invoiceItemArray = [
			'invoice_id'  => $invoice->id,
			'type'        => InvoiceController::DEFAULT_INT,
			'start_date'  => $invoice->start_date,
			'description' => InvoiceController::DESCRIPTION,
			'taxable'     => InvoiceController::DEFAULT_INT,
		];

		foreach ($subscriptionIds as $subscriptionId) {
			$subscription = Subscription::find($subscriptionId);

			if(!$subscription->order_num || $subscription->order_num !== $order->order_num){
				/**
				 * Update order_num for subscription
				 */
				$subscription->update([
					'order_num' => $order->order_num
				]);
			}
			$invoiceItemArray['subscription_id'] = $subscription->id;

			if ($subscription->device_id !== null && !$planActivation) {
				$invoiceItemArray['product_type']    = InvoiceController::DEVICE_TYPE;
				$invoiceItemArray['product_id']      = $subscription->device_id;

				if ($subscription->device_id === 0) {
					$invoiceItemArray['amount'] = '0';
				} else {
					$device = Device::find($subscription->device_id);
					$invoiceItemArray['type'] = 3;
					$invoiceItemArray['amount'] = $device->amount_w_plan;
					$invoiceItemArray['description'] = '';
					$invoiceItemArray['taxable'] = $device->taxable;
				}
				$invoiceItem = InvoiceItem::create($invoiceItemArray);
			}

			if ($subscription->plan_id != null) {
				$plan = Plan::find($subscription->plan_id);

				$proratedAmount = $order->planProRate($plan->id);
				$amount = $proratedAmount == null ? $plan->amount_recurring : $proratedAmount;

				$invoiceItemArray['product_type'] = InvoiceController::PLAN_TYPE;
				$invoiceItemArray['product_id'] = $subscription->plan_id;
				$invoiceItemArray['amount'] = $this->convertToTwoDecimals($amount, 2);
				$invoiceItemArray['taxable'] = $plan->taxable;
				$invoiceItemArray['description'] = '';
				$invoiceItem = InvoiceItem::create($invoiceItemArray);

				//add REGULATORY FEE charges in invoice-item table
				$this->addRegulatorFeesToSubscription(
					$subscription,
					$invoiceItem->invoice,
					self::TAX_FALSE,
					$order
				);

				//add activation charges in invoice-item table
				$this->addActivationCharges(
					$subscription,
					$invoiceItem->invoice,
					InvoiceController::DESCRIPTION
				);
			}

			if ($subscription->sim_id != null && !$planActivation) {
				$sim = Sim::find($subscription->sim_id);
				$invoiceItemArray['product_type'] = InvoiceController::SIM_TYPE;
				$invoiceItemArray['product_id'] = $subscription->sim_id;
				$invoiceItemArray['type'] = 3;
				$invoiceItemArray['amount'] = $sim->amount_w_plan;
				$invoiceItemArray['taxable'] = $sim->taxable;
				$invoiceItemArray['description'] = '';
				$invoiceItem = InvoiceItem::create($invoiceItemArray);
			}

			$subscriptionAddons = $subscription->subscriptionAddon;

			if ($subscriptionAddons) {
				foreach ($subscriptionAddons as $subAddon) {

					$addon = Addon::find($subAddon->addon_id);
					$isProrated = $order->orderGroup->where('plan_prorated_amt', '!=', null);

					if ($isProrated) {
						$proratedAmount = $order->calProRatedAmount($addon->amount_recurring);
					}
					$addonAmount    = $proratedAmount >= 0 ? $proratedAmount : $addon->amount_recurring;

					$invoiceItemArray['product_type'] = InvoiceController::ADDON_TYPE;
					$invoiceItemArray['product_id'] = $addon->id;
					$invoiceItemArray['type'] = 2;
					$invoiceItemArray['amount'] = $this->convertToTwoDecimals($addonAmount, 2);
					$invoiceItemArray['taxable'] = $addon->taxable;
					$invoiceItemArray['description'] = '';

					$invoiceItem = InvoiceItem::create($invoiceItemArray);
				}
			}
			//add taxes to subscription items only
			$this->addTaxesToSubscription(
				$subscription,
				$invoiceItem->invoice,
				InvoiceController::TAX_FALSE,
				[]
			);
		}
	}



	/**
	 * @param $orderItem
	 * @param $invoice
	 * @param $subscriptionId
	 *
	 * @return void
	 */
	protected function addonsInvoiceItem($orderItem, $invoice, $subscriptionId)
	{
		$invoiceItemArray = [
			'invoice_id'        => $invoice->id,
			'type'              => self::INVOICE_ITEM_TYPES['feature_charges'],
			'start_date'        => $invoice->start_date,
			'subscription_id'   => $subscriptionId,
			'product_type'      => InvoiceController::ADDON_TYPE
		];
		$orderGroupAddons = $orderItem->orderGroupAddon()->get();
		foreach($orderGroupAddons as $orderGroupAddon){
			$addon = Addon::find($orderGroupAddon->addon_id);
			if($addon->is_one_time){
				/**
				 * @internal Update the pending_number_change
				 */
				$subscription = Subscription::find($subscriptionId);
				$subscription->update([
					'pending_number_change' => true,
					'requested_zip'         => $orderItem->requested_zip
				]);
				/**
				 * Create subscription logs
				 */
				SubscriptionLog::create( [
					'subscription_id'   => $subscription->id,
					'company_id'        => $subscription->company->id,
					'customer_id'       => $subscription->customer->id,
					'description'       => $subscription->sim_card_num,
					'category'          => SubscriptionLog::CATEGORY['number-change-requested'],
					'old_product'       => $subscription->phone_number,
					'new_product'       => null,
					'order_num'         => $invoice->order->order_num
				]);
			} else {
				$subscriptionAddon = SubscriptionAddon::create([
					'subscription_id' => $subscriptionId,
					'addon_id'        => $addon->id,
					'status'          => SubscriptionAddon::STATUSES['for-adding']
				]);
			}
			$invoiceItemArray['product_id'] = $addon->id;
			$invoiceItemArray['amount'] = $this->convertToTwoDecimals($addon->amount_recurring, 2);
			$invoiceItemArray['taxable'] = $addon->taxable;
			$invoiceItemArray['description'] = "(Billable Addon) {$addon->description}";

			$invoiceItem = InvoiceItem::create($invoiceItemArray);
		}
		$this->addTaxesToStandalone( $invoice->order->id, self::TAX_FALSE, InvoiceController::ADDON_TYPE );
	}


	/**
	 * @param Request $request
	 * @param         $orderItems
	 *
	 * @return float|int
	 */
	protected function getAddonPricesForPreview(Request $request, $orderItems)
	{
		$prices = [];
		$customerId = $request->get('customer_id');
		$customer = Customer::find($customerId);
		foreach ($orderItems as $orderItem) {
			$addons = $orderItem['addon_id'] ??  [];
			if(!$addons){
				$addons = !is_array($orderItem) ? $orderItem->orderGroupAddon()->pluck('addon_id')->toArray() : [];
			}
			if ($addons) {
				foreach ($addons as $addon) {
					$addon = Addon::find($addon);
					$amount = $addon->amount_recurring;
					if(!$addon->is_one_time) {
						$addonProRatedAmount = $this->calProRatedAmount( $amount, $customer );
						if ( $addonProRatedAmount ) {
							$prices[] = $addonProRatedAmount;
						} else {
							$prices[] = $amount;
						}
					} else {
						$prices[] = $amount;
					}
				}
			}
		}
		return $prices ? array_sum($prices) : 0;
	}


	/**
	 * Creates invoice_item for customer_standalone_device
	 * @param $standAloneDevices
	 * @param $invoice
	 * @param $itemStatus
	 *
	 * @return null
	 */
	protected function standaloneDeviceInvoiceItem($standAloneDevices, $invoice, $itemStatus)
	{
		$invoiceItem = null;
		$invoiceItemArray = [
			'subscription_id'   => 0,
			'product_type'      => InvoiceController::DEVICE_TYPE,
			'invoice_id'        => $invoice->id,
			'start_date'        => $invoice->start_date,
		];

		if($standAloneDevices) {
			foreach ( $standAloneDevices as $standAloneDevice ) {
				CustomerStandaloneDevice::create( [
					'customer_id' => $invoice->customer_id,
					'order_id'    => $invoice->order->id,
					'order_num'   => $invoice->order->order_num,
					/**
					 * @internal since these are bulk orders, we don't want these
					 * to go into shipping status, set a special rule for these lines to complete
					 */
					'status'      => $itemStatus ?: CustomerStandaloneDevice::STATUS[ 'complete' ],
					'processed'   => StandaloneRecordController::DEFAULT_PROSSED,
					'device_id'   => $standAloneDevice->id,
					'imei'        => $standAloneDevice->imei
				] );
				$device                            = Device::find( $standAloneDevice->id );
				$invoiceItemArray[ 'product_id' ]  = $device->id;
				$invoiceItemArray[ 'type' ]        = 3;
				$invoiceItemArray[ 'amount' ]      = $device->amount;
				$invoiceItemArray[ 'taxable' ]     = $device->taxable;
				$invoiceItemArray[ 'description' ] = '';
				$invoiceItem                       = InvoiceItem::create( $invoiceItemArray );
			}
			$this->addTaxesToStandalone( $invoice->order->id, InvoiceController::TAX_FALSE, InvoiceController::DEVICE_TYPE );
		}
		return $invoiceItem;
	}

	/**
	 * Creates invoice item for customer_standalone_sim
	 * @param $standaloneSims
	 * @param $invoice
	 * @param $itemStatus
	 *
	 * @return null
	 */
	protected function standaloneSimInvoiceItem($standaloneSims, $invoice, $itemStatus)
	{
		$invoiceItem = null;
		$invoiceItemArray = [
			'product_type'      => InvoiceController::SIM_TYPE,
			'subscription_id'   => 0,
			'invoice_id'        => $invoice->id,
			'type'              => InvoiceController::DEFAULT_INT,
			'start_date'        => $invoice->start_date,
			'description'       => InvoiceController::DESCRIPTION,
			'taxable'           => InvoiceController::DEFAULT_INT,
		];

		if($standaloneSims) {
			foreach ( $standaloneSims as $standaloneSim ) {
				CustomerStandaloneSim::create( [
					'customer_id' => $invoice->customer_id,
					'order_id'    => $invoice->order->id,
					'order_num'   => $invoice->order->order_num,
					/**
					 * @internal since these are bulk orders, we don't want these
					 * to go into shipping status, set a special rule for these lines to complete
					 */
					'status'      => $itemStatus ?: CustomerStandaloneSim::STATUS[ 'complete' ],
					'processed'   => StandaloneRecordController::DEFAULT_PROSSED,
					'sim_id'      => $standaloneSim->id,
					'sim_num'     => $standaloneSim->sim_num,
				] );
				$sim                               = Sim::find( $standaloneSim->id );
				$invoiceItemArray[ 'product_id' ]  = $sim->id;
				$invoiceItemArray[ 'type' ]        = 3;
				$invoiceItemArray[ 'amount' ]      = $sim->amount_alone;
				$invoiceItemArray[ 'taxable' ]     = $sim->taxable;
				$invoiceItemArray[ 'description' ] = '';
				$invoiceItem                       = InvoiceItem::create( $invoiceItemArray );
			}
			$this->addTaxesToStandalone( $invoice->order->id, InvoiceController::TAX_FALSE, InvoiceController::SIM_TYPE );
		}
		return $invoiceItem;
	}

	/**
	 * @param Request $request
	 * @param         $orderItems
	 *
	 * @return object
	 */
	protected function getCostBreakDownPreviewForDevices(Request $request, $orderItems)
	{
		$priceDetails = (object) [];
		foreach ($orderItems as $orderItem) {
			if (isset($orderItem['device_id']) && !$request->plan_activation) {
				$device = Device::find($orderItem['device_id']);
				if (isset($orderItem['plan_id'])) {
					$prices[$device->id][] = $device->amount_w_plan;
				} else {
					$prices[$device->id][] = $device->amount;
				}
				if(isset($priceDetails->{$device->id})){
					$priceDetails->{$device->id}['prices'] = $prices[$device->id];
					$priceDetails->{$device->id}['quantity'] = count($prices[$device->id]);
				} else {
					$priceDetails->{$device->id} = [
						'device'     => $device->name,
						'prices'     => $prices[$device->id],
						'quantity'   => count($prices[$device->id])
					];
				}
			}
		}
		return $priceDetails;
	}

	/**
	 * @param $orderItems
	 *
	 * @return object
	 */
	protected function getCostBreakDownPreviewForPlans(Request $request, $orderItems)
	{
		$priceDetails = (object) [];
		$prices = [];
		$customerId = $request->get('customer_id');
		$customer = Customer::find($customerId);
		foreach ($orderItems as $orderItem) {
			if (isset($orderItem['plan_id'])) {
				$plan = Plan::find($orderItem['plan_id']);
				$planProRatedAmount = $this->calProRatedAmount($plan->amount_recurring, $customer);
				if ($planProRatedAmount) {
					$prices[$plan->id][] = $planProRatedAmount;
				} else {
					$prices[$plan->id][] = $plan->amount_recurring;
				}
				if(isset($priceDetails->{$plan->id})){
					$priceDetails->{$plan->id}['prices'] = $prices[$plan->id];
					$priceDetails->{$plan->id}['quantity'] = count($prices[$plan->id]);
				} else {
					$priceDetails->{$plan->id} = [
						'plan'      => $plan->name,
						'prices'    => $prices[$plan->id],
						'quantity'  => count($prices[$plan->id])
					];
				}
			}
		}
		return $priceDetails;
	}

	/**
	 * @param Request $request
	 * @param         $orderItems
	 *
	 * @return object
	 */
	protected function getCostBreakDownPreviewForSims(Request $request, $orderItems)
	{
		$priceDetails = (object) [];
		foreach ($orderItems as $orderItem) {
			if(isset($orderItem['sim_id']) && !$request->plan_activation){
				$sim = Sim::find($orderItem['sim_id']);
				if (isset($orderItem['plan_id'])) {
					$prices[$sim->id][] = $sim->amount_w_plan;
				} else {
					$prices[$sim->id][] = $sim->amount_alone;
				}
				if(isset($priceDetails->{$sim->id})){
					$priceDetails->{$sim->id}['prices'] = $prices[$sim->id];
					$priceDetails->{$sim->id}['quantity'] = count($prices[$sim->id]);
				} else {
					$priceDetails->{$sim->id} = [
						'sim'       => $sim->name,
						'prices'    => $prices[$sim->id],
						'quantity'  => count($prices[$sim->id])
					];
				}
			}
		}
		return $priceDetails;
	}

	/**
	 * @param Request $request
	 * @param         $orderItems
	 *
	 * @return object
	 */
	protected function getCostBreakDownPreviewForAddons(Request $request, $orderItems, Customer $customer)
	{
		$priceDetails = (object) [];
		foreach ($orderItems as $orderItem) {
			if (isset($orderItem['addon_id'])) {
				foreach ($orderItem['addon_id'] as $addon) {
					$addon = Addon::find($addon);
					$amount = $addon->amount_recurring;
					if(!$addon->is_one_time) {
						$addonProRatedAmount = $this->calProRatedAmount( $amount, $customer );
						if ( $addonProRatedAmount ) {
							$prices[$addon->id][] = $addonProRatedAmount;
						} else {
							$prices[$addon->id][] = $amount;
						}
					} else {
						$prices[$addon->id][] = $amount;
					}

					if(isset($priceDetails->{$addon->id})){
						$priceDetails->{$addon->id}['prices'] = $prices[$addon->id];
						$priceDetails->{$addon->id}['quantity'] = count($prices[$addon->id]);
					} else {
						$priceDetails->{$addon->id} = [
							'addon'     => $addon->name,
							'prices'    => $prices[$addon->id],
							'quantity'  => count($prices[$addon->id])
						];
					}
				}
			}
		}
		return $priceDetails;
	}

	/**
	 * @param $amount
	 * @param $customer
	 *
	 * @return float|int
	 */
	protected function calProRatedAmount($amount, $customer)
	{
		$today     = Carbon::today();
		$startDate = Carbon::parse($customer->billing_start);
		$endDate   = Carbon::parse($customer->billing_end);

		$numberOfDaysLeft  = $endDate->diffInDays($today);
		$totalNumberOfDays = $endDate->diffInDays($startDate);

		return (($numberOfDaysLeft + 1)/($totalNumberOfDays + 1)) * $amount;
	}

	/**
	 * @param $customer_id
	 *
	 * @return int
	 */
	protected function getCustomerDue($customer_id){
		$dues = 0;
		$invoices = Invoice::where('customer_id', $customer_id)->where('status', 1);
		foreach($invoices as $invoice){
			$dues += $invoice->total_due;
		}
		return $dues;
	}

	/**
	 * @param $customer
	 * @param $invoice
	 */
	protected function surchargeInvoiceItem($invoice, $surchargeAmount)
	{
		$invoiceItemArray = [
			'product_id'        => 0,
			'amount'            => $this->convertToTwoDecimals($surchargeAmount, 2),
			'product_type'      => InvoiceController::SURCHARGE_TYPE,
			'invoice_id'        => $invoice->id,
			'type'              => InvoiceItem::TYPES['surcharge'],
			'start_date'        => $invoice->start_date,
			'description'       => InvoiceController::SURCHARGE_DESCRIPTION,
			'taxable'           => InvoiceController::DEFAULT_INT,
		];
		InvoiceItem::create($invoiceItemArray);
	}

	/**
	 * @param     $data
	 * @param     $order
	 * @param     $order_group
	 * @param int $paidMonthlyInvoice
	 *
	 * @return mixed
	 */
	private function insertOrderGroupForBulkOrder($data, $order, $order_group, $paidMonthlyInvoice = 0)
	{
		$og_params = [];
		if(isset($data['device_id']) && $paidMonthlyInvoice == 0){
			$og_params['device_id'] = $data['device_id'];
		}
		if(isset($data['plan_id'])){

			$og_params['plan_id'] = $data['plan_id'];

			if ($order->customer && $order->compare_dates && $paidMonthlyInvoice == 0) {
				$og_params['plan_prorated_amt'] = $order->planProRate($data['plan_id']);
			}
			// delete all rows in order_group_addon table associated with this order

			$_oga = OrderGroupAddon::where('order_group_id', $order_group->id)
			                       ->get();

			$planToAddon = PlanToAddon::wherePlanId($data['plan_id'])->get();

			$addon_ids = [];

			foreach ($planToAddon as $addon) {
				array_push($addon_ids, $addon->addon_id);
			}

			foreach($_oga as $__oga){
				if (!in_array($__oga->addon_id, $addon_ids)) {
					$__oga->delete();
				}
			}
			if(isset($data['sim_num'])) {
				if ( ! isset( $data[ 'subscription_id' ] ) ) {
					$subscriptionData = $this->generateSubscriptionData( $data, $order );
					$subscription     = Subscription::create( $subscriptionData );
					$subscription_id  = $subscription->id;
				} else {
					$subscription_id = $data[ 'subscription_id' ];
				}

				if ( $subscription_id ) {
					$og_params[ 'subscription_id' ] = $subscription_id;
				}
			}
		}

		if($paidMonthlyInvoice == 0){
			if(isset($data['sim_id'])){
				$sim_id = $data['sim_id'];
				if($sim_id == 0){
					$sim_id = null;
				}
				$og_params['sim_id'] = $sim_id;
			}

			if(isset($data['sim_num'])){
				$og_params['sim_num'] = $data['sim_num'];
			}

			if(isset($data['sim_type'])){
				$og_params['sim_type'] = $data['sim_type'];
			}

			if(isset($data['porting_number'])){
				$og_params['porting_number'] = $data['porting_number'];
			}

			if(isset($data['area_code'])){
				$og_params['area_code'] = $data['area_code'];
			}

			if(isset($data['operating_system'])){
				$og_params['operating_system'] = $data['operating_system'];
			}

			if(isset($data['imei_number'])){
				$og_params['imei_number'] = $data['imei_number'];
			}

			if (isset($data['require_device'])) {
				$og_params['require_device'] = $data['require_device'];
			}
		}

		if(isset($data['addon_id'][0])){
			foreach ($data['addon_id'] as $addon) {
				$ogData = [
					'addon_id'       => $addon,
					'order_group_id' => $order_group->id
				];
				if ($order->customer && $order->compare_dates && $paidMonthlyInvoice == 0) {
					$amt = $order->addonProRate($addon);
					$oga = OrderGroupAddon::create(array_merge($ogData, ['prorated_amt' => $amt]));
				} else {
					$oga = OrderGroupAddon::create($ogData);
				}
			}
			/**
			 * @internal Added for Number Change
			 */
			if ( $data['subscription_id'] ) {
				$og_params[ 'subscription_id' ] = $data['subscription_id'];
			}
		}
		return tap(OrderGroup::findOrFail($order_group->id))->update($og_params);
	}


	/**
	 * Check if the Zip Codes are valid
	 * @param $zipCode
	 *
	 * @return bool
	 */
	private function isZipCodeValid($zipCode, $requestCompany)
	{
		return $this->isZipCodeValidInUltra($zipCode, $requestCompany);
	}

	/**
	 * Returns data as array which is to be inserted in subscription table
	 *
	 * @param  $data
	 * @param  Order  $order
	 * @return array
	 */
	protected function generateSubscriptionData($data, $order)
	{
		$plan  = Plan::find($data['plan_id']);

		if ((!isset($data['sim_type']) || $data['sim_type'] == null) && isset($data['sim_id'])) {
			$sim = Sim::find($data['sim_id']);
			$data['sim_type'] = ($sim) ? $sim->name : null;
		}
		if(!isset($data['subscription_status'])){
			$subscriptionStatus = isset($data['sim_id']) || isset($data['device_id']) ? 'shipping' : 'for-activation';
		} else {
			$subscriptionStatus = $data['subscription_status'];
		}

		$output = [
			'order_id'                         =>  $order->id,
			'customer_id'                      =>  $order->customer->id,
			'company_id'                       =>  $order->customer->company_id,
			'order_num'                        =>  $order->order_num,
			'plan_id'                          =>  $data['plan_id'],
			'status'                           =>  $plan && $plan->type === 4 ? 'active' : $subscriptionStatus,
			'sim_id'                           =>  $data['sim_id'] ?? null,
			'sim_name'                         =>  $data['sim_type'] ?? '',
			'sim_card_num'                     =>  $data['sim_num'] ?? '',
			'device_id'                        =>  $data['device_id'] ?? null,
			'device_os'                        =>  $data['operating_system'] ?? '',
			'device_imei'                      =>  $data['imei_number'] ?? '',
			'subsequent_porting'               =>  ($plan) ? $plan->subsequent_porting : 0,
			'requested_area_code'              =>  $data['area_code'] ?? '',
			'requested_zip'                    =>  $data['zip_code'] ?? '',
		];

		if($plan && $plan->type === 4){
			$output['activation_date']  = Carbon::now();
		}

		return $output;
	}

	/**
	 * Return order count
	 * @param Customer $customer
	 *
	 * @return mixed
	 */
	private function getOrderCount(Customer $customer)
	{
		return Order::where([['status', 1], ['company_id', $customer->company_id]])->max('order_num');
	}


	/**
	 * @param $zipCode
	 * @param $requestCompany
	 *
	 * @return false|\Illuminate\Support\Collection|mixed|\Psr\Http\Message\StreamInterface|string
	 */
	protected function isZipCodeValidInUltra($zipCode, $requestCompany)
	{
		$url = 'connect/zip/'.$zipCode;
		$responseFromUltra = $this->requestUltraSimValidationConnection( $url, 'get', null, false, $requestCompany );
		if($responseFromUltra){
			$responseFromUltra = $responseFromUltra['activationEligible'] ?? false;
		}
		return $responseFromUltra;
	}
}