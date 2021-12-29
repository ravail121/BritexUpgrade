<?php

namespace App\Http\Controllers\Api\V1\Traits;

use App\Helpers\Log;
use App\Model\Sim;
use App\Model\Tax;
use Carbon\Carbon;
use App\Model\Plan;
use App\Model\Order;
use App\Model\Addon;
use App\Model\Device;
use App\Model\Invoice;
use App\Model\Customer;
use App\Model\InvoiceItem;
use App\Model\Subscription;
use Illuminate\Http\Request;
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
	 *
	 * @return float|int
	 */
	protected function totalPriceForPreview(Request $request, $orderItems)
	{
		$price[] = $this->subTotalPriceForPreview($request, $orderItems);
 		$price[] = $this->calRegulatoryForPreview($request, $orderItems);
		$price[] = $this->getPlanActivationPricesForPreview($orderItems);
		return $this->convertToTwoDecimals(array_sum($price), 2);

	}

	/**
	 * @param Request $request
	 * @param         $orderItems
	 * @param bool    $applySurcharge
	 *
	 * @return string
	 */
	protected function subTotalPriceForPreview(Request $request, $orderItems, $applySurcharge=true)
	{
		$customer = Customer::find($request->get('customer_id'));
		$price[] = $this->calDevicePricesForPreview($request, $orderItems);
		$price[] = $this->getPlanPricesForPreview($request, $orderItems);
		$price[] = $this->getSimPricesForPreview($request, $orderItems);
		$price[] = $this->getAddonPricesForPreview($request, $orderItems);
		$subTotal = array_sum($price);
		if($applySurcharge && $customer->surcharge > 0) {
			$surcharge = ($subTotal * $customer->surcharge) / 100;
			$subTotal += $surcharge;
		}
		return $this->convertToTwoDecimals($subTotal, 2);
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
	 * @param Request  $request
	 * @param          $order
	 * @param          $orderItems
	 * @param          $planActivation
	 * @param          $hasSubscription
	 */
	public function createInvoice(Request $request, $order, $orderItems, $planActivation, $hasSubscription)
	{
		$customer = Customer::find($request->get('customer_id'));
		$order = Order::whereHash($order->hash)->first();
		if($hasSubscription) {
			$this->updateCustomerDates( $customer );
		}
		$invoiceStartDate = $this->getInvoiceDates($customer);
		$invoiceEndDate = $this->getInvoiceDates($customer, 'end_date');
		$invoiceDueDate = $this->getInvoiceDates($customer, 'due_date', true);

		$invoice = Invoice::create([
			'customer_id'             => $customer->id,
			'type'                    => CardController::DEFAULT_VALUE,
			'status'                  => CardController::DEFAULT_VALUE,
			'end_date'                => $invoiceEndDate,
			'start_date'              => $invoiceStartDate,
			'due_date'                => $invoiceDueDate,
			'subtotal'                => $this->subTotalPriceForPreview($request, $orderItems),
			'total_due'               => $this->totalPriceForPreview($request, $orderItems),
			'prev_balance'            => $this->getCustomerDue($customer->id),
			'payment_method'          => 'Bulk Order',
			'notes'                   => 'Bulk Order | Without Payment',
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
		]);

		$order->update([
			'invoice_id'    => $invoice->id,
			'status'        => '1'
		]);

		$this->invoiceItem($orderItems, $invoice, $planActivation);

		/**
		 * Insert record for surcharge amount
		 */
		if($customer->surcharge > 0) {
			$subTotalAmountWithoutSurcharge = $this->subTotalPriceForPreview($request, $orderItems, false);
			$surchargeAmount = ($customer->surcharge * $subTotalAmountWithoutSurcharge) / 100;
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
	 */
	protected function invoiceItem($orderItems, $invoice, $planActivation)
	{
		$subscriptionIds = [];
		$standAloneSims = [];
		$standAloneDevices = [];
		$order = Order::where('invoice_id', $invoice->id)->first();
		$customer = Customer::find($invoice->customer_id);
		foreach($orderItems as $orderItem) {
			if(isset($orderItem['subscription_id'])){
				$subscriptionIds[] = $orderItem['subscription_id'];
			} else {
				if(isset($orderItem['sim_id']) && !isset($orderItem['device_id']) && !isset($orderItem['plan_id'])){
					$standAloneSims[] = (object) [
						'id'        => $orderItem['sim_id'],
						'sim_num'   => $orderItem['sim_num']
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
			$this->standaloneSimInvoiceItem($standAloneSims, $invoice);
		}
		if(!empty($standAloneDevices) && !$planActivation){
			$this->standaloneDeviceInvoiceItem($standAloneDevices, $invoice);
		}
		if($customer->surcharge > 0){
			$this->surchargeInvoiceItem($customer, $invoice);
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
	 *  Creates invoice_item for customer_standalone_device
	 * @param $standAloneDevices
	 * @param $invoice
	 *
	 * @return null
	 */
	protected function standaloneDeviceInvoiceItem($standAloneDevices, $invoice)
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
				'status'        => CustomerStandaloneDevice::STATUS['complete'],
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
	 *
	 * @return null
	 */
	protected function standaloneSimInvoiceItem($standaloneSims, $invoice)
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
				'status'        => CustomerStandaloneSim::STATUS['complete'],
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
			'type'              => 10,
			'start_date'        => $invoice->start_date,
			'description'       => InvoiceController::SURCHARGE_DESCRIPTION,
			'taxable'           => InvoiceController::DEFAULT_INT,
		];
		InvoiceItem::create($invoiceItemArray);
	}
}