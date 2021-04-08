<?php

namespace App\Http\Controllers\Api\V1\Traits;

use App\Model\Sim;
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
use App\Http\Controllers\Api\V1\CardController;
use App\Http\Controllers\Api\V1\Invoice\InvoiceController;

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
	 * @param $orderItems
	 */
	private function totalPriceForPreview($orderItems)
	{
		$this->calDevicePricesForPreview($orderItems);
		$this->getPlanPricesForPreview($orderItems);
		$this->getSimPricesForPreview($orderItems);
		$this->calTaxesForPreview($orderItems);
		// $this->getShippingFeeForPreview($orderItems);
		$this->calRegulatoryForPreview($orderItems);
		$price[] = ($this->prices) ? array_sum($this->prices) : 0;
		$price[] = ($this->regulatory) ? array_sum($this->regulatory) : 0;
		$price[] = ($this->coupon());

		if ($this->tax_total === 0) {
			$price[] = ($this->taxes) ? number_format(array_sum($this->taxes), 2) : 0;
		} else {
			$price[] = number_format($this->tax_total, 2);
		}
		$price[] = ($this->activation) ? array_sum($this->activation) : 0;
		$price[] = ($this->shippingFee) ? array_sum($this->shippingFee) : 0;
		$totalPrice = array_sum($price);
		$this->total_price = $totalPrice;
		return $totalPrice;

	}

	/**
	 * @param $orderItems
	 */
	private function subTotalPriceForPreview($orderItems)
	{

	}

	/**
	 * @param $orderItems
	 */
	private function calMonthlyChargeForPreview($orderItems)
	{

	}

	/**
	 * @param $orderItems
	 */
	private function calTaxesForPreview($orderItems)
	{

	}

	/**
	 * @param $orderItems
	 *
	 * @return float|int
	 */
	private function calRegulatoryForPreview($orderItems)
	{
		$regulatoryFees = [];
		foreach ($orderItems as $orderItem) {
			if (isset($orderItem['plan_id'])) {
				$plan = Plan::find($orderItem['plan_id']);
				if ($plan->regulatory_fee_type == 1) {
					$regulatoryFees = $plan->regulatory_fee_amount;
				} elseif ($plan->regulatory_fee_type == 2) {
					$regulatoryFees = number_format($plan->regulatory_fee_amount * $plan->amount_recurring / 100, 2);
				}
			}
		}
		return $regulatoryFees ? array_sum($regulatoryFees) : 0;

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
		return $shippingFees ? array_sum($shippingFees) : 0;
	}

	/**
	 * @param $orderItems
	 *
	 * @return float|int
	 */
	protected function calDevicePricesForPreview($orderItems)
	{
		$prices = [];
		if ($orderItems) {
			foreach ($orderItems as $orderItem) {
				if (isset($orderItem['device_id'])) {
					$device = Device::find($orderItem['device_id']);
					if (isset($orderItem['plan_id'])) {
						$prices[] = $device->amount_w_plan;
					} else {
						$prices[] = $device->amount;
					}
				}
			}
		}
		return $prices ? array_sum($prices) : 0;
	}

	/**
	 * @param $orderItems
	 *
	 * @return float|int
	 */
	protected function getPlanPricesForPreview($orderItems)
	{
		$prices = [];
		foreach ($orderItems as $orderItem) {
			if (isset($orderItem['plan_id'])) {
				$plan = Plan::find($orderItem['plan_id']);
				if ( $plan->amount_onetime > 0 ) {
					$prices[] = $plan->amount_onetime;
				}
				$prices[] = $plan->amount_recurring;
			}
		}
		return $prices ? array_sum($prices) : 0;
	}

	/**
	 * @param $orderItems
	 *
	 * @return float|int
	 */
	protected function getSimPricesForPreview($orderItems)
	{
		$prices = [];
		foreach ($orderItems as $orderItem) {
			if(isset($orderItem['sim_id'])){
				$sim = Sim::find($orderItem['sim_id']);
				if (isset($orderItem['plan_id'])) {
					$prices[] = $sim->amount_w_plan;
				} else {
					$prices[] = $sim->amount_alone;
				}
			}
		}
		return $prices ? array_sum($prices) : 0;
	}

	/**
	 * @param Request $request
	 * @param         $order
	 * @param         $orderItems
	 * @param         $planActivation
	 */
	public function createInvoice(Request $request, $order, $orderItems, $planActivation)
	{
		$customer = Customer::find($request->get('customer_id'));
		$end_date = Carbon::parse($customer->billing_end)->addDays(1);
		$order = Order::whereHash($order->hash)->first();

		$invoice = Invoice::create([
			'customer_id'             => $customer->id,
			'type'                    => CardController::DEFAULT_VALUE,
			'status'                  => CardController::DEFAULT_VALUE,
			'end_date'                => $end_date,
			'start_date'              => $customer->billing_start,
			'due_date'                => $customer->billing_start,
			'subtotal'                => 0,
			'total_due'               => CardController::DEFAULT_DUE,
			'prev_balance'            => CardController::DEFAULT_DUE,
			'payment_method'          => 'Bulk Order',
			'notes'                   => 'Bulk Order | Without Payment',
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
			'invoice_id'    => $invoice->id,
			'status'        => '1',
			'order_num'     => $orderCount + 1,
		]);

		$this->subscriptionInvoiceItem($orderItems, $invoice, $planActivation);
		$this->ifTotalDue($order);
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
				'billing_end'             => $carbon->addMonth()->subDay()->toDateString()
			]);
		}
	}

	/**
	 * @param $orderItems
	 * @param $invoice
	 * @param $planActivation
	 */
	protected function subscriptionInvoiceItem($orderItems, $invoice, $planActivation)
	{
		$invoiceItemArray = [
			'invoice_id'  => $invoice->id,
			'type'        => InvoiceController::DEFAULT_INT,
			'start_date'  => $invoice->start_date,
			'description' => InvoiceController::DESCRIPTION,
			'taxable'     => InvoiceController::DEFAULT_INT,
		];
		$order = Order::where('invoice_id', $invoice->id)->first();
		$subscriptionIds = [];
		foreach($orderItems as $orderItem) {
			if(isset($orderItem['subscription_id'])){
				$subscriptionIds[] = $orderItem['subscription_id'];
			}
		}
		foreach ($subscriptionIds as $subscriptionId) {
			$subscription = Subscription::find($subscriptionId);
			$invoiceItemArray['subscription_id'] = $subscription->id;

			if ($subscription->device_id !== null) {
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
				$invoiceItemArray['amount'] = number_format($amount, 2);
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

			$order = Order::where('invoice_id', $invoice->id)->first();
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
					$invoiceItemArray['amount'] = number_format($addonAmount, 2);
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
}