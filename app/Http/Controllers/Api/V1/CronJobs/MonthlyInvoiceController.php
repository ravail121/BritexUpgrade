<?php

namespace App\Http\Controllers\Api\V1\CronJobs;


use App\Model\CronLog;
use Exception;
use Carbon\Carbon;
use App\Model\Plan;
use App\Model\Order;
use App\Model\Invoice;
use App\Model\Customer;
use App\Model\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\BaseController;
use App\libs\Constants\ConstantInterface;
use App\Http\Controllers\Api\V1\Traits\CronLogTrait;
use App\Http\Controllers\Api\V1\Traits\BulkOrderTrait;
use App\Http\Controllers\Api\V1\Traits\InvoiceCouponTrait;


/**
 * Class MonthlyInvoiceController
 *
 * @package App\Http\Controllers\Api\V1\CronJobs
 */
class MonthlyInvoiceController extends BaseController implements ConstantInterface
{
	use InvoiceCouponTrait, BulkOrderTrait, CronLogTrait;

	/**
	 * Responses from various sources
	 * @var $response
	 */
	public $response;

	/**
	 * @var
	 */
	public $flag;

    /**
     * Sets current date variable
     * 
     * @param Carbon $carbon
     */
    public function __construct()
    {
        $this->response = ['error' => 'Email was not sent'];
    }

    /**
     * Generates Monthly Invoice of all Customers by checking conditions
     * 
     * @return Response
     */
    public function generateMonthlyInvoice(Request $request)
    {
        $customers = Customer::shouldBeGeneratedNewInvoices();
        foreach ($customers as $customer) {
            try {
	            \Log::info('Generating invoice for ' . $customer->id);
	            $this->processMonthlyInvoice($customer, $request, true);
	            $logEntry = [
		            'name'      => 'Generate Monthly Invoice',
		            'status'    => 'success',
		            'payload'   => json_encode($customer),
		            'response'  => 'Generated Successfully for ' . $customer->id
	            ];

	            $this->logCronEntries($logEntry);
            } catch (Exception $e) {
	            $logEntry = [
		            'name'      => 'Generate Monthly Invoice',
		            'status'    => 'error',
		            'payload'   => json_encode($customer),
		            'response'  => $e->getMessage(). ' on line: '.$e->getLine(). ' inside monthly invoice controller for ' . $customer->id
	            ];

	            $this->logCronEntries($logEntry);
                \Log::info($e->getMessage(). ' on line: '.$e->getLine(). ' inside monthly invoice controller');
            }
        }

        return $this->respond($this->response);
    }

	/**
	 * @param      $customer
	 * @param      $request
	 * @param bool $mail
	 */
	public function processMonthlyInvoice($customer, $request, $mail = true)
	{
		if ($customer->billableSubscriptions->count()) {
			$invoice = Invoice::create($this->getInvoiceData($customer));

			$dueDate = Carbon::parse($invoice->start_date)->subDay();

			$dueDateChange = $invoice->update([
				'due_date' => $dueDate
			]);

			$billableSubscriptionInvoiceItems = $this->addBillableSubscriptions($customer->billableSubscriptions, $invoice);

			$billableSubscriptionAddons = $this->addSubscriptionAddons($billableSubscriptionInvoiceItems);

			$regulatoryFees = $this->regulatoryFees($billableSubscriptionInvoiceItems);

			$pendingCharges = $this->pendingCharges($invoice);

			$totalPendingCharges = $pendingCharges->sum('amount') ? $pendingCharges->sum('amount') : 0;

			// Add Coupons
			$couponAccount = $this->customerAccountCoupons($customer, $invoice);

			$couponSubscription = $this->customerSubscriptionCoupons($invoice, $customer->billableSubscriptions);

			if($customer->stateTax) {
				$taxes = $this->addTaxes( $customer, $invoice, $billableSubscriptionInvoiceItems );
				\Log::info("----State Tax not present for customer with id {$customer->id} and stateTax {$customer->stateTax}. Tax Calculation skipped");
			}

			$monthlyCharges = $invoice->cal_total_charges;

			/**
			 * Apply Surcharge
			 */
			if($customer->surcharge > 0) {
				$surcharge = ($monthlyCharges * $customer->surcharge) / 100;
				$this->surchargeInvoiceItem($invoice, $surcharge);
				$monthlyCharges += $surcharge;
			}

			//Plan charge + addon charge + pending charges + taxes - discount = monthly charges
			$subtotal = str_replace(',', '', number_format($monthlyCharges + $totalPendingCharges, 2));

			$invoiceUpdate = $invoice->update(compact('subtotal'));

			$totalDue = $this->applyCredits($customer, $invoice);

			$invoice->update(['total_due' => $totalDue]);

			if ($totalDue == 0) {
				$invoice->update(['status' => Invoice::INVOICESTATUS['closed']]);
			}

			$insertOrder = $this->insertOrder($invoice);

			$order       = Order::where('invoice_id', $invoice->id)->first();

			$request->headers->set('authorization', $order->company->api_key);

			$this->generateInvoice($order, $mail, $request);
		}
	}

