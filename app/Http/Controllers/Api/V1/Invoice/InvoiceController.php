<?php

namespace App\Http\Controllers\Api\V1\Invoice;

use Carbon\Carbon;
use App\Model\Sim;
use App\Model\Plan;
use App\Model\Order;
use App\Model\Addon;
use App\Model\Device;
use App\Model\Credit;
use App\Model\Invoice;
use App\Model\Customer;
use App\Model\OrderGroup;
use App\Model\InvoiceItem;
use App\Model\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Model\OrderGroupAddon;
use App\Model\SystemGlobalSetting;
use App\Model\CustomerStandaloneSim;
use App\Model\CustomerStandaloneDevice;
use App\Http\Controllers\BaseController;
use App\libs\Constants\ConstantInterface;
use App\Http\Controllers\Api\V1\Traits\InvoiceTrait;
use App\Http\Controllers\Api\V1\Traits\InvoiceCouponTrait;

class InvoiceController extends BaseController implements ConstantInterface
{
	use InvoiceTrait, InvoiceCouponTrait;

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
	/**
	 * Creates invoice_item and sends email with invoice.pdf attachment
	 *
	 * @param  Request    $request
	 * @return Response
	 */
	public function oneTimeInvoice(Request $request)
	{
		\Log::info($request->all());
		$msg = '';
		$order = Order::where('hash', $request->hash ?: $request->order_hash)->first();

		$path = SystemGlobalSetting::first()->upload_path;
		if ($request->data_to_invoice && !$request->status == 'Without Payment') {

			$invoice = $request->data_to_invoice;

			if ($request->coupon_data) {
				$this->storeCoupon($request->coupon_data, $order);
			}

			if (isset($invoice['subscription_id'])) {
				$subscription = Subscription::find($invoice['subscription_id'][0]);

				if($subscription->upgrade_downgrade_status){
					$arrayData = ['order_id' => $order->id, 'customer_id' => $request->customer_id];
					$updateCustomerDates = $this->updateCustomerDates((object) $arrayData);
					$invoiceItem  = $this->changeSubcriptionInvoiceItem($subscription, $request->auto_generated_order);
				}else{
					$updateCustomerDates = $this->updateCustomerDates($subscription);
					$invoiceItem  = $this->subscriptionInvoiceItem($invoice['subscription_id'], json_decode($request->coupon));
					if($request->auto_generated_order == '1'){
						$autoInvoiceItem  = $this->autoGeneratedOrder($invoice['subscription_id']);
					}
				}

				$msg = (!$invoiceItem) ? 'Subscription Invoice item was not generated' : 'Invoice item generated successfully.';

			}
			if(isset($invoice['same_subscription_id'])){
				$invoiceItem  = $this->samePlanUpgradeInvoiceItem($invoice['same_subscription_id'], $order->id, $request->auto_generated_order);
			}
			if (isset($invoice['customer_standalone_device_id'])) {

				$deviceId = is_array($invoice['customer_standalone_device_id']) ? $invoice['customer_standalone_device_id'][0] : $invoice['customer_standalone_device_id'];

				$standaloneDevice = CustomerStandaloneDevice::find($deviceId);

				$updateCustomerDates = $this->updateCustomerDates($standaloneDevice);

				$invoiceItem = $this->standaloneDeviceInvoiceItem($invoice['customer_standalone_device_id']);

				$taxes = $this->addTaxesToStandalone($order->id, self::TAX_FALSE, self::DEVICE_TYPE, $request->coupon);

				$msg = (!$invoiceItem) ? 'Standalone Device Invoice item was not generated' : 'Invoice item generated successfully.' ;
			}
			if (isset($invoice['customer_standalone_sim_id'])) {

				$standaloneSim = CustomerStandaloneSim::find($invoice['customer_standalone_sim_id'][0]);

				$updateCustomerDates = $this->updateCustomerDates($standaloneSim);

				$invoiceItem = $this->standaloneSimInvoiceItem($invoice['customer_standalone_sim_id']);

				$taxes = $this->addTaxesToStandalone($order->id, self::TAX_FALSE, self::SIM_TYPE, $request->coupon);

				$msg = (!$invoiceItem) ? 'Standalone Sim Invoice item was not generated' : 'Invoice item generated successfully.';

			}

			$this->addShippingCharges($order->id); // giving order directly excludes shipping fee in some cases.

			$this->updateCouponNumUses($order);

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

		} else if ($request->status == 'Without Payment') {

			$this->createInvoice($request);
			$this->generateInvoice($order, true, $request);
			return $this->respond($msg);
		}

		$this->generateInvoice($order, true, $request);
		return [
			'status' => $this->respond($msg),
			'invoice_items_total' => number_format($order->invoice->cal_total_charges, 2),
			'order_num' => $order->order_num
		];
	}

