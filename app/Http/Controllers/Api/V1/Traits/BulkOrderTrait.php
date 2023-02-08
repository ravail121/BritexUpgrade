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
use App\Model\OrderGroupAddon;
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
	use InvoiceTrait;

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
			if (isset($orderItem['addons'])) {
				foreach ($orderItem['addons'] as $addon) {
					if ($addon['subscription_addon_id'] != null) {
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
		return 0;
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
	 *
	 * @return void
	 */
	public function createInvoice(Request $request,
		$order,
		$orderItems,
		$planActivation,
		$hasSubscription,
		$itemStatus=null,
		$notes=null)
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

		$this->invoiceItem($orderItems, $invoice, $planActivation, $itemStatus);

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
	 *
	 * @return void
	 */
	protected function invoiceItem($orderItems, $invoice, $planActivation, $itemStatus)
	{
		$subscriptionIds = [];
		$standAloneSims = [];
		$standAloneDevices = [];
		$order = Order::where('invoice_id', $invoice->id)->first();
		foreach($orderItems as $orderItem) {
			if(isset($orderItem['subscription_id'])){
				$subscriptionIds[] = $orderItem['subscription_id'];
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
			if (isset($orderItem['addons'])) {
				foreach ($orderItem['addons'] as $addon) {
					$addon = Addon::find($addon);
					$amount = $addon->amount_recurring;
					$addonProRatedAmount = $this->calProRatedAmount($amount, $customer);
					if ($addonProRatedAmount) {
						$prices[] = $addonProRatedAmount;
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

		foreach ($standAloneDevices as $standAloneDevice) {
			CustomerStandaloneDevice::create([
				'customer_id'   => $invoice->customer_id,
				'order_id'      => $invoice->order->id,
				'order_num'     => $invoice->order->order_num,
				/**
				 * @internal since these are bulk orders, we don't want these
				 * to go into shipping status, set a special rule for these lines to complete
				 */
				'status'        => $itemStatus ?: CustomerStandaloneDevice::STATUS['complete'],
				'processed'     => StandaloneRecordController::DEFAULT_PROSSED,
				'device_id'     => $standAloneDevice->id,
				'imei'          => $standAloneDevice->imei
			]);
			$device           = Device::find($standAloneDevice->id);
			$invoiceItemArray['product_id'] = $device->id;
			$invoiceItemArray['type'] = 3;
			$invoiceItemArray['amount'] = $device->amount;
			$invoiceItemArray['taxable'] = $device->taxable;
			$invoiceItemArray['description'] = '';
			$invoiceItem = InvoiceItem::create($invoiceItemArray);
			$this->addTaxesToStandalone($invoice->order->id, InvoiceController::TAX_FALSE, InvoiceController::DEVICE_TYPE);
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

		foreach ($standaloneSims as $standaloneSim) {
			CustomerStandaloneSim::create([
				'customer_id'   => $invoice->customer_id,
				'order_id'      => $invoice->order->id,
				'order_num'     => $invoice->order->order_num,
				/**
				 * @internal since these are bulk orders, we don't want these
				 * to go into shipping status, set a special rule for these lines to complete
				 */
				'status'        => $itemStatus ?: CustomerStandaloneSim::STATUS['complete'],
				'processed'     => StandaloneRecordController::DEFAULT_PROSSED,
				'sim_id'        => $standaloneSim->id,
				'sim_num'       => $standaloneSim->sim_num,
			]);
			$sim           = Sim::find($standaloneSim->id);
			$invoiceItemArray['product_id'] =  $sim->id;
			$invoiceItemArray['type'] = 3;
			$invoiceItemArray['amount'] = $sim->amount_alone;
			$invoiceItemArray['taxable'] = $sim->taxable;
			$invoiceItemArray['description'] = '';
			$invoiceItem = InvoiceItem::create($invoiceItemArray);
			$this->addTaxesToStandalone($invoice->order->id, InvoiceController::TAX_FALSE, InvoiceController::SIM_TYPE);
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
			'subscription_id'   => 0,
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
			foreach ($data['addon_id'] as $key => $addon) {
				$ogData = [
					'addon_id'       => $addon,
					'order_group_id' => $order_group->id
				];

				if ($order->customer && $order->compare_dates && $paidMonthlyInvoice== 0) {
					$amt = $order->addonProRate($addon);
					$oga = OrderGroupAddon::create(array_merge($ogData, ['prorated_amt' => $amt]));
				} else {
					$oga = OrderGroupAddon::create($ogData);
				}
			}
		}
		return tap(OrderGroup::findOrFail($order_group->id))->update($og_params);
	}

	/**
	 * @see https://stackoverflow.com/questions/35220048/import-csv-file-to-laravel-controller-and-insert-data-to-two-tables
	 * @param $filename
	 * @param $delimiter
	 *
	 * @return array|false
	 */
	private function convertCsvRecordsToArrayAndValidateZipCodes($filename = '', $delimiter = ',')
	{
		$error = false;
	    if (!file_exists($filename) || !is_readable($filename))
	        return false;

	    $header = null;
	    $data = array();
	    if (($handle = fopen($filename, 'r')) !== false)
	    {
	        while (($row = fgetcsv($handle, 1000, $delimiter)) !== false)
	        {
	            if (!$header) {
		            $header = $row;
	            } else {
		            $data[] = array_combine( $header, $row );
	            }
	        }
			fclose($handle);
		}

		return $data;
	}

	/**
	 * List of invalid zip codes
	 * @return array
	 */
	private function inValidZipCodes()
	{
		return [
			'65260',
			'69152',
			'53931',
			'67764',
			'54520',
			'38361', '54872', '38229',
			'54611', '38201', '49459', '5753', '54764', '69138',
			'76432', '89318', '89318', '48401', '48401', '79540',
			'80941', '79540', '79540', '80941', '80941',
			'95936', '44046', '68428', '72838', '17748', '16850',
			'88264', '68179', '72630', '11004', '65054', '68172',
			'72051', '11416', '54614', '65239', '99723', '68183',
			'11224', '64654', '74571', '48745', '99551', '68197',
			'11697', '65287', '48633', '51440', '99649', '46372',
			'96763', '46117', '67516', '68588', '11221', '64745',
			'50563', '69340', '99668', '67743', '4738', '68631', '66214',
			'68139', '66061', '56453', '46922', '5042', '63560', '12749',
			'67859', '3894', '56735', '66060', '46510', '5345', '63465', '56556',
			'67063', '68061', '93033', '66554', '46539', '12494', '24150', '5405',
			'63540', '95679', '56434', '67504', '99599', '67747', '62834', '5072',
			'63557', '96106', '69033', '99749', '67544', '97465', '5750', '65250',
			'69141', '49075', '99648', '66728', '5255', '63566', '68879', '13360',
			'99689', '67849', '5453', '65334', '69037', '99753', '67428', '5446',
			'65276', '69120', '99552', '67107', '5849', '65254', '69343', '99580',
			'66755', '5661', '65034', '69039', '99772', '67526', '5001', '65244', '69337',
			'99613', '66758', '5304', '63536', '69148', '99832', '66783', '5043', '64463',
			'69331', '99761', '67049', '5492', '64730', '69027', '99577', '67578', '5842',
			'64002', '68966', '99506', '67502', '5302', '64647', '68920', '99829', '5150',
			'64638', '68622', '99669', '5043', '64770', '69358', '99680', '5665', '64740',
			'69021', '99517', '65081', '68827', '5742', '99780', '65332', '69023', '5356',
			'99505', '63501', '68872', '5340', '99827', '63543', '69167', '5035', '99653',
			'63561', '5819', '99841', '64499', '99507', '63432', '99736', '63541', '99638',
			'65320', '99566', '63431', '99529', '65340', '99803', '64780', '99729', '63533',
			'99546', '63552', '99521', '64671', '99811', '64625', '99758', '64093', '99732',
			'65274', '99712', '64620', '99708', '65338', '99656', '64628', '99771', '64445',
			'99625', '64635', '99665', '65236', '99558', '64655', '99919', '64428', '99621',
			'64630', '99709', '64632', '99697', '64643', '99610', '64657', '99622', '65321', '99825',
			'64652', '99790', '64021', '99748', '65330', '99681', '63453', '99737', '64479', '99701',
			'65345', '99776', '63565', '99692', '64752', '99514', '64467', '99683', '63547', '99751',
			'64470', '99624', '64656', '99620', '64672', '99836', '63474', '99734', '64726', '99724',
			'64489', '99549', '63546', '99830', '64442', '99550', '64673', '99745', '64676', '99722',
			'64670', '99769', '65329', '99650', '64456', '99716', '64722', '99778', '63460', '99654',
			'64733', '99925', '64680', '99756', '64486', '99520', '63430', '99694', '65336', '99662',
			'64084', '99555', '64788', '99740', '64020', '99645', '64683', '99706', '64451', '99663',
			'63545', '99746', '64067', '99651', '63535', '99770', '63437', '99631', '65281', '68844',
			'99569', '65351', '69365', '99903', '64491', '68842', '99926', '14707', '12541', '64481',
			'52147', '55379', '1263', '54861', '64648', '68862', '99602', '12960', '52207', '55305',
			'54891', '63558', '69150', '99672', '63442', '55387', '54850', '82633', '64601', '68870',
			'99711', '65263', '24217', '63964', '55378', '38327', '57450', '64642', '69301', '99518',
			'65042', '24612', '73960', '55420', '57345', '65339', '68817', '99655', '24185', '55435',
			'57580', '68976', '99826', '68959', '47959', '55054', '57323', '15485', '4570', '68822',
			'99721', '69153', '61466', '55318', '57045', '16843', '4764', '68967', '99922', '68929',
			'55345', '4231', '61535', '90640', '78657', '47849', '90222', '21017', '27040', '68937',
			'99929', '69357', '55088', '4346', '61282', '90090', '78828', '47245', '90094', '21031',
			'28659', '69036', '99641', '68982', '55431', '61733', '90038', '78638', '47201', '90404',
			'21639', '27155', '69345', '99754', '68874', '55337', '61738', '90028', '78842', '47845',
			'90306', '21042', '27202', '68814', '99634', '68801', '99785', '61544', '90041', '78039',
			'47618', '90501', '20763', '58355', '99152', '69140', '99821', '61462', '21645', '24458',
			'3809', '58460', '98670', '68881', '99636', '3255', '46581', '59743', '58273', '89496',
			'71433', '69157', '99784', '3771', '46538', '59432', '58067', '5665', '56650', '67028',
			'54111', '69131', '99591', '59058', '67664', '5669', '67557', '54489', '69026', '99522',
			'67748', '69166', '99766', '5654', '67112', '54128', '69034', '99923', '80483', '67637',
			'68852', '99619', '79834', '54429', '69190', '99726', '94172', '86054', '40355', '7945',
			'37660', '27834', '89030', '55433', '77423', '68936', '5081', '54405', '99684', '68824',
			'99585', '94141', '86512', '40201', '7004', '37341', '28590', '89131', '55309', '77505', '68638',
			'99691', '5770', '54488', '68833', '99628', '94151', '86538', '40066', '7945', '37304', '27557', '89031', '55029', '77393', '68859', '99548',
			'5356', '54760', '68971', '99515', '39836', '94164', '85365', '40224', '7188', '37870', '28571', '89086', '55445', '77092', '68946', '99603',
			'5032', '54978', '68878', '99782', '94163', '85942', '40004', '7035', '37397', '27910', '89033', '55303', '77518', '95595', '69122', '99928',
			'5823', '68945', '99730', '12444', '94126', '86302', '40370', '7828', '37641', '27553', '89087', '55422', '77089', '95451', '68828', '99609',
			'5363', '68853', '99633', '12460', '94144', '86434', '40603', '7006', '37733', '27882', '89084', '55040', '77021', '68813', '99686', '5753',
			'68876', '99554', '54832', '94137', '85544', '40110', '7403', '37621', '28556', '89036', '55359', '77037', '69045', '99727', '5345', '68837', '99565', '94120', '86511',
			'40166', '7866', '37317', '28530', '89081', '55434', '77022', '68924', '99835', '5827', '99739', '88121', '94142', '86343', '40012', '7438', '37691', '27810', '99611', '5009', '99589', '68627', '87516', '82201', '99695', '99643', '5050', '69123', '94160', '86044', '40109', '7802', '37682', '99647', '83127', '99677', '99573', '5354', '69022', '94188', '85548', '40243', '7866', '37353', '99750', '84628', '82410', '76871', '57646', '99759',
			'99644', '5353', '69043', '94145', '85547', '40205', '7837', '37416', '99530', '84316',
			'82711', '79541', '57399', '99640', '99738', '5042', '68628', '94161', '85349', '40036', '7875', '37422', '99567',
			'83001', '57337', '52574', '99516', '99791', '5857', '68923', '94159', '86046', '40241', '7822', '37384', '99605', '82640', '57534', '99682', '99635', '5304', '69353', '94125', '85939', '40257', '7015', '37680', '99587', '5903', '99660', '5030', '68863', '99508', '83124', '57454', '99504', '5701', '69103', '99675', '82063', '57070', '15721', '46759', '61480', '26531', '99571', '5040', '69165', '99511', '82335', '57255', '16368', '99637', '5009',
			'69346', '99578', '82901', '57651', '56363', '99588', '5604', '69144', '99679', '82440', '57543', '58571', '93512', '99322', '5853', '69355', '99576', '82310', '57029', '58281', '68803', '99671', '82214', '57639', '58854', '68812', '99626', '83101', '57354', '58048', '5459', '56669', '67564', '51446', '54531', '69366', '99775', '82223', '57584', '58041', '67659', '18823', '5682', '67012', '51631', '68933', '69351', '99788', '82609', '57452', '58494',
			'66544', '68655', '99561', '5740', '66850', '51593', '68935', '68849', '99559', '68871', '99767', '68364', '69336', '99720', '60926', '68922', '99630', '49847', '49339', '68623', '99586', '14769', '68927', '99901', '49922', '95248', '69170', '99614', '68866', '99678', '37824', '69334',
			'99918', '69133', '99607', '79330', '68861', '99659', '68860', '99608', '52166', '69024', '99742', '68883', '99710', '52049',
			'88213', '69347', '99553', '69121', '99667', '82834', '68831', '99702', '69142', '99773', '76849', '53535', '38310', '68834', '99783', '68816', '99768',
			'79517', '53541', '38389', '69130', '99755', '52583', '65772', '68873', '99612', '53920', '38336', '69145', '99563', '68969', '99850', '47943', '99764', '16620', '46746', '68836', '99705', '26546', '99789', '69360', '99627', '69171', '99545', '78833', '13826', '99676', '68869', '99752', '56381', '58266',
			'99824', '68810', '99774', '48469', '99632', '69154', '99623', '59351', '89420', '99703', '68882', '99820', '5039', '89315', '66754', '12787', '99786', '68855', '99501', '5068', '96063', '69335', '67505', '99572', '69352', '99564', '68825', '99519', '17343', '75801', '66861', '99741', '61336', '49855',
			'68665', '99765', '68848', '99733', '17003', '75641', '99704', '49863', '69367', '99802', '99503', '17236', '99781', '99524', '68949', '99652', '49918', '23943', '99833', '99557', '69041', '99777', '99731', '99579', '99568', '69129', '99664', '69168', '99743', '54976', '79024', '69149', '99707', '69155',
			'99666', '47988', '99615', '69354', '99801', '99658', '69160', '99581', '99661', '68977', '99661', '72182', '82941', '68960', '99547', '82635', '68858', '99921', '82516', '38008', '57652', '18443', '41397', '68821', '99657', '18417', '41729', '48475', '64763', '78960', '37927', '69161', '99639', '72851',
			'99523', '65690', '99840', '4348', '99575', '99556', '99502', '99725', '99757', '48426', '89833', '99674', '56270', '89430', '5843', '56593', '43619', '66779', '51001', '54493', '99693', '67654', '10912', '43605', '67838', '51062', '54002', '67468', '12416', '45807', '67140', '51503', '54157', '43540',
			'67047', '51550', '54177', '67559', '12495', '43435', '66711', '51560', '54465', '12465', '99590', '99744', '50274', '12742', '68781', '99510', '99688', '68440', '64622', '69135', '99629', '99513', '99812', '68463', '69001', '99513', '81653', '79832', '49827', '99760', '99583', '68790', '69029', '99762',
			'80824', '14781', '62568', '49872', '50649', '99509', '99927', '68636', '69038', '99685', '49819', '50608', '97877', '99687', '99763', '68815', '99604', '49874', '52637', '79095', '48887', '95454', '99606', '99950', '68856', '99714', '49885', '95426', '99690', '99747', '69042', '99574', '49955', '95493',
			'45618', '88118', '99540', '99540', '68940', '99670', '49886', '82929', '69218', '49817', '82219', '24333', '65529', '84644', '53924', '38387', '69339', '63901', '84728', '53941', '38376', '65001', '48650', '53817', '38223', '63666', '15549', '4865', '4986', '47556', '63199', '56271', '11437', '58271',
			'4943', '3751', '63122', '56260', '58495', '81123', '4962', '3756', '46912', '59528', '63127', '46531', '74850', '5768', '63111', '62979', '66761', '16914', '46504', '74883', '5043', '96006', '68332', '65327', '69046', '63006', '67332', '5041', '68934', '63538', '69361', '75802', '49902', '68964', '64438',
			'68663', '49879', '14720', '61941', '68701', '69025', '62373', '37753', '68978', '68637', '90134', '42061', '79344', '37755', '69125', '42741', '79234', '69020', '79236', '82433', '24604', '65586', '76832', '53959', '57046', '63638', '76854', '54638', '52581', '57544', '48445', '65682', '54621', '50611',
			'48656', '46374', '48820', '37224', '83343', '53968', '4736', '48830', '47948', '48910', '37090', '4979', '49234', '37071', '4024', '58325', '49220', '37057', '99126', '3887', '81101', '35986', '48855', '37133', '59273', '56120', '59313', '5867', '67036', '51441', '67623', '12448', '17778', '96029', '68640',
			'66857', '68947', '67753', '67574', '68835', '17223', '49802', '67342', '69363', '49840', '24539', '68850', '49858', '79091', '69341', '49822', '69030', '68845', '49892', '95542', '68840', '49901', '95567', '52140', '37313', '69356', '49815', '69156', '68823', '69333', '41097', '45241', '91225', '48168',
			'60018', '8029', '77419', '72686', '7661', '70896', '49506', '52750', '88538', '68958', '40372', '45102', '91204', '48304', '60056', '8034', '77974', '72347', '7656', '70734', '82426', '85043', '77539', '69348', '49530', '52321', '40517', '45062', '30410', '32430', '28688', '78539', '24381', '79908', '91482',
			'48341', '60043', '8061', '78418', '72640', '7666', '70783', '82520', '85075', '77212', '69151', '48815', '52175', '41005', '45214', '31315', '32531', '28217', '78548', '24637', '63450', '79968', '69127', '48846', '52216', '40361', '45030', '91496', '48309', '60095', '8311', '78104', '72672', '7417', '70760',
			'82240', '85051', '77245', '30446', '32408', '28006', '78067', '24657', '79976', '68926', '49501', '33446', '98139', '16142', '38767', '15088', '88321', '2895', '38449', '52076', '40353', '45223', '91210', '48361', '60041', '8327', '78413', '72450', '7650', '70815', '79948', '69147', '49351', '82001', '85071',
			'77001', '31307', '32352', '52046', '40410', '45269', '91404', '48347', '60177', '8316', '78407', '28075', '78558', '24220', '88572', '69169', '49502', '71961', '7646', '52054', '41040', '45258', '79932', '68826', '49301', '91222', '52169', '41015', '45237', '79851', '69040', '49302', '91470', '52774', '40581',
			'45012', '88542', '68948', '49523', '90039', '52748', '40591', '79998', '68838', '49528', '45157', '99510', '99510', '99688', '99688', '99688', '68847', '50655', '69134', '72683', '82430', '68802', '72074', '83115', '24273', '84639', '69162', '69028', '69044', '20078', '49015', '33634', '61377', '85298', '79731',
			'49748', '35073', '49653', '49119', '68875', '57239', '11570', '49930', '49628', '69132', '57648', '11756', '18415', '49808', '49320', '77070', '49419', '60423', '69163', '57744', '11771', '33758', '85269', '80478', '49864', '10018', '77611', '80129', '49626', '33681', '68939', '57737', '11568', '33708', '39573',
			'41366', '50841', '10029', '77642', '80274', '74354', '94604', '49821', '35062', '84059', '49667', '68820', '57219', '10035', '77659', '80299', '41348', '74441', '94708', '69101', '57053', '10286', '77704', '80214', '41843', '74833', '94622', '49129', '68864', '57217', '10157', '77612', '69146', '57644', '30357',
			'5490', '36460', '54856', '82443', '57271', '5155', '5143', '69143', '58622', '67861', '82428', '57263', '5738', '66757', '83552', '46787', '82945', '57314', '67511', '82510', '57571', '46542', '67869', '87357', '89415', '82801', '5083', '56540', '66717', '67150', '54422', '96128', '5648', '69350', '97902', '58277', '54748', '69032', '97491', '66770', '62567', '67550', '97846', '58377', '58262', '97904', '58035', '67102', '58433', '58225', '67865', '86030', '67831', '12463', '36475', '31304', '24243', '12812', '79527', '53962', '23302', '57562', '53504', '57232', '53948', '57420', '54632', '57537', '57332', '57258', '54982', '54982', '47966', '20643', '71486', '66720', '05141', '73556', '49913', '66549', '72540', '12453', '79201', '65286', '79519', '65777', '58269', '40803', '50239', '82732', '82227', '40815', '82336', '95511', '03575', '95457', '03774', '82831', '80834', '66870', '04772', '51006', '49347', '67871', '04414', '49413', '82301', '88038', '56218', '34619', '34619', '34619', '05875', '23399', '73658', '67054', '01229', '67745', '33452', '33452', '38731', '67860', '38371', '38110', '43348', '14056', '67124', '38359', '45307', '67952', '64623', '24293', '65259', '67138', '64636', '11227', '72833', '57058', '92714', '64498', '57758', '63559', '93515', '57469', '64473', '57367', '57384', '98336', '48410', '47995', '48661', '83830', '14803', '56386', '58737', '92366', '98610', '3752', '81073', '58330', '47950', '59532', '99359', '30664', '46580', '5862', '56548', '67879', '67520', '18457', '5673', '67491', '5649', '67345', '49931', '14101', '29364', '54816', '88040', '82922', '24269', '82212', '38475', '23420', '82514', '48413', '64830', '1470', '82083', '65557', '46702', '64867', '98844', '3262', '66056', '21528', '46968', '30559', '56442', '67576', '67556', '56531', '66852', '67482', '49450', '67851', '62354', '13639', '67880', '67863', '47958', '93928', '87753', '72538', '88427', '83112', '72470', '84024', '38333', '38329', '47922', '87830', '83002', '57075', '82637', '57657', '83118', '57767', '57260', '57564', '79831', '72651', '12490', "'72577", "'12564", "'75223", "'87502", "'60131", "'55592", "'77218", "'78254", "'26404", "'43157", '72634', "'94304", "'44865", "'94025", "'45899", "'37852", '59746', "'89834", "'42032", "'49718", "'47366", "'42719", "'01263", "'82058", "'84522", "'82432", "'84312", "'83119", "'84539", '98527', '53937', '85337', '57325', '57585', '90704', '57374', '57344', '47366', '64426', '57622', '93665', '68846', '73938', '82073', '23408', '82932', '82646', '62027', '82601', '67525', '67123', '18842', '59463', '59025', '05872', '46945', '53061', '50839', '87520', '59461', '76848', '57373', '57782', '76465', '95532', '57375', '94923', '57538', '57362', '22482', '64437', '57339', '64446', '72654', '57724', '72669', '68755', '14766', '47455', '23417', '23358', '03284', '62640', '67547', '66853', '78885', '84715', '67417', '67672', '67627', '67744', '96093', '66407', '56553', '36457', '54890', '22578', '49870', '50530', '65466', '79842', '49812', '68350', '49865', '68717', '82642', '23405', '82934', '82938', '80860', '62888', '67127', '67104', '18453', '67850', '67554', '05866', '04607', '51240', '67841', '04949', '84511', '58270', '48454', '96112', '50165', '59469', '65484', '24487', '24412', '56659', '56651', '54926', '64668', '01471', '54626', '95305', '69220', '68354', '24282', '82423', '82723', '61955', '62053', '66748', '67567', '14823', '67351', '67022', '86031', '85926', '05860', '58538', '58251', '58353', '96103', '96101', '55366', '97738', '60919', '61258', '18405', '45863', '57569', '70585', '57656', '36872', '18622', '95934', '82222', '49675', '56584', '56710', '53801', '03592', '03771', '99147', '03585', '93529', '43041', '04933', '04223', '63874', '04912', '63440', '49805', '04847', '04965', '40855', '04956', '66869', '02575', '66426', '73438', '67739', '68303', '98281', '13465', '13346', '38374', '95486', '49761', '64653', '72413', '72140', '53506', '14777', '91987', '89003', '04612', '04441', '59918', '55968', '46381', '27915', '62639', '15362', '62367', '68747', '62238', '62977', '51636', '51009', '67701', '53102', '67640', '18611', '95423', '18851', '82931', '53556', '16245', '16876', '32329', '73033', '89418', '57638', '89001', '57559', '28631', '04629', '89404', '57449', '04286', '89043', '04420', '65582', '49905', '27920', '89047', '93504', '66036', '41723', '41149', '67954', '67867', '67868', '48766', '73461', '73749', '74572', '62934', '73741', '48419', '74549', '73487', '51639', '67751', '67756', '66945', '38311', '52030', '66080', '82902', '82936', '82835', '72072', '82842', '36444', '98849', '16230', '82512', '73043', '57237', '72842', '04655', '49801', '83638', '37111', '38450', '41745', '40874', '67444', '79239', '12036', '68452', '68431', '63350', '58241', '58381', '12958', '67732', '58542', '12073', '66449', '18340', '58643', '95419', '82837', '56763', '71652', '56147', '53805', '72587', '53553', '92275', '57251', '59454', '40823', '15359', '79085', '01084', '68359', '81155', '46977', '54543', '66532', '74561', '74560', '52056', '94972', '74340', '99123', '15822', '50590', '57246', '28623', '57363', '72958', '04679', '57634', '49963', '38425', '87538', '62681', '41477', '41385', '61321', '53102', '54855', '05153', '28905', '36038', '72928', '46990', '72752', '61439', '72828', '98925', '59467', '72776', '59252', '22473', '5470', '56589', '66843', '54552', '66451', '64482', '72841', '59085', '66439', '97819', '66522', '63530', '60932', '13630', '95389', '37770', '62092', '95558', '36481', '95462', '56450', '56384', '56285', '63633', '82524', '42631', '95983', '67663', '25862', '59452', '26521', '98561', '36741', '36539', '43555', '53533', '47381', '57424', '56145', '57027', '57472', '31041', '18469', '57645', '86032', '57366', '13838', '03812', '38077', '38390', '68950', '68370', '56756', '68941', '63737', '93628', '82839', '82412', '59441', '72944', '73661', '47951', '54941', '57043', '57257', '67857', '66839', '62822', '66866', '67155', '58776', '08329', '49635', '67070', '80734', '38328', '38044', '13368', '52060', '13134', '65638', '65779', '04920', '64658', '61471', '65766', '04238', '64453', '65717', '04653', '64458', '65666', '04631', '63539', '65660', '63447', '38558', '74529', '37175', '37025', '54634', '57269', '66075', '74553', '66767', '48658', '66951', '74636', '16943', '36473', '16750', '47457', '56221', '16255', '47577', '54457', '56236', '57501', '16645', '59211', '57467', '56210', '88580', '32050', '73122', '8757', '5059', '57787', '59231', '66710', '54554', '12748', '49831', '56342', '5758', '58049', '08072', '59217', '54423', '08039', '80726', '5829', '97834', '08313', '59317', '54847', '08318', '79093', '5746', '62444', '08341', '27024', '52323', '59479', '5069', '48865', '65658', '04646', '64424', '61422', '35979', '87743', '68774', '74939', '68376', '82324', '68375', '27972', '23418', '57772', '76842', '98547', '66017', '92315', '44607', '66970', '74530', '49255', '73744', '78024', '45848', '12167', '53554', '58249', '54545', '54566', '67835', '66833', '86029', '58642', '08020', '58601', '08051', '58327', '79326', '72419', '04263', '04677', '04285', '82501', '82229', '79852', '54928', '56156', '66860', '54839', '58310', '58366', '79347', '04962', '69211', '68719', '68786', '63462', '68727', '63435', '67734', '67474', '51578', '67565', '73432', '66406', '56241', '48467', '53943', '48756', '48624', '57012', '57658', '66716', '57567', '67061', '36742', '31065', '67137', '86535', '80723', '72619', '14478', '72389', '99167', '04538', '04693', '56742', '63439', '56740', '74534', '73642', '97464', '29933', '52312', '29923', '67842', '86043', '49725', '58770', '49891', '08352', '62618', '03894', '52101', '04617', '63531', '04862', '74350', '56660', '73734', '93605', '85321', '14173', '59482', '59054', '46379', '54962', '78561', '57762', '62879', '14857', '08061', '08343', '47541', '08074', '38334', '84656', '16656', '16666', '63537', '74538', '89437', '5739', '56446', '62836', '67510', '54406', '79223', '49718', '59474', '57473', '80758', '57532', '87461', '76836', '63458', '64001', '76460', '64679', '24245', '78883', '26298', '66871', '72721', '26205', '52549', '62866', '31081', '62992', '59501', '62481', '59078', '58461', '64480', '81243', '57227', '80745', '65025', '74734', '68362', '46916', '66736', '67334', '23416', '38324', '54664', '27978', '61540', '93541', '64433', '64645', '98840', '72629', '72153', '79259', '16244', '85608', '72717', '16910', '21522', '95984', '96011', '49056', '86503', '79046', '74724', '68316', '68658', '68062', '49838', '55783', '56741', '54814', '04009', '95646', '49622', '82844', '82942', '97135', '82832', '82933', '85601', '72853', '50427', '87064', '62818', '59521', '61285', '58338', '05058', '13814', '51540', '83545', '57766', '57262', '85934', '44809', '36470', '63941', '37822', '13468', '54859', '46935', '54418', '23421', '23404', '67021', '67073', '67573', '50552', '67059', '67836', '04785', '92329', '65623', '84776', '66548', '66930', '66097', '50238', '67740', '60959', '58231', '57047', '68745', '68647', '89067', '49945', '69201', '49881', '68624', '03846', '68424', '87328', '04694', '82329', '82944', '82401', '93542', '78021', '82051', '56371', '82643', '82718', '82442', '67481', '66404', '67665', '59453', '58795', '58372', '50579', '58565', '96108', '58852', '47019', '58062', '58030', '79033', '80818', '58645', '79077', '58032', '79383', '79013', '54977', '46960', '49950', '95552', '67870', '67854', '67103', '04992', '64471', '82327', '42060', '55965', '59301', '67023', '65755', '67337', '65566', '63820', '13697', '73946', '74632', '24351', '49042', '37756', '82322', '54643', '54120', '74523', '88410', '81047', '88123', '5448', '63446', '52731', '62070', '62431', '59740', '57660', '59544', '57270', '93517', '67545', '26293', '57241', '57385', '50451', '57071', '57235', '57756', '57057', '57221', '57331', '57633', '57236', '57002', '57520', '57268', '57007', '57422', '57625', '57523', '57794', '57247', '57476', '36753', '57361', '58531', '92386', '46996', '79783', '82420', '03779', '80861', '49841', '49921', '83549', '14893', '72566', '72687', '87562', '68734', '16871', '57468', '15712', '57370', '78873', '58219', '67420', '65676', '03579', '49833', '49896', '54532', '54517', '87820', '18815', '17966', '68429', '87455', '87326', '14130', '15801', '57031', '57566', '59354', '57356', '58625', '57623', '48761', '67757', '67752', '65589', '65741', '49917', '84512', '96114', '56164', '96129', '67864', '67837', '72383', '56240', '63563', '68341', '68629', '68718', '31327', '57529', '57054', '08324', '58363', '66510', '50255', '66436', '82935', '27936', '65607', '65715', '94576', '13464', '14837', '99117', '14874', '68841', '53540', '31556', '57059', '59724', '58324', '52160', '95318', '63934', '95345', '93514', '03238', '03749', '95560', '38242', '03279', '95422', '95497', '95531', '73455', '23440', '99160', '67950', '67523', '72584', '72424', '40061', '05863', '65325', '53510', '86540', '57522', '57249', '08345', '52151', '66550', '67467', '50854', '67673', '52590', '67635', '50847', '67674', '93596', '64738', '24272', '49820', '73939', '73443', '96094', '29741', '18619', '56211', '16948', '58260', '58620', '82649', '82332', '82721', '65624', '82323', '95437', '82727', '83119', '49420', '82638', '82720', '73729', '98554', '93204', '55922', '67045', '88336', '76869', '91948', '53526', '12418', '85902', '15054', '08250', '57547', '58385', '49115', '67513', '56535', '56591', '51447', '54152', '65661', '03740', '80862', '81077', '56970', '57799', '57716', '56971', '75023', '35752', '35963', '35765', '21824', '21628', '21667', '21620', '21678', '21690', '54646', '54666', '53825', '54652', '54619', '54652', '54616', '54641', '53958', '54639', '53936', '53543', '53569', '54662', '54602', '53587', '54619', '58479', '13343', '54637', '53569', '53518', '53516', '58775', '21545', '53816', '53969', '14747', '58239', '14730', '14733', '14750', '14757', '58853', '79786', '14785', '58343', '14701', '15621', '15677', '16054', '16210', '15316', '16113', '16250', '21866', '21661', '21670', '55981', '55131', '11215', '11243', '11005', '11222', '11360', '66872', '67466', '66961', '67738', '67651', '66955', '66428', '67485', '65446', '66955', '66901', '66411', '67447', '66543', '67626', '66408', '67449', '67441', '66427', '16151', '30449', '72945', '66838', '67418', '67730', '66859', '67653', '67490', '67639', '29041', '29046', '86514', '55615', '81148', '88414', '85320', '54846', '81071', '88025', '73011', '5455', '63472', '88034', '62947', '79247', '59073', '57067', '43151', '4360', '67519', '62621', '4738', '46511', '62624', '46524', '42649', '68309', '58374', '68337', '3750', '58069', '78871', '68460', '93522', '79843', '37378', '72860', '85923', '72936', '36458', '49336', '95325', '54909', '15710', '56014', '5656', '16821', '52309', '62817', '72042', '49252', '57717', '74442', '71972', '57466', '57540', '57649', '57436', '59212', '57553', '11433', '93923', '95010', '95005', '93922', '95039', '95017', '93962', '93924', '10464', '11357', '11428', '11232', '11423', '10312', '95001', '95004', '55070', '68342', '51463', '15522', '15865', '86545', '59241', '54659', '66942', '65588', '13690', '14819', '14820', '70747', '56728', '61435', '56662', '67439', '56588', '58313', '67736', '56672', '24474', '67761', '47957', '80815', '81092', '22529', '98583', '62996', '23426', '82071', '65655', '96132', '89023', '82717', '5156', '82515', '38388', '5653', '81236', '4055', '68318', '14029', '3581', '80802', '5077', '68403', '5665', '54513', '5359', '54555', '5036', '54442', '56687', '5070', '54424', '37333', '56585', '1263', '5442', '54480', '48720', '63862', '63744', '84530', '66963', '98590', '53910', '66521', '98644', '62965', '54624', '49322', '67515', '62045', '36435', '49639', '62825', '36763', '49616', '04942', '68730', '51467', '85929', '89013', '46031', '59341', '47836', '49903', '57626', '46971', '57004', '45720', '8886', '46017', '93621', '35013', '18833', '73724', '01472', '82422', '82712', '79845', '74555', '80804', '47942', '38332', '49454', '49449', '68769', '68780', '54458', '54450', '62557', '37887', '58357', '16933', '58361', '98350', '79770', '67853', '73646', '66777', '66734', '67876', '78879', '95987', '38221', '96028', '38318', '53950', '53803', '54655', '04943', '68752', '68042', '03814', '15741', '68972', '03582', '78588', '68714', '27943', '85911', '68944', '59427', '68735', '54529', '66960', '71937', '57335', '49125', '46047', '61087', '61775', '93440', '67834', '74525', '67360', '74646', '82833', '62046', '50110', '03751', '85925', '85927', '57311', '62311', '57755', '56711', '50426', '48763', '58448', '18413', '18832', '73561', '73436', '65784', '62432', '93546', '64861', '62030', '49627', '23410', '23301', '89317', '68348', '68313', '77970', '57319', '57432', '45674', '12956', '58581', '58752', '79261', '58639', '50521', '87734', '36722', '56151', '95637', '65444', '96057', '96056', '72943', '89045', '04481', '68445', '89319', '04850', '03262', '04732', '64455', '04262', '12507', '04984', '59332', '04276', '54135', '66962', '65440', '66501', '57448', '62098', '57641', '61876', '57640', '12405', '38781', '17735', '97848', '67353', '05762', '52161', '82421', '82441', '38379', '04929', '68420', '04781', '62436', '67905', '66712', '80721', '79351', '79245', '05840', '23955', '51025', '82432', '73520', '28901', '28902', '60933', '56641', '68702', '68756', '61334', '85922', '93513', '57051', '57572', '57776', '57036', '95488', '95430', '82055', '37732', '82943', '63881', '93222', '66422', '67638', '59003', '74440', '73759', '95701', '12503', '12936', '83227', '57442', '57014', '36446', '80823', '64483', '17729', '56232', '67065', '13845', '49719', '55760', '68759', '56542', '47925', '56623', '57342', '84636', '79323', '87747', '88113', '80749', '95424', '95435', '04068', '63774', '55931', '65730', '72377', '72659', '78536', '66540', '89422', '89883', '13832', '62356', '62044', '54485', '48466', '85924', '80733', '95480', '04940', '04660', '13312', '70580', '81019', '66417', '59243', '92333', '72760', '96010', '93664', '62078', '62033', '68058', '59540', '58452', '59201', '58276', '43144', '59248', '24283', '36425', '82321', '83526', '67584', '66959', '79539', '79529', '57574', '80735', '65048', '13646', '49894', '79252', '63445', '47975', '23308', '79220', '64833', '51341', '51603', '56734', '66851', '73624', '73041', '63629', '74761', '05764', '54519', '84633', '95568', '72052', '81126', '12442', '12469', '88039', '45783', '87730', '87518', '97473', '82715', '83635', '95587', '56140', '56714', '56732', '56644', '04667', '04923', '67344', '04683', '68325', '63878', '68457', '63621', '05763', '54156', '68441', '63636', '13645', '29476', '49807', '62852', '79530', '72037', '89042', '48765', '67658', '51573', '51601', '56654', '54967', '53947', '91931', '04564', '67801', '04637', '04492', '81332', '63622', '04029', '93464', '54821', '13362', '42063', '17840', '96013', '49845', '49837', '12424', '64466', '47987', '58576', '78952', '16855', '82710', '82636', '64686', '65246', '79505', '04239', '42079', '60952', '96119', '50152', '50544', '87749', '23398', '97837', '23395', '36439', '79342', '66425', '67650', '61346', '60924', '60956', '56634', '54965', '04353', '67346', '73848', '05746', '54469', '54950', '49455', '62422', '96067', '71448', '83110', '65647', '86034', '98398', '67105', '27824', '48427', '61455', '62899', '62862', '84749', '99136', '16859', '83802', '79324', '64756', '65732', '65555', '5143', '3590', '5481', '12727', '38236', '59934', '5481', '95571', '5828', '5255', '21539', '65061', '52257', '49782', '66058', '67473', '12858', '57636', '57579', '82224', '71969', '56136', '56725', '55954', '55725', '56447', '58431', '49311', '62517', '50652', '76824', '04221', '51656', '04037', '83462', '61328', '68725', '54548', '56568', '48627', '58386', '72180', '93526', '96133', '71218', '63451', '66010', '67552', '57371', '66862', '57312', '67581', '04664', '04657', '85341', '96123', '87729', '54560', '78591', '64427', '36782', '14745', '58644', '42070', '23407', '23483', '13847', '13450', '13813', '50067', '49873', '81030', '67671', '66937', '18357', '67446', '57576', '67450', '57577', '57252', '04642', '51454', '82716', '54893', '54756', '36031', '62413', '74752', '72951', '64646', '65449', '65067', '59419', '50147', '66416', '36429', '85912', '85935', '74652', '50586', '71662', '50556', '65564', '68319', '54769', '54430', '54845', '58630', '73668', '43523', '73651', '13840', '93642', '79848', '15533', '63544', '64441', '63863', '30464', '12409', '67629', '80801', '66742', '04751', '04676', '04015', '41764', '61440', '54945', '73835', '64667', '13154', '84637', '66935', '76352', '66863', '57010', '66775', '57076', '57541', '57548', '99116', '82450', '83116', '56757', '50074', '50104', '48749', '95526', '95545', '67560', '66412', '68651', '68057', '05757', '57439', '68751', '57632', '68664', '57218', '68961', '72650', '79830', '83531', '74966', '81151', '59531', '95467', '13633', '47574', '27885', '66087', '67648', '67579', '05748', '57037', '23488', '57334', '23389', '57321', '51579', '57779', '51645', '57764', '82411', '56755', '63549', '79064', '49775', '79370', '66967', '92226', '93530', '67143', '51543', '72026', '72537', '74949', '12412', '80805', '16839', '86544', '80751', '86502', '93954', '04746', '04478', '62065', '03583', '73853', '67530', '68660', '68764', '54562', '82838', '56631', '58250', '58562', '37096', '38550', '38365', '13404', '93549', '66773', '68736', '68732', '05740', '05772', '57328', '05730', '57383', '57376', '89311', '82620', '61375', '36764', '37137', '93643', '26571', '16650', '96117', '17249', '48635', '04757', '79053', '49852', '14060', '67855', '68938', '68323', '57073', '57349', '24221', '57062', '54524', '40964', '61424', '65438', '61488', '54481', '5677', '56264', '5822', '56123', '98819', '55712', '80807', '5821', '5839', '81143', '76804', '97865', '64496', '22517', '14787', '5154', '81044', '64651', '5907', '81154', '5736', '5459', '5769', '5848', '54644', '4935', '2185', '68416', '54213', '49887', '46737', '12747', '65322', '26884', '54540', '58227', '67661', '54403', '12965', '67656', '95412', '84756', '84542', '66749', '57272', '84638', '57061', '73435', '46513', '85605', '34957', '57750', '74562', '84007', '24246', '53952', '4675', '15646', '57340', '74722', '84539', '14877', '57220', '73039', '84053', '57720', '31006', '54211', '47369', '71439', '57214', '46946', '57256', '57471', '65258', '63556', '31037', '64723', '64724', '64660', '64661', '57355', '57364', '54240', '60973', '80720', '57078', '23942', '57470', '81241', '13740', '56629', '66738', '13681', '82334', '02557', '73716', '59338', '64420', '72083', '83253', '05832', '85633', '71942', '98827', '56541', '82840', '96068', '62875', '62829', '84649', '71841', '72064', '04936', '53580', '53810', '66782', '37773', '82648', '53522', '92266', '96034', '97710', '66938', '65752', '86039', '78001', '99138', '98833', '56761', '66751', '61417', '74535', '73901', '73756', '58651', '74943', '63457', '57348', '05823', '57040', '05845', '56729', '56668', '56032', '80826', '58063', '77961', '59451', '84731', '74956', '67762', '67749', '66508', '66419', '85930', '23482', '23345', '05904', '05732', '04640', '04623', '76481', '04055', '56287', '74866', '29826', '59465', '59471', '67735', '67631', '67423', '64641', '57533', '68014', '57437', '68643', '41713', '61358', '98829', '36033', '36032', '96097', '16651', '74567', '73950', '89314', '54234', '58602', '74829', '63620', '86504', '38320', '50465', '50137', '68036', '68377', '99151', '67953', '82602', '80744', '56376', '16847', '61539', '74472', '48620', '86510', '52568', '54491', '56726', '67585', '82604', '72835', '58844', '26527', '64659', '65543', '65468', '13755', '65689', '68415', '68039', '68442', '68414', '53599', '82845', '83111', '47551', '16873', '16941', '61731', '36436', '63551', '48748', '63534', '57330', '68942', '04926', '04227', '54773', '54538', '72733', '95930', '62916', '59333', '84531', '62923', '59214', '67733', '67622', '48737', '50543', '03216', '03598', '72066', '51061', '4851', '54127', '4614', '1966', '54412', '3750', '54948', '54452', '54449', '63468', '68038', '47017', '12498', '96031', '13655', '56684', '66732', '56653', '67840', '67844', '66772', '66858', '67846', '83466', '21524', '76856', '62928', '56758', '87315', '23347', '30562', '36432', '38238', '51363', '56557', '74072', '74545', '68652', '40849', '66944', '96137', '05837', '74856', '50840', '62292', '62973', '62088', '62083', '57317', '57346', '49701', '67109', '96016', '61322', '05079', '05846', '12767', '05902', '05737', '04570', '80420', '38315', '38370', '95606', '87027', '96109', '56220', '68740', '68380', '68760', '89832', '89017', '82615', '31812', '36427', '86520', '59930', '38363', '72085', '56685', '74947', '57359', '58458', '65733', '31319', '29659', '84773', '13457', '61541', '93669', '79508', '16917', '04945', '54561', '04040', '64432', '81149', '75849', '79032', '56759', '68642', '12939', '82434', '18459', '73844', '73726', '74750', '65261', '72747', '74536', '68738', '57358', '67572', '81248', '58046', '23423', '67437', '67660', '67301', '88126', '61340', '59489', '59257', '98614', '68753', '23427', '65608', '95429', '67122', '48764', '13780', '13848', '05773', '05765', '73945', '73663', '85940', '73838', '81087', '81124', '30448', '56081', '55606', '15539', '45855', '66946', '58265', '67632', '58416', '66953', '58655', '67646', '58323', '66538', '58528', '67575', '72641', '46943', '16371', '57421', '36736', '61001', '5848', '57747', '5009', '57326', '5871', '57225', '5731', '57026', '5492', '82930', '84046', '53961', '54649', '53595', '57315', '16725', '57563', '15761', '57722', '57380', '80822', '57368', '64402', '65230', '62274', '64674', '79070', '18460', '66933', '56183', '28909', '12973', '68648', '47034', '63363', '65036', '04668', '04762', '93645', '24215', '57386', '96085', '67427', '49871', '52048', '04085', '54970', '54174', '79846', '84536', '38368', '81125', '65548', '64847', '49416', '62374', '61335', '77629', '73949', '73030', '12468', '31083', '52653', '05758', '59036', '05824', '72517', '54628', '16634', '62077', '56737', '66941', '51461', '83543', '78598', '68954', '68601', '68367', '68980', '49674', '63839', '87735', '04741', '03752', '62961', '58540', '62871', '58220', '62884', '38258', '64744', '64783', '50481', '70772', '68634', '89310', '80740', '62319', '61949', '67524', '61364', '13454', '86507', '49736', '97635', '97620', '05830', '56219', '05850', '05050', '05085', '74935', '83120', '62976', '58721', '58351', '57072', '96014', '67951', '66846', '49063', '73944', '76852', '76870', '69216', '68758', '68467', '87011', '04681', '04852', '63567', '54943', '58656', '38342', '67553', '66855', '50048', '42638', '68973', '63370', '59261', '63950', '04860', '63738', '65035', '37078', '74942', '62833', '58229', '62926', '62835', '60942', '62282', '67645', '67478', '45389', '12874', '76845', '40801', '75882', '40854', '52069', '55735', '04614', '03875', '03887', '66943', '50231', '72829', '81136', '12932', '48471', '1244', '16238', '64849', '86025', '71474', '4357', '67512', '5738', '56248', '23306', '52701', '49707', '66714', '5056', '55370', '57021', '38237', '80732', '5680', '56252', '57212', '54421', '5156', '57259', '23976', '5034', '57714', '57233', '95944', '46582', '62363', '73551', '59466', '56720', '57790', '96127', '62089', '57213', '96054', '12407', '62876', '95676', '95988', '1966', '16667', '88254', '63782', '88418', '57382', '84752', '54542', '72444', '88323', '54427', '86020', '12758', '54411', '54463', '58640', '54476', '58455', '49747', '54865', '58426', '51020', '54209', '54151', '54969', '67340', '54771', '54435', '56276', '82242', '54414', '62860', '3860', '36456', '62556', '41777', '62379', '60931', '5037', '56661', '87712', '21530', '55613', '56458', '4613', '54447', '23950', '4261', '54446', '23924', '67634', '4662', '54765', '58259', '66552', '58843', '48613', '93554', '58344', '48434', '51566', '68773', '58040', '48762', '67035', '58772', '74836', '67364', '58443', '73746', '79251', '82215', '58017', '73641', '13754', '98602', '14479', '16633', '73763', '63763', '95910', '14895', '73858', '36921', '46502', '18853', '64681', '36401', '57568', '81043', '4861', '54721', '27981', '12765', '54563', '12480', '66956', '54871', '54471', '54114', '24962', '56284', '79854', '42753', '13635', '63746', '37326', '46778', '36445', '14126', '56676', '65692', '66527', '63433', '72626', '71922', '57350', '49309', '65068', '5351', '5738', '67529', '5826', '38254', '3750', '92278', '5459', '59315', '3813', '76880', '59324', '53530', '71339', '74558', '53944', '58401', '14752', '46501', '58061', '61410', '47997', '58421', '60948', '49880', '58335', '46555', '93460', '98587', '63851', '65570', '40830', '24558', '24256', '24230', '72950', '60920', '16352', '15851', '67443', '75838', '38579', '88055', '46731', '37829', '46779', '59425', '57278', '58838', '93218', '99371', '53940', '85606', '57245', '89421', '65729', '68045', '13839', '13815', '79785', '76873', '50025', '57069', '46380', '12441', '68644', '98641', '57521', '47351', '13788', '53581', '58009', '23422', '89022', '12454', '56724', '99150', '96121', '13654', '04547', '84626', '58428', '83547', '98812', '68351', '05151', '57752', '46917', '58311', '67877', '99154', '13305', '57601', '56356', '04742', '61468', '52254', '73564', '66415', '51632', '56673', '96015', '62478', '85502', '81227', '80432', '68979', '98640', '57261', '59322', '58568', '76885', '72462', '56581', '85928', '68326', '13614', '04848', '04443', '59053', '54558', '73747', '66780', '67004', '95443', '62859', '68955', '68843', '05051', '56224', '61233', '59226', '38256', '52071', '49927', '51650', '50864', '51455', '51527', '72611', '96040', '84723', '72827', '68726', '53802', '18848', '23341', '82730', '57661', '57430', '64682', '73932', '66094', '51528', '72353', '41778', '59314', '58236', '48621', '58382', '73622', '58254', '79537', '66958', '66432', '67669', '51532', '67352', '26534', '16638', '56647', '56436', '56679', '50843', '82630', '58072', '21560', '5056', '58524', '5656', '54654', '5086', '5053', '96116', '96084', '53928', '54896', '54425', '52648', '99166', '54479', '49818', '54556', '81132', '95538', '95347', '87515', '1966', '87012', '24219', '55335', '67460', '25984', '23336', '56551', '48028', '14751', '52074', '04572', '68338', '68943', '13664', '89021', '31058', '95555', '72585', '27960', '62856', '57783', '45739', '82725', '05056', '04849', '04658', '49876', '76452', '57531', '67647', '61074', '89825', '83121', '58224', '16858', '72638', '71677', '86515', '04756', '04672', '12855', '49834', '54210', '72749', '57560', '82729', '96048', '96130', '85932', '12131', '96130', '23307', '59736', '13675', '89037', '67657', '56748', '54103', '96025', '48758', '86547', '04224', '04644', '80821', '59343', '56309', '95460', '83533', '12941', '12076', '59012', '12450', '66932', '62075', '67018', '67159', '04267', '04863', '04231', '22579', '72134', '72038', '92323', '76459', '21501', '83548', '62445', '35958', '53942', '86402', '65542', '95970', '49829', '65051', '98559', '98595', '23413', '59262', '28672', '29939', '41719', '52631', '50657', '04352', '61562', '12456', '50264', '65443', '49258', '64874', '65761', '99157', '03561', '47531', '80759', '81242', '85639', '38375', '68746', '38347', '68343', '29913', '62891', '55413', '2873', '90508', '90012', '60440', '91208', '85305', '88301', '66207', '67464', '28478', '93703', '24265', '79927', '14855', '90707', '92170', '48161', '72112', '95106', '85280', '77282', '55467', '2826', '90250', '90007', '60197', '91042', '85312', '88049', '66053', '66024', '28362', '93641', '24609', '79922', '13808', '90846', '91943', '48152', '72447', '95157', '85211', '77240', '55415', '2832', '90505', '90095', '60184', '91407', '85372', '87723', '66217', '67487', '28466', '49893', '65335', '70118', '31642', '88262', '62442', '45827', '5679', '16679', '62969', '5841', '83520', '94562', '64779', '46938', '64853', '94573', '65688', '58424', '68722', '58008', '68716', '36028', '53026', '58282', '36121', '67621', '3809', '58060', '93505', '58255', '74837', '48412', '58484', '14732', '58329', '5343', '26560', '58301', '5262', '56470', '5736', '5674', '56715', '47537', '4738', '5827', '71449', '47032', '4462', '63942', '5352', '54613', '66725', '57773', '95458', '58560', '79566', '36727', '54215', '34948', '54246', '50241', '18426', '89834', '78119', '78604', '89409', '78624', '78117', '78884', '78006', '78011', '78009', '78130', '78052', '78115', '78606', '78004', '78063', '78636', '78840', '78880', '78016', '78114', '78058', '33055', '78614', '33040', '78154', '33137', '78074', '33111', '78633', '33138', '78843', '33010', '78801', '33116', '78861', '33016', '78070', '33185', '78654', '78027', '78132', '78122', '78124', '78113', '33119', '78050', '78147', '78056', '78832', '78144', '78671', '78151', '33045', '78155', '78065', '77994', '78830', '78872', '78836', '78028', '78618', '78802', '33158', '50591', '38058', '70184', '11946', '16201', '77964', '18202', '15204', '45658', '97124', '48433', '94129', '10170', '33466', '31098', '70363', '20685', '60176', '19710', '78658', '78953', '33178', '50380', '38150', '70148', '11950', '15052', '78350', '18711', '15262', '45780', '97071', '48457', '94979', '10174', '33432', '31013', '70442', '20711', '60124', '19905', '78159', '78066', '33013', '50430', '38193', '70058', '11746', '15686', '77976', '17938', '15244', '45768', '97317', '48116', '94940', '33445', '31061', '70435', '21092', '60142', '19952', '85285', '78059', '78632', '33042', '50322', '38127', '70131', '11795', '15459', '77960', '17701', '15258', '43332', '97027', '48049', '94965', '33465', '31296', '70466', '21612', '60091', '19718', '85257', '78121', '78156', '33193', '50582', '38120', '70186', '11939', '15057', '78383', '17856', '15289', '43716', '97036', '48059', '94942', '33416', '30477', '70357', '21270', '60060', '19950', '85213', '78013', '78635', '33001', '50314', '38152', '70056', '11764', '16161', '78387', '17929', '15045', '43988', '97049', '48422', '94127', '33460', '31062', '70090', '21132', '60110', '19891', '85205', '78135', '78123', '33283', '50569', '38115', '70115', '11948', '15376', '78405', '17836', '15223', '43149', '97045', '48139', '94901', '33413', '31031', '70380', '21133', '60123', '19736', '85119', '78026', '33199', '50316', '38187', '70119', '11796', '15312', '78333', '18626', '15202', '89508', '43787', '97211', '48555', '94941', '33459', '31046', '70463', '21856', '60122', '19930', '85120', '78839', '33161', '50394', '38054', '70156', '11725', '15731', '78351', '18419', '15277', '89406', '43734', '97007', '48022', '94974', '33401', '31032', '70455', '21287', '60008', '19726', '85224', '78012', '33162', '50516', '38049', '70067', '11775', '15486', '78060', '17968', '15211', '89436', '43302', '97129', '48114', '94132', '33486', '31050', '70446', '21652', '60083', '19808', '85216', '78623', '33139', '50334', '38028', '70053', '11955', '15696', '77903', '16926', '15120', '89595', '43048', '97055', '48532', '94957', '33410', '31011', '70429', '21162', '60055', '19850', '85209', '78860', '33131', '50169', '38019', '70116', '11779', '15461', '78373', '17868', '15239', '89440', '43768', '97015', '48437', '94117', '33406', '31096', '70454', '21609', '78075', '33296', '78140', '33149', '78838', '33168', '78116', '33163', '78143', '33186', '97110', '48439', '84716', '14767', '97239', '56667', '54922', '7458', '73089', '7604', '73650', '7652', '73521', '39079', '28701', '38726', '28805', '5159', '26680', '4077', '85937', '4016', '4662', '46953', '63936', '46567', '65788', '81144', '79331', '81029', '3588', '13692', '72865', '98548', '62538', '58654', '56335', '37394', '41347', '41081', '58033', '50064', '96032', '95548', '29564', '58222', '58652', '83866', '37845', '89301', '62314', '66968', '63443', '93237', '62930', '68401', '62970', '49964', '68770', '4843', '6079', '96009', '4971', '50664', '47848', '4669', '68742', '43510', '4854', '24277', '16321', '53565', '5744', '78360', '18349', '68465', '76692', '59526', '68763', '40807', '67548', '66541', '53088', '5903', '35978', '5154', '67347', '82058', '56431', '3597', '50002', '54759', '53152', '95514', '5731', '15341', '68327', '57775', '68832', '57620', '93930', '55342', '16937', '53584', '16836', '86556', '67839', '18813', '73032', '69217', '57630', '59547', '58056', '7608', '65479', '22530', '79229', '38071', '48110', '45876', '58345', '56186', '95553', '65055', '16022', '95456', '72658', '4738', '56751', '84640', '54495', '54862', '54746', '51565', '5302', '5868', '72573', '66845', '81232', '72938', '48740', '4290', '4088', '68761', '57455', '59223', '68466', '57313', '59247', '20142', '68633', '73001', '64624', '42740', '50833', '5152', '51018', '51630', '62464', '49106', '88422', '49915', '4464', '57440', '84533', '52555', '15861', '68902', '68787', '68620', '69212', '50625', '68352', '68784', '69221', '89025', '95420', '74577', '50243', '74546', '24218', '51637', '65436', '61415', '5042', '61475', '62848', '82731', '71823', '23409', '4652', '58626', '3817', '58413', '52170', '63466', '38380', '62326', '5851', '97736', '5045', '85920', '71831', '20134', '58647', '84736', '67667', '66516', '29921', '5030', '5839', '12993', '85532', '5819', '4606', '79056', '5745', '5083', '85501', '95947', '83554', '4971', '96122', '76686', '59330', '59484', '54763', '89136', '89085', '91310', '89112', '91380', '82072', '72565', '82059', '89135', '93590', '49942', '93241', '49935', '83123', '49854', '82939', '93501', '49752', '82060', '91382', '49806', '82010', '93535', '82003', '93306', '82644', '93586', '49895', '82639', '93268', '49958', '82005', '93311', '49911', '82002', '93524', '49959', '82435', '93301', '49785', '83114', '93314', '49784', '82053', '93254', '49971', '94712', '3215', '98530', '82925', '93225', '49925', '91302', '73075', '70426', '82213', '93313', '49848', '67901', '20588', '14241', '67568', '14265', '60608', '14710', '60605', '49835', '14753', '60686', '14140', '60699', '14303', '60677', '14109', '60070', '14129', '60678', '14231', '60607', '56456', '43202', '55732', '43082', '56326', '43116', '55719', '43201', '56678', '23354', '41727', '29844', '89820', '67003', '16671', '16694', '65013', '58763', '65702', '54610', '51431', '5045', '28423', '71075', '18351', '82331', '4854', '68381', '59545', '84762', '81239', '80747', '53588', '51451', '49436', '17731', '89049', '73573', '51460', '97722', '97636', '19732', '19894', '50065', '49910', '68854', '50262', '98195', '72824', '56214', '63452', '63965', '56280', '63166', '51646', '98170', '63454', '98158', '63787', '62565', '56033', '07544', '12919', '63387', '51572', '85190', '50108', '50475', '63178', '85631', '62352', '62689', '50144', '56125', '63180', '62571', '12883', '12998', '56255', '63665', '51430', '51432', '63625', '85724', '51444', '63448', '50860', '85744', '65546', '50068', '50848', '30439', '12439', '96742', '51529', '56149', '98181', '53062', '65565', '62667', '50433', '63167', '62510', '62764', '30451', '73153', '52569', '50076', '50862', '79051', '93545', '31524', '93592', '10120', '62843', '62964', '54806', '54766', '87583', '54875', '48703', '48551', '52149', '56051', '52037', '62378', '56144', '94143', '88353', '04024', '36766', '50605', '04654', '51442', '04088', '18352', '24250', '27351', '50133', '18814', '04544', '85135', '28663', '36454', '04563', '70612', '16936', '51638', '48175', '04779', '04775', '48554', '04611', '04738', '18350', '36471', '04549', '37234', '17867', '01115', '92628', '04268', '06390', '92674', '04686', '27359', '33586', '51562', '37232', '04479', '44080', '98174', '04786', '04491', '50103', '85723', '48552', '37243', '37242', '70651', '01012', '68925', '59353', '59004', '59464', '68361', '68001', '92182', '93410', '39566', '74438', '40018', '68783', '59219', '60196', '59274', '59710', '68952', '11451', '68771', '73444', '59623', '74533', '68669', '73544', '68464', '59087', '69210', '92132', '32919', '74576', '73772', '68928', '59336', '68335', '68340', '02862', '73947', '73758', '74563', '74521', '48636', '59542', '74557', '73771', '68975', '68439', '93408', '68785', '74937', '68981', '74951', '68662', '48818', '68818', '48730', '73555', '68040', '68433', '79918', '68666', '73660', '48852', '48739', '79942', '59462', '93447', '70540', '70629', '68711', '73942', '79837', '74528', '44901', '49866', '05035', '71952', '13333', '97010', '56515', '49877', '05750', '79094', '33630', '55146', '65724', '65483', '55145', '22576', '4041', '59250', '58054', '58027', '60082', '84126', '54149', '64688', '38226', '50835', '93634', '49026', '49816', '93024', '72041', '95428', '94508', '31054', '84740', '67737', '54428', '16720', '61473', '96113', '68765', '38381', '77224', '92384', '82605', '4010', '56545', '58370', '67675', '2539', '70524', '2553', '54150', '64664', '56902', '97130', '20159', '49920', '81140', '42024', '57279', '59222', '57642', '59062', '3838', '59530', '67758', '67642', '66523', '64637', '67649', '76841', '97147', '12928', '76644', '76667', '29812', '29038', '29802', '29828', '29842', '29203', '29115', '29055', '29054', '29020', '72370', '29042', '71822', '29150', '72376', '29207', '72170', '29059', '72440', '29853', '71740', '29734', '71725', '29851', '82714', '65283', '56688', '56731', '56566', '60945', '73667', '66778', '99118', '56166', '15553', '46288', '66952', '81633', '64858', '51048', '93603', '62892', '42153', '74359', '61324', '58833', '75064', '08606', '04843', '65778', '63956', '71079', '76820', '4972', '4743', '4628', '49826', '41702', '40816', '48654', '82923', '3251', '95714', '72544', '12872', '84055', '13473', '5702', '4643', '66743', '96104', '66534', '59316', '68901', '12434', '12516', '67024', '76676', '54515', '83122', '54530', '56576', '58439', '58441', '74027', '58243', '35746', '60921', '67361', '93644', '93661', '95387', '58272', '68055', '58317', '80728', '63632', '4743', '62468', '53809', '85938', '62649', '2539', '59225', '58230', '59525', '68047', '59529', '68437', '5901', '54160', '64781', '56212', '92280', '71678', '61724', '72623', '61469', '83465', '82937', '67009', '93309', '93384', '93584', '52657', '37134', '49825', '68748', '54113', '4010', '61091', '4859', '4485', '57066', '57329', '34101', '87715', '68180', '68175', '15532', '3780', '15537', '3584', '62806', '16675', '59538', '55377', '92309', '87029', '63751', '4675', '57659', '62085', '38225', '40316', '36745', '50983', '58045', '68723', '97732', '88056', '66771', '54933', '57457', '33464', '83128', '13623', '56293', '76825', '68667', '72310', '68365', '72583', '68715', '80736', '72459', '72635', '32961', '72528', '68602', '34985', '98381', '72449', '32969', '68659', '78044', '33661', '64065', '68791', '7724808088', '9565943799', '8163285864', '47917', '3134479418', '8456895220', '9376708719', '58212', '28367', '23703', '38344', '15949', '15078', '57063', '04745', '04098', '20213', '53804', '92368', '04022', '54119', '42021', '03584', '23464', '90079', '48667', '12050', '86312', '68957', '47162', '65614', '65635', '61470', '77661', '50863', '38130', '92521', '04057', '38485', '07509', '62837', '54844', '72003', '60946', '34106', '12427', '39460', '99185', '59260', '65069', '63057', '90510', '37684', '95417', '05759', '04010', '04645', '56142', '55488', '04539', '50052', '80722', '59055', '52168', '32520', '02535', '54512', '24205', '24244', '48288', '73460', '13482', '96037', '67053', '62756', '41367', '37185', '74402', '62288', '62071', '98075', '11743', '33543', '48657', '78008', '97017', '24413', '84775', '98058', '11733', '40376', '28690', '55042', '75163', '35806', '98015', '11763', '41164', '28698', '55024', '75417', '36267', '68789', '98043', '11980', '40856', '28761', '55025', '75433', '36273', '98008', '11703', '42564', '28715', '55165', '75831', '35612', '5901', '98020', '11780', '40995', '28755', '55122', '15353', '76491', '98204', '11752', '41859', '28732', '55109', '92328', '98207', '28815', '55116', '98077', '28603', '55047', '66438', '98009', '28791', '55119', '66088', '62963', '38341', '41421', '69214', '72414', '62362', '56230', '45861', '63434', '59318', '74147', '65770', '27268', '23829', '94596', '52760', '48220', '73832', '58348', '58348', '54568', '39356', '67454', '36748', '79912', '58244', '51531', '3233', '95421', '68450', '38241', '72820', '5753', '66936', '41413', '27982', '51060', '95445', '53821', '18430', '57621', '52771', '50031', '76468', '5839', '74760', '73722', '58784', '96721', '96838', '96825', '96730', '96839', '96773', '29574', '84726', '72111', '32328', '57528', '65747', '49849', '50040', '73859', '99141', '68330', '5742', '78007', '52593', '12915', '57631', '61930', '80419', '80294', '80293', '80264', '87736', '23357', '4077', '74953', '61057', '79528', '73731', '3836', '71447', '35744', '73062', '3817', '5833', '72354', '13332', '95589', '57763', '28533', '51439', '56681', '63071', '63780', '65072', '35740', '65402', '5767', '72639', '65032', '13618', '62357', '63764', '63935', '64776', '65202', '58362', '63461', '65280', '12234', '17861', '05745', '73548', '74754', '74569', '73433', '92310', '92242', '37141', '92395', '37215', '59411', '92011', '37179', '99140', '59047', '68315', '92222', '37152', '98628', '45636', '59620', '68654', '92236', '37089', '99353', '45653', '23185', '59007', '68410', '37713', '63532', '92036', '33865', '59258', '99213', '37840', '65278', '92008', '33896', '59254', '98834', '37379', '64475', '92274', '33884', '59105', '98802', '37744', '64423', '93238', '33840', '59841', '99149', '37363', '65354', '92363', '33859', '59011', '37757', '65275', '92068', '33811', '37743', '65350', '92079', '37669', '96710', '37726', '37380', '37409', '37644', '37724', '37601', '37811', '37415', '37730', '59745', '53939', '92389', '28039', '04964', '12422', '55485', '20472', '20270', '58803', '55479', '52544', '20388', '87327', '20551', '12470', '28244', '20504', '58580', '58121', '04662', '97312', '04271', '39522', '04555', '61460', '73764', '16672', '57551', '88051', '63345', '4226', '4737', '54819', '98249', '91735', '91199', '91021', '91732', '91188', '91008', '91715', '91030', '91102', '12771', '12594', '12429', '12719', '12779', '10537', '10998', '12443', '10981', '12745', '52077', '78547', '79034', '80825', '56680', '56256', '62883', '67644', '97840', '55612', '54867', '62237', '85554', '86047', '56296', '50631', '67521', '74747', '99146', '98340', '32509', '36754', '81228', '4952', '4430', '4455', '4558', '60974', '35809', '83530', '70044', '03756', '56237', '70391', '30429', '77720', '52064', '56087', '98862', '5149', '13410', '58773', '83464', '58533', '49116', '33455', '67843', '66948', '68777', '72478', '72460', '89010', '95465', '65774', '58346', '35270', '63663', '60030', '67882', '37140', '71959', '4938', '18346', '4853', '56637', '4971', '56517', '4675', '19109', '56257', '68632', '67430', '3231', '56153', '56139', '68749', '16646', '54635', '66518', '68974', '38951', '56965', '56627', '56627', '42047', '04624', '82061', '96059', '84747', '95956', '67563', '78226', '95554', '78216', '74369', '5030', '70167', '5769', '20131', '70123', '70062', '16692', '57748', '70172', '70114', '70146', '70064', '74556', '50853', '19903', '71029', '50136', '62617', '20529', '72475', '66949', '66035', '15562', '73664', '73654', '50319', '92096', '68355', '72149', '05083', '05774', '68768', '68067', '71021', '04961', '04853', '60688', '33662', '68724', '70302', '01653', '98236', '68436', '68661', '92697', '68453', '72139', '72826', '68956', '68310', '92619', '68865', '68072', '68198', '68406', '68456', '92616', '68357', '68322', '68720', '68970', '68458', '68050', '68729', '59275', '59448', '59259', '59523', '68305', '68071', '68321', '59230', '59215', '59626', '59221', '59527', '59749', '59326', '68653', '59255', '68371', '59319', '59276', '59725', '59339', '59434', '59435', '68583', '15282', '68778', '59084', '59344', '59039', '59418', '59270', '59337', '59537', '68757', '59812', '68779', '59253', '59077', '59747', '59032', '59535', '68016', '59218', '68930', '68739', '68444', '59755', '68447', '59256', '59076', '68728', '68641', '68713', '68766', '59111', '59263', '68792', '59311', '59457', '59486', '59735', '59443', '59424', '68710', '59436', '93450', '74766', '78590', '80755', '70139', '13842', '58380', '03785', '04669', '03593', '03574', '03285', '04925', '03813', '03860', '04543', '03282', '04626', '04013', '04464', '59349', '04333', '59524', '54125', '79233', '2568', '93226', '3817', '39630', '4359', '49781', '5861', '13406', '61447', '61413', '81129', '55766', '43550', '73571', '73626', '5848', '5778', '72028', '4691', '4573', '28617', '56274', '62861', '73770', '82523', '89831', '60962', '15315', '52619', '96858', '31039', '61369', '60037', '14012', '91409', '30332', '95468', '79248', '74425', '84086', '05861', '05033', '58627', '48550', '66629', '78549', '77843', '80705', '45893', '03590', '04739', '37315', '70576', '86033', '23337', '62954', '47011', '56671', '56760', '92871', '55787', '92822', '04226', '04360', '34260', '04041', '04851', '04558', '11256', '93707', '93718', '06075', '15638', '39436', '67355', '83542', '58636', '04864', '60685', '30916', '81233', '93244', '2552', '66940', '61942', '58365', '88424', '88415', '80812', '89426', '87119', '87119', '4275', '18344', '18344', '56727', '71966', '2552', '47596', '47596', '5034', '5034', '32335', '12921', '58632', '96027', '81225', '62047', '73021', '49239', '58623', '87746', '58482', '23396', '4743', '98357', '62480', '56998', '78961', '47611', '23303', '97721', '87320', '51520', '56134', '56134', '66856', '93623', '78847', '78847', '62844', '62334', '70081', '4421', '67445', '13460', '5903', '53573', '55949', '54625', '61049', '12457', '62985', '3855', '82836', '96038', '4938', '73851', '5821', '53934', '67349', '15746', '50272', '50272', '57341', '57341', '72529', '93604', '72529', '93604', '74349', '74349', '99333', '15344', '63473', '63473', '95981', '87364', '80746', '52070', '3845', '18820', '53813', '53813', '13693', '58569', '55772', '66434', '64842', '5751', '5751', '5701', '64457', '24130', '58065', '30450', '4970', '81201', '4773', '47986', '47986', '74578', '95556', '65680', '95717', '70541', '70541', '4973', '79314', '5766', '5074', '5731', '16432', '88125', '23350', '4237', '4237', '31810', '85357', '29633', '49102', '24073', '36612', '72175', '48211', '29651', '44864', '49013', '36671', '72320', '74430', '60633', '88265', '76196', '55425', '49051', '11050', '74159', '62962', '64857', '4217', '7890', '34249', '41775', '73855', '67452', '48870', '95644', '85931', '10959', '58573', '58216', '62842', '3849', '2713', '85618', '4743', '13812', '38377', '59213', '57735', '20160', '47832', '56738', '36045', '56658', '65603', '76427', '41751', '5833', '53826', '67438', '57324', '57555', '56754', '4275', '74728', '13804', '5839', '3570', '47021', '52646', '32013', '23412', '65464', '4228', '4568', '56060', '58415', '58575', '58570', '62459', '74755', '54754', '95559', '84631', '5069', '74368', '12920', '28903', '51448', '41451', '41332', '74547', '96074', '65785', '56283', '57536', '36461', '72059', '84718', '6245', '84535', '40843', '30568', '41310', '93452', '55796', '65456', '97751', '40844', '64431', '43719', '4666', '52351', '37058', '98638', '47921', '55605', '66403', '62344', '73437', '61758', '72685', '87733', '36783', '84534', '32143', '48743', '58490', '62839', '71329', '14840', '54645', '86052', '64631', '58339', '5152', '5081', '49791', '57570', '71944', '72648', '72351', '42087', '91608', '61251', '60499', '37389', '61474', '14441', '10118', '60701', '87722', '62458', '20229', '67643', '83844', '97870', '81231', '6041', '4650', '37892', '79031', '14887', '10060', '55460', '57216', '79997', '96850', '46401', '93262', '74026', '20420', '66630', '76628', '41760', '10172', '5738', '13353', '53290', '65762', '93251', '8888', '80027', '73951', '8556', '60697', '96107', '13455', '2051', '33835', '91906', '19176', '3849', '18628', '61940', '77261', '96135', '19884', '79353', '32099', '5853', '48750', '93208', '11815', '62867', '62809', '79711', '73403', '95343', '85117', '91526', '20599', '20375', '56973', '20204', '20204', '72513', '60669', '48265', '56592', '77491', '90506', '53586', '81131', '4219', '17957', '18810', '20431', '56904', '28760', '33101', '33101', '33101', '33101', '33101', '33101', '33101', '33101', '33101', '33101', '33101', '33101', '33101', '33101', '33101', '33101', '33101', '33101', '33101', '52158', '33101', '33101', '33101', '76508', '79430', '33101', '33101', '33101', '33101', '34988', '95373', '5907', '56180', '57552', '91980', '3817', '70310', '28765', '21235', '03777', '62051', '64856', '04773', '74192', '65735', '04630', '62323', '60675', '65711', '65636', '46366', '73768', '54138', '80523', '20146', '15773', '05819', '05155', '20771', '23465', '93599', '90633', '84753', '74182', '70163', '35971', '64121', '53783', '53777', '87185', '23269', '55487', '86042', '10311', '51651', '1263', '1805', '10271', '95585', '78711', '16947', '71272', '82084', '87420', '4341', '10098', '10098', '38559', '10098', '10098', '72679', '10098', '72515', '10098', '10098', '2553', '31599', '10098', '65618', '80836', '4495', '32067', '57475', '33101', '11371', '04574', '62819', '70402', '11424', '44660'];

	}



	/**
	 * Check if the Zip Codes are valid
	 * @param $zipCode
	 *
	 * @return bool
	 */
	private function isZipCodeValid($zipCode)
	{
		$inValidZipCodes = $this->inValidZipCodes();
		return !in_array($zipCode, $inValidZipCodes);
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
}