	public function processMonthlyInvoice2($invoice,$customer, $request, $mail = true)
	{
		// dd(9);
		if ($customer->billableSubscriptions->count()) {
			// $invoice = Invoice::create($this->getInvoiceData($customer));

			$dueDate = Carbon::parse($invoice->start_date)->subDay();

			$dueDateChange = $invoice->update([
				'due_date' => $dueDate
			]);

			$billableSubscriptionInvoiceItems = $this->addBillableSubscriptions2($customer->billableSubscriptions, $invoice);

			$billableSubscriptionAddons = $this->addSubscriptionAddons($billableSubscriptionInvoiceItems);

			$regulatoryFees = $this->regulatoryFees($billableSubscriptionInvoiceItems);

			$pendingCharges = $this->pendingCharges($invoice);

			$totalPendingCharges = $pendingCharges->sum('amount') ? $pendingCharges->sum('amount') : 0;

			// Add Coupons
			$couponAccount = $this->customerAccountCoupons2($customer, $invoice);

			$couponSubscription = $this->customerSubscriptionCoupons2($invoice, $customer->billableSubscriptions);

			if($customer->stateTax) {
				$taxes = $this->addTaxes2( $customer, $invoice, $billableSubscriptionInvoiceItems );
				\Log::info("----State Tax not present for customer with id {$customer->id} and stateTax {$customer->stateTax}. Tax Calculation skipped");
			}

			$monthlyCharges = $invoice->cal_total_charges;
			/**
			 * Apply Surcharge
			 */
			if($customer->surcharge > 0) {
				$surcharge = ($monthlyCharges * $customer->surcharge) / 100;
				$this->surchargeInvoiceItem($invoice, $surcharge);
				$monthlyCharges += $surcharge;
			}

			//Plan charge + addon charge + pending charges + taxes - discount = monthly charges
			$subtotal = str_replace(',', '', number_format($monthlyCharges + $totalPendingCharges, 2));

			$invoiceUpdate = $invoice->update(compact('subtotal'));

			$totalDue = $this->applyCredits($customer, $invoice);

			$invoice->update(['total_due' => $totalDue]);

			if ($totalDue == 0) {
				$invoice->update(['status' => Invoice::INVOICESTATUS['closed']]);
			}

			// $insertOrder = $this->insertOrder($invoice);

			// $order       = Order::where('invoice_id', $invoice->id)->first();

			// $request->headers->set('authorization', $order->company->api_key);

			// $this->generateInvoice($order, $mail, $request);
		}
	}
	/**
	 * @param $customer
	 * @param $invoice
	 *
	 * @return mixed
	 */
	public function applyCredits($customer, $invoice)
	{
		$totalDue = $invoice->subtotal;
		if (isset($totalDue) && $totalDue) {
			foreach ($customer->creditsNotAppliedCompletely as $creditNotAppliedCompletely) {
				$usedCredit = $creditNotAppliedCompletely->usedOnInvoices->sum('amount');
				$pendingCredits = $creditNotAppliedCompletely->amount - $usedCredit;

				if($totalDue >= $pendingCredits){
					$creditNotAppliedCompletely->update(['applied_to_invoice' => 1]);

					$creditNotAppliedCompletely->usedOnInvoices()->create([
						'invoice_id'  => $invoice->id,
						'amount'      => $pendingCredits,
						'description' => "{$pendingCredits} applied on invoice id {$invoice->id}",
					]);

					$totalDue -= $pendingCredits;

					if($totalDue == $pendingCredits) break;
				}else {
					$creditNotAppliedCompletely->usedOnInvoices()->create([
						'invoice_id'  => $invoice->id,
						'amount'      => $totalDue,
						'description' => "{$totalDue} applied on invoice id {$invoice->id}",
					]);
					// Warning: Don't move it above as the value
					$totalDue -= $totalDue;
					break;
				}
			}

			return $totalDue;
		}
	}