	/**
	 * Generates the Invoice template and downloads the invoice.pdf file
	 *
	 * @param  Request    $request
	 * @return Response
	 */
	public function get(Request $request)
	{
		if ($request->order_hash) {
			$order = Order::where('hash', $request->order_hash)->first();
			return $this->generateInvoice($order, false, $request);
		} elseif ($request->invoice_hash) {
			$decryptedId = pack("H*",$request->invoice_hash);
			$invoiceId   = substr($decryptedId, strpos($decryptedId, "=") + 1);
			$invoice = Invoice::find($invoiceId);
			$transactionNumber = $invoice->refundLog ? $invoice->refundLog->transaction_num : null;
			return $this->generateRefundInvoice($invoice, $transactionNumber, true);
		}
		return 'Sorry, invoice not found.';
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


	protected function autoGeneratedOrder($subscriptionIds)
	{

		$invoiceItem = null;
		$order = Order::where('invoice_id', $this->input['invoice_id'])->first();
		foreach ($subscriptionIds as $index => $subscriptionId) {
			$subscription = Subscription::find($subscriptionId);

			$subarray = [
				'subscription_id' => $subscription->id,
			];

			if ($subscription->plan_id != null) {
				$plan = Plan::find($subscription->plan_id);

				$array = [
					'product_type' => self::PLAN_TYPE,
					'product_id'   => $subscription->plan_id,
					'amount'       => number_format($plan->amount_recurring, 2),
					'taxable'      => $plan->taxable,
					'description'  => 'Auto generated as monthly is paid'
				];

				$array = array_merge($subarray, $array);

				$invoiceItem = InvoiceItem::create(array_merge($this->input, $array));

				//add REGULATORY FEE charges in invoice-item table
				$regulatoryFee = $this->addRegulatorFeesToSubscription(
					$subscription,
					$invoiceItem->invoice,
					self::TAX_FALSE,
					$order,
					1
				);
			}

			$order = Order::where('invoice_id', $this->input['invoice_id'])->first();
			$subscriptionAddons = $subscription->subscriptionAddon;


			if ($subscriptionAddons) {
				foreach ($subscriptionAddons as $subAddon) {

					$addon = Addon::find($subAddon->addon_id);
					$addonAmount = $addon->amount_recurring;


					$array = [
						'product_type' => self::ADDON_TYPE,
						'product_id'   => $addon->id,
						'type'         => 2,
						'amount'       => number_format($addonAmount, 2),
						'taxable'      => $addon->taxable,
						'description'  => 'Auto generated as monthly is paid'
					];

					$array = array_merge($subarray, $array);

					$invoiceItem = InvoiceItem::create(array_merge($this->input, $array));
				}
			}
			$order->invoice->invoiceItem()->where('description', "(Taxes)")->delete();

			$this->addTaxesToUpgrade($order->invoice, self::TAX_FALSE, $subscription->id);
		}

		return $invoiceItem;
	}

	protected function changeSubcriptionInvoiceItem($subscription, $paidInvoice)
	{
		$invoiceItem = null;
		if ($subscription->plan_id != null) {
			$plan = Plan::find($subscription->plan_id);
			$amount = $plan->amount_recurring - $subscription->oldPlan->amount_recurring;

			$array = [
				'subscription_id' => $subscription->id,
				'product_type' => self::PLAN_TYPE,
				'product_id'   => $subscription->plan_id,
				'amount'       => number_format($amount, 2),
				'taxable'      => $plan->taxable,
				'description'  => ''
			];
			if($subscription->upgrade_downgrade_status =='for-upgrade'){

				$array['description'] = 'Upgrade from plan '.$subscription->old_plan_id.' to plan '.$subscription->plan_id;
			}

			$invoiceItem = InvoiceItem::create(array_merge($this->input, $array));

			if($paidInvoice == 1){
				$invoiceItem = InvoiceItem::create(array_merge($this->input, $array));
			}
		}

		$order = Order::where('invoice_id', $this->input['invoice_id'])->first();
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
					'subscription_id' => $subscription->id,
					'product_type' => self::ADDON_TYPE,
					'product_id'   => $addon->id,
					'type'         => 2,
					'amount'       => number_format($addonAmount, 2),
					'taxable'      => $addon->taxable,
					'description'  => ''
				];

				$invoiceItem = InvoiceItem::create(array_merge($this->input, $array));
				if($paidInvoice == 1){
					$invoiceItem = InvoiceItem::create(array_merge($this->input, $array));
				}
			}
		}
		$this->addTaxesToUpgrade($order->invoice, self::TAX_FALSE, $subscription->id);
		return $invoiceItem;
	}

	/**
	 * Creates inovice_item for subscription
	 * @param      $subscriptionIds
	 * @param null $coupons
	 *
	 * @return null
	 */
	protected function subscriptionInvoiceItem($subscriptionIds, $coupons = null)
	{
		$paidInvoice = 0;
		$invoiceItem = null;
		$order = Order::where('invoice_id', $this->input['invoice_id'])->first();
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
						'type'           => 3,
						'amount'        => $device->amount_w_plan,
						'description'   => '',
						'taxable'       => $device->taxable
					]);

				}

				$array = array_merge($subarray, $array);
				$invoiceItem = InvoiceItem::create(array_merge($this->input, $array));

			}

			if ($subscription->plan_id != null) {
				$plan = Plan::find($subscription->plan_id);

				$proratedAmount = $order->planProRate($plan->id);
				$amount = $proratedAmount == null ? $plan->amount_recurring : $proratedAmount;

				$array = [
					'product_type' => self::PLAN_TYPE,
					'product_id'   => $subscription->plan_id,
					'amount'       => number_format($amount, 2),
					'taxable'      => $plan->taxable,
					'description'  => ''
				];

				$array = array_merge($subarray, $array);

				$invoiceItem = InvoiceItem::create(array_merge($this->input, $array));

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
					self::DESCRIPTION
				);
			}

			if ($subscription->sim_id != null ) {
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
			$subscriptionAddons = $subscription->subscriptionAddon;


			if ($subscriptionAddons) {
				foreach ($subscriptionAddons as $subAddon) {

					$addon = Addon::find($subAddon->addon_id);
					$isProrated = $order->orderGroup->where('plan_prorated_amt', '!=', null);

					if ($isProrated) {
						$proratedAmount = $order->calProRatedAmount($addon->amount_recurring);
					}
					$addonAmount    = $proratedAmount >= 0 ? $proratedAmount : $addon->amount_recurring;


					$array = [
						'product_type' => self::ADDON_TYPE,
						'product_id'   => $addon->id,
						'type'         => 2,
						'amount'       => number_format($addonAmount, 2),
						'taxable'      => $addon->taxable,
						'description'  => ''
					];

					$array = array_merge($subarray, $array);

					$invoiceItem = InvoiceItem::create(array_merge($this->input, $array));
				}
			}
			//add taxes to subscription items only
			$taxes = $this->addTaxesToSubscription(
				$subscription,
				$invoiceItem->invoice,
				self::TAX_FALSE,
				$coupons
			);
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
	protected function samePlanUpgradeInvoiceItem($subscriptionIds, $orderId, $paidInvoice)
	{
		$invoiceItem = null;
		$order = Order::find($orderId);

		foreach ($subscriptionIds as $index => $subscriptionId) {
			$subscription = Subscription::find($subscriptionId);

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
						'amount'          => number_format($addonAmount, 2),
						'taxable'         => $addon->taxable,
						'subscription_id' => $subscription->id,
						'invoice_id'      => $order->invoice_id,
						'start_date'      => $order->invoice->start_date,
						'description'     => self::ADDON_TYPE,
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

	protected function getCustomerDue($customer_id){
		$dues = 0;
		$invoices = Invoice::where('customer_id', $customer_id)->where('status', 1);
		foreach($invoices as $invoice){
			$dues += $invoice->total_due;
		}
		return $dues;
	}


	public function createInvoice(Request $request)
	{
		$data = $request->validate([
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
			'status'                  => 2,
			'subtotal'                => 0,
			'total_due'               => 0,
			'prev_balance'            => 0,
			'payment_method'          => 1,
			'notes'                   => 'No Payment',
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

		$this->createInvoiceItem($data['order_groups'], $invoice, $request->type);
	}

	private function createInvoiceItem($orderGroups, $invoice, $type)
	{
		foreach ($orderGroups as $orderGroup) {
			$subscription = Subscription::find($orderGroup['subscription']['id']);
			if(!($type == 'sameplan')){
				if($type == "for-upgrade"){
					$description = 'Upgrade from '.$subscription['old_plan_id'].' to '.$subscription['plan_id'];
				}else{
					$description = 'Downgrade from '.$subscription['plan_id'].' to '.$subscription['new_plan_id'];
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
			}

			if(isset($orderGroup['addons'])){
				$addonData = [
					'invoice_id'      => $invoice->id,
					'subscription_id' => $subscription['id'],
					'product_type'    => self::ADDON_TYPE,
					'amount'          => 0,
					'type'            => 2,
					'start_date'      => $invoice->start_date,
					'description'     => "removal-scheduled",
				];

				foreach ($orderGroup['addons'] as $addon) {
					$addonData['product_id'] = $addon['id'];
					$addonData['taxable'] = $addon['taxable'];

					InvoiceItem::create($addonData);
				}
			}
		}
	}

	public function checkMonthlyInvoice(Request $request)
	{
		$date = Carbon::today()->addDays(6)->endOfDay();
		$invoice = Invoice::where([
			['customer_id', $request->id],
			['status', Invoice::INVOICESTATUS['closed&paid'] ],
			['type', Invoice::TYPES['monthly']]
		])->whereBetween('start_date', [Carbon::today()->startOfDay(), $date])->where('start_date', '!=', Carbon::today())->first();

		return $invoice ? 1: 0;
	}

}