	/**
	 * @param $customer
	 * @param $invoice
	 * @param $billableSubscriptionInvoiceItems
	 *
	 * @return \Illuminate\Support\Collection
	 */
	public function addTaxes($customer, $invoice, $billableSubscriptionInvoiceItems)
	{
		$taxes = collect();
		$taxPercentage = ($customer->stateTax->rate)/100;
		$amount = [0];
		$taxData = [];
		$taxableItems = $invoice->invoiceItem->where('taxable', true);
		
		
		$taxableSubscriptionIds = array_unique($taxableItems->pluck('subscription_id')->toArray());

		foreach ($taxableSubscriptionIds as $subId) {
			$itemAmount = $taxableItems->where('subscription_id', $subId)->sum('amount');
			if (count($this->taxDiscount)) {
				foreach ($this->taxDiscount as $id => $taxDiscount) {
					if ($id == $subId) {
						$itemAmount -= array_sum($taxDiscount);
					}
				}
			}
			$itemAmount = $itemAmount * $taxPercentage;
			$data = [
				'subscription_id' => $subId,
				'product_type'    => '',
				'product_id'      => null,
				'type'            => InvoiceItem::INVOICE_ITEM_TYPES['taxes'],
				'start_date'      => $invoice->start_date,
				'description'     => '(Taxes)',
				'amount'          => number_format($itemAmount, 2),
				'taxable'         => self::TAX_FALSE
			];

			$taxes->push(
				$invoice->invoiceItem()->create($data)
			);
		}
		return $taxes;
	}

	public function addTaxes2($customer, $invoice, $billableSubscriptionInvoiceItems)
	{
		$taxes = collect();
		$taxPercentage = ($customer->stateTax->rate)/100;
		$amount = [0];
		$taxData = [];
		$taxableItems = $invoice->invoiceItem->where('taxable', true);
		$today     = Carbon::today();
		$taxableItems = $taxableItems->filter(function($taxableItems, $i) use ($today){
			//dd(7);
			$billingEndParsed = Carbon::parse($taxableItems->created_at);
			// Is today between customer.billing_date and -5 days
			return
				$today <= $billingEndParsed;
		});

		// dd($taxableItems);
		$taxableSubscriptionIds = array_unique($taxableItems->pluck('subscription_id')->toArray());

		foreach ($taxableSubscriptionIds as $subId) {
			$itemAmount = $taxableItems->where('subscription_id', $subId)->sum('amount');
			if (count($this->taxDiscount)) {
				foreach ($this->taxDiscount as $id => $taxDiscount) {
					if ($id == $subId) {
						$itemAmount -= array_sum($taxDiscount);
					}
				}
			}
			$itemAmount = $itemAmount * $taxPercentage;
			$data = [
				'subscription_id' => $subId,
				'product_type'    => '',
				'product_id'      => null,
				'type'            => InvoiceItem::INVOICE_ITEM_TYPES['taxes'],
				'start_date'      => $invoice->start_date,
				'description'     => '(Taxes)',
				'amount'          => number_format($itemAmount, 2),
				'taxable'         => self::TAX_FALSE
			];

			$taxes->push(
				$invoice->invoiceItem()->create($data)
			);
		}
		return $taxes;
	}

	/**
	 * @param $invoice
	 */
	protected function insertOrder($invoice)
	{
		$hash = md5(time().rand());

		if ($invoice->type === Invoice::TYPES['monthly'] && isset($invoice->customer)) {
			Log::info('insertOrder');
			$company_id = $invoice->customer->company_id;
			if($company_id) {
				$count = Order::where( 'company_id', $company_id )->max( 'order_num' );
				Order::create( [
					'status'         => 1,
					'invoice_id'     => $invoice->id,
					'hash'           => $hash,
					'company_id'     => $company_id,
					'customer_id'    => $invoice->customer_id,
					'date_processed' => Carbon::today(),
				] );
			}
		}
	}

	/**
	 * Creates/Regenerates the Invoice
	 *
	 * @param  int       $customerId
	 * @return Invoice   $invoice
	 */
	protected function createInvoice($customerId)
	{
		$invoice = false;
		$invoicePending     = Invoice::monthlyInvoicePending()->first();
		$invoicePaid        = Invoice::monthlyInvoicePaid()->first();
		$regenratedInvoice  = $this->regenerateInvoice($customerId);

		if (!$invoicePaid && !$invoicePending) {

			$customer = Customer::find($customerId);
			$data     = getInvoiceData($customer);
			$invoice  = Invoice::create($data);

		} elseif ($invoicePaid) {
			$this->flag = 'paid';

		} elseif ($invoicePending) {
			$this->flag = 'pending';
			$invoice    = $invoicePending;

		} else {
			$this->flag = 'error';
		}
		return $invoice;
	}

	/**
	 * @param $invoice
	 *
	 * @return mixed
	 */
	protected function deleteOldInvoiceItems($invoice)
	{
		return InvoiceItem::where('invoice_id', $invoice->id)->delete();
	}

	/**
	 * Sets Invoice data
	 *
	 * @param  Customer  $customer
	 * @return array
	 */
	protected function getInvoiceData($customer)
	{
		return [
			'staff_id'                => 5,
			'customer_id'             => $customer->id,
			'type'                    => self::INVOICE_TYPES['monthly'],
			'status'                  => self::STATUS['pending_payment'],
			'start_date'              => $customer->add_day_to_billing_end,
			'end_date'                => $customer->add_month_to_billing_end_for_invoice,
			'due_date'                => $customer->billing_end,
			// Todo: add notes
			'subtotal'                => 0,
			'total_due'               => 0,
			'prev_balance'            => 0,
			'payment_method'          => 'USAePay',
			'notes'                   => 'notes',
			'business_name'           => $customer->company_name,
			'billing_fname'           => $customer->fname,
			'billing_lname'           => $customer->lname,
			'billing_address_line_1'  => $customer->billing_address1,
			'billing_address_line_2'  => $customer->billing_address2,
			'billing_city'            => $customer->billing_city,
			'billing_state'           => $customer->billing_state_id,
			'billing_zip'             => $customer->billing_zip,
			'shipping_fname'          => $customer->fname,
			'shipping_lname'          => $customer->lname,
			'shipping_address_line_1' => $customer->shipping_address1,
			'shipping_address_line_2' => $customer->shipping_address2,
			'shipping_city'           => $customer->shipping_city,
			'shipping_state'          => $customer->shipping_state_id,
			'shipping_zip'            => $customer->shipping_zip,
		];
	}


	/**
	 * Creates Invoice-items
	 *
	 * @param  int       $customerId
	 * @return boolean
	 */
	protected function debitInvoiceItems($invoice)
	{
		$invoiceItemIds = $this->addBillableSubscriptions($invoice);
		$this->addSubscriptionAddons($invoiceItemIds);
		$invoiceId = $this->regulatoryFees($invoiceItemIds);
		$this->pendingCharges($invoiceId);
		return true;

	}

	/**
	 * Creates invoice-items for all billable subscriptions
	 * @param $billableSubscriptions
	 * @param $invoice
	 *
	 * @return \Illuminate\Support\Collection
	 */
	protected function addBillableSubscriptions($billableSubscriptions, $invoice)
	{
		$invoiceItems = collect();

		foreach ($billableSubscriptions as $billableSubscription) {
			$subDescription = isset($billableSubscription->plan->description) ? $billableSubscription->plan->description : null;
			$data = [
				'subscription_id' => $billableSubscription->id,
				'product_type'    => InvoiceItem::INVOICE_ITEM_PRODUCT_TYPES['plan'],
				'type'            => InvoiceItem::INVOICE_ITEM_TYPES['plan_charges'],
				'start_date'      => $invoice->start_date,
				'description'     => "(Billable Subscription) {$billableSubscription->plan->description}",
				// Values added below
				'product_id'      => '',
				'amount'          => '',
				'taxable'         => '',
			];

			if ($billableSubscription->is_status_shipping_or_for_activation) {
				$plan     = $billableSubscription->plan;
				$planData = $this->getPlanData($plan);

			}
			// Subscription that are scheduled for Suspension/Closure  are already excluded
			// (sub_status!=’suspend-scheduled’ AND sub_status!=’close-scheduled’)
			// See  in Customer@billableSubscriptions() and
			// Subscription@scopeBillabe()
			elseif ($billableSubscription->is_status_active_not_upgrade_downgrade_status) {
				$plan     = $billableSubscription->plan;
				$planData = $this->getPlanData($plan);

			} elseif ($billableSubscription->status_active_and_upgrade_downgrade_status) {
				$plan     = $billableSubscription->newPlanDetail;
				$planData = $this->getPlanData($plan);
				$data['description'] = "(Billable Subscription) {$plan->description}";

			} else {
				\Log::error(">>>>>>>>>> Subscription status not met in Monthly Invoice for subscription.id = {$billableSubscription} <<<<<<<<<<<<");
				continue;
			}

			$dataForInvoiceItem = array_merge($data, $planData);

			$invoiceItems->push(
				$invoice->invoiceItem()->create($dataForInvoiceItem)
			);
		}

		return $invoiceItems;
	}

	protected function addBillableSubscriptions2($billableSubscriptions, $invoice)
	{
		$invoiceItems = collect();

		$today     = Carbon::yesterday();
		
		$billableSubscriptions = $billableSubscriptions->filter(function($billableSubscriptions, $i) use ($today){
			//dd(7);
			$billingEndParsed = Carbon::parse($billableSubscriptions->created_at);
			// Is today between customer.billing_date and -5 days
			return
				$today <= $billingEndParsed;
		});


		foreach ($billableSubscriptions as $billableSubscription) {
			$subDescription = isset($billableSubscription->plan->description) ? $billableSubscription->plan->description : null;
			$data = [
				'subscription_id' => $billableSubscription->id,
				'product_type'    => InvoiceItem::INVOICE_ITEM_PRODUCT_TYPES['plan'],
				'type'            => InvoiceItem::INVOICE_ITEM_TYPES['plan_charges'],
				'start_date'      => $invoice->start_date,
				'description'     => "(Billable Subscription) {$billableSubscription->plan->description}",
				// Values added below
				'product_id'      => '',
				'amount'          => '',
				'taxable'         => '',
			];

			if ($billableSubscription->is_status_shipping_or_for_activation) {
				$plan     = $billableSubscription->plan;
				$planData = $this->getPlanData($plan);

			}
			// Subscription that are scheduled for Suspension/Closure  are already excluded
			// (sub_status!=’suspend-scheduled’ AND sub_status!=’close-scheduled’)
			// See  in Customer@billableSubscriptions() and
			// Subscription@scopeBillabe()
			elseif ($billableSubscription->is_status_active_not_upgrade_downgrade_status) {
				$plan     = $billableSubscription->plan;
				$planData = $this->getPlanData($plan);

			} elseif ($billableSubscription->status_active_and_upgrade_downgrade_status) {
				$plan     = $billableSubscription->newPlanDetail;
				$planData = $this->getPlanData($plan);
				$data['description'] = "(Billable Subscription) {$plan->description}";

			} else {
				\Log::error(">>>>>>>>>> Subscription status not met in Monthly Invoice for subscription.id = {$billableSubscription} <<<<<<<<<<<<");
				continue;
			}

			$dataForInvoiceItem = array_merge($data, $planData);

			$invoiceItems->push(
				$invoice->invoiceItem()->create($dataForInvoiceItem)
			);
		}

		return $invoiceItems;
	}

	/**
	 * Creates Invoice-items corresponding to subscription-addons
	 *
	 * @param  array       $invoiceItemIds
	 * @return Response
	 */
	protected function addSubscriptionAddons($subscriptionInvoiceItems)
	{
		$subscriptionAddons = collect();

		foreach ($subscriptionInvoiceItems as $invoiceItem) {
			$subscription = $invoiceItem->subscriptionDetail;
			if ($subscription) {
				foreach ( $subscription->billableSubscriptionAddons as $subscriptionAddon ) {
					$addon = $subscriptionAddon->addon;

					$subscriptionAddons->push(
						$subscription->invoiceItemDetail()->create([
							'invoice_id'   => $invoiceItem->invoice_id,
							'product_type' => InvoiceItem::INVOICE_ITEM_PRODUCT_TYPES['addon'],
							'product_id'   => $subscriptionAddon->addon_id,
							'type'         => InvoiceItem::INVOICE_ITEM_TYPES['feature_charges'],
							'start_date'   => $invoiceItem->invoice->start_date,
							'description'  => "(Billable Addon) {$addon->description}",
							'amount'       => $addon->amount_recurring,
							'taxable'      => $addon->taxable,
						])
					);
				}
			}
		}
		return $subscriptionAddons;
	}

	/**
	 * Creates Invoice-items corresponding to regulatory-fees
	 *
	 * @param  array      $invoiceItemIds
	 * @return Response
	 */
	protected function regulatoryFees($subscriptionInvoiceItems)
	{
		$regulatoryFees = collect();

		foreach ($subscriptionInvoiceItems as $invoiceItem) {
			// ToDO: Can there be a case of new_plan_id?
			// I don't see it implemented

			$regulatoryFee = $this->addRegulatorFeesToSubscription(
				$invoiceItem->subscriptionDetail,
				$invoiceItem->invoice,
				self::TAX_FALSE
			);

			$regulatoryFees->push( $regulatoryFee );
		}

		return $regulatoryFees;
	}

	/**
	 * @param $invoice
	 *
	 * @return \Illuminate\Support\Collection
	 */
	protected function pendingCharges($invoice)
	{
		$pendingCharges = collect();

		foreach ($invoice->customer->pendingChargesWithoutInvoice as $pendingChargeWithoutInvoice) {
			$data = [
				'subscription_id' => $pendingChargeWithoutInvoice->subscription_id,
				'product_type'    => '',
				'type'            => $pendingChargeWithoutInvoice->type,
				'start_date'      => $invoice->start_date,
				'description'     => "(Pending Charge) $pendingChargeWithoutInvoice->description",
				'amount'          => $pendingChargeWithoutInvoice->amount,
				'taxable'         => self::TAX_TRUE
			];

			$pendingCharges->push(
				$invoice->invoiceItem()->create($data)
			);

			$pendingChargeWithoutInvoice->update([
				'invoice_id' => $invoice->id
			]);
		}


		return $pendingCharges;
	}

	/**
	 * Generates Plan data
	 *
	 * @param  Plan   $plan
	 * @return array
	 */
	private function getPlanData($plan)
	{
		return [
			'product_id'  => isset($plan->id) ? $plan->id : null,
			'amount'      => isset($plan->amount_recurring) ? $plan->amount_recurring : 0,  // ToDo: CONFIRM THIS FIRST
			'taxable'     => isset($plan->taxable) ? $plan->taxable : 0,
		];
	}

	/**
	 * @param Request $request
	 */
	public function regenerateInvoice(Request $request)
	{
		$customers = Customer::invoicesForRegeneration(); // Customers with open and unpaid invoice and gap between today and billing_end is <=5 days.
		// dd($customers);
		foreach ($customers as $customer) {
			$latestUnpaidInvoice = $customer->openAndUnpaidInvoices()->orderBy('id', 'desc')->first(); // Get latest open unpaid invoice
			$latestPaidInvoice   = false;
			if ($customer->paidOneTimeInvoice->count()) {
				$invoice = $customer->paidOneTimeInvoice()->orderBy('id', 'desc')->first(); // Get latest paid order invoice
				if ($invoice->withSubscription) {
					$latestPaidInvoice = $invoice;
				}
			}
	
			// dd($latestUnpaidInvoice);
			if (isset($latestPaidInvoice->id) && isset($latestUnpaidInvoice->id) && $latestPaidInvoice->id > $latestUnpaidInvoice->id  && $latestPaidInvoice->subtotal!=0) { // If latest order invoice was made after latest open unpaid invoice
				$this->regenerateCoupons($latestUnpaidInvoice->couponUsed); // Regenerate used coupons (add to cycles remaining)
				$regenerateInvoice = $this->processMonthlyInvoice2($latestUnpaidInvoice,$customer, $request, false); // Regenerate monthly invoice
				$updatedLatestUnpaidInvoice = $customer->openAndUnpaidInvoices()->orderBy('id', 'desc')->first(); // Get latest generated monthly invoice
			//	dd($updatedLatestUnpaidInvoice);
				// if ($updatedLatestUnpaidInvoice->id > $latestPaidInvoice->id) { // Confirm latest monthly invoice->id is greater than old monthly invoice
				// 	$creditsUsed = $latestUnpaidInvoice->creditsToInvoice->sum('amount');
				// 	$latestUnpaidInvoice->creditsToInvoice()->update(['invoice_id' => $updatedLatestUnpaidInvoice->id]); // Update invoice id of used credits
				// 	$latestUnpaidInvoice->delete(); // Delete old monthly invoice, also deletes order and invoice item rows.
				// 	$updatedLatestUnpaidInvoice->decrement('total_due', $creditsUsed); // Update total due with old used credits.
				// 	$this->generateInvoice($updatedLatestUnpaidInvoice->order, true, $request); // Send pdf mail.
					$logEntry = [
						'name'      => CronLog::TYPES['regenerate-invoice'],
						'status'    => 'success',
						'payload'   => json_encode($customer),
						'response'  => 'Generated Successfully for customer ' . $customer->id
					];

					$this->logCronEntries($logEntry);
				// }
				$latestUnpaidInvoice->update(['regenerate' => 1]);
				$this->generateInvoice($updatedLatestUnpaidInvoice->order, true, $request);
			}
		}
	}

	/**
	 * @param $usedCoupons
	 */
	protected function regenerateCoupons($usedCoupons)
	{
		foreach ($usedCoupons as $coupon) {
			if (isset($coupon->finiteSubscriptionCoupon)) {
				foreach ($coupon->finiteSubscriptionCoupon as $c) {
					$c->increment('cycles_remaining');
				}
			}
			if (isset($coupon->finiteCustomerCoupon)) {
				foreach ($coupon->finiteCustomerCoupon as $c) {
					$c->increment('cycles_remaining');
				}
			}
		}
	}
}
