<?php

namespace App\Http\Controllers\Api\V1;

use App\Model\Tax;
use Carbon\Carbon;
use App\Model\Invoice;
use App\Model\Customer;
use App\Model\InvoiceItem;
use App\Model\Subscription;
use App\Model\PendingCharge;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Model\CreditToInvoice;
use App\Model\SubscriptionAddon;
use App\Model\subscriptionCoupon;
use App\Http\Controllers\BaseController;

class InvoiceController extends BaseController
{

		public $content;


		public function __construct()
		{
				$this->content = [];
		}



	public function get(Request $request){
		
				$lastdayofmonth = ['1'=>31 , '2'=>28 , '3'=>31 , '4'=>30 , '5'=>31, '6'=> 30 , '7'=>31 , '8'=>31, '9'=>30, '10'=>31 , '11'=>30 , '12'=>31 ];
					
				$cmonth = date("n");
				 
				$current_month_last_date = $lastdayofmonth[$cmonth];

				$billing_end = date("Y-m-$current_month_last_date");

				 $fivedaybefore = $current_month_last_date -5;
				$five_day_before_billing_end = date("Y-m-$fivedaybefore");
		
				$today = date("y-m-d");
				$customers = Customer::with(['company'])->where(
						[
							['billing_end' , '<=' , $billing_end],
						]
				)->get();

			 
				foreach($customers as $customer){

					if($customer->billing_end >= $five_day_before_billing_end){
						continue;
					}
						$subscriptions = Subscription::where('customer_id', $customer->id)->get();
						$pendingcharges = PendingCharge::with(['customer'])->where('customer_id' , $customer->id)->where('invoice_id' , null)->get();

				 foreach ($subscriptions as $subscription){

						 $_invoice = [];
							if($subscription->status= 'active' || $subscription->status = 'shipping' || $subscription->status = 'for-activation' && $pendingcharges){
									$invoices = Invoice::where('customer_id',$customer->id)->get();
									foreach ($invoices as $invoice) {
										 if($invoice->start_date > $customer->billing_end && $invoice->type!= 1){
												$_enddate = $customer->end_date;
												$start_date = date ("Y-m-d", strtotime ($_enddate ."+1 days"));
												$end_date =date ("Y-m-d", strtotime ( $start_date ."+1 months"));
												$due_date = $customer->billing_end;
												$_invoice = Invoice::create([
												 'end_date'=>$start_date,
												 'start_date'=>$end_date,
												 'due_date'=>$due_date,
												 'type'=>1,
												 'status'=>1,
												 'subtotal'=>0,
												 'total_due'=>0,
												 'prev_balance'=>0,
												 'payment_method'=>0,
												 'business_name'=>0,
												 'billingfname'=>0,
												 'billing_fname'=>0,
												 'billing_lname'=>0,
												 'billing_address_line_1'=>0,
												 'billing_address_line_2'=>0,
												 'billing_city'=>0,
												 'billing_state'=>0,
												 'billing_zip'=>0,
												 'shipping_fname'=>0,
												 'shipping_lname'=>0,
												 'shipping_address_line_1'=>0,
												 'shipping_address_line_2'=>0,
												 'shipping_city'=>0,
												 'shipping_state'=>0,
												 'shipping_zip'=>0,

												
												]);
													}
												}
												 
					 
								 
							}else{
											return respond(['subscription status or pendingcharge doesnot match']);
									}
							$_subscriptions = Subscription::with(['plan'])->where('customer_id', $subscription->customer_id)->get();
							 
						 foreach ($_subscriptions as $_subscription ){
						
							
							 
								if($_subscription->status ='active'|| $_subscription->status = 'shipping' || $_subscription->status = 'for-activation'){
										 $name = $_subscription->plans->name;
										 $cost = $_subscription->plans->amount_recurring;
								}elseif($_subscription->status= 'active' && $_subscription->upgrade_downgrade_status != 'downgrade-scheduled'){
												$name= $_subscription->plans->name;
												$cost = $_subscription->plans->amount_recurring;

								}elseif($_subscription->status= 'active' && $_subscription->upgrade_downgrade_status = 'downgrade-scheduled'){
											$name = $_subscription->new_plan->name;
											$cost = $_subscription->new_plan->amount_recurring;

								}elseif($_subscription->status = 'suspended' || $_subscription->status ='closed'){
											continue ;
								}else{

												$invoice_item = InvoiceItem::create([
												 
													'subscription_id'=> $_subscription->id,
													 'product_type'=> 'plan',
													 'product_id'=>$_subscription->plans->id,
													 'type'=>1,
													 'start_date'=>$_invoice->start_date,
													 'description'=>$_subscription->plans->description,
													 'amount'=>$_subscription->plans->amount_recurring,
													 'taxable'=>$_subscription->plans->taxable,
												]);
											}

								
							 $subscriptionaddons = SubscriptionAddon::with('subscription')->where('subscription_id' , $_subscription->id)->get();
								 
							 foreach ($subscriptionaddons as $subscriptionaddon) {
										
										if($subscriptionaddon->status ='removal-scheduled' || $subscriptionaddon->status = 'for-removal'){

												 continue;
												 

										}

										if($_subscription->plans->taxable = 1){
												$taxable = $subscriptionaddon->addon->taxable;

										 }    
										
										$_invoiceitem = InvoiceItem::create([
										 'subscription_id'=>$_subscription->id,
										 'product_type'=>'addon',
										 'product_id'=>$subscriptionaddon->addon->id,
										 'type'=>2,
										 'start_date'=>$_invoice->start_date,
										 'description'=>$subscriptionaddon->addon->description,
										 'amount'=>$subscriptionaddon->addon->amount_recurring,
										 'taxable'=>$taxable,

										]);
									 

									 $regulatory_fee_type = $_subscription->plans->regulatory_fee_type;
									 $regulatory_fee_amount = $_subscription->plans->regulatory_fee_amount;

									 if($regulatory_fee_type = 1){
										$_regulatory_fee_amount =$regulatory_fee_amount;

									 }elseif($regulatory_fee_type =2 && $regulatory_fee_amount = 5){
											$x= 100;                 /*asummed value for running step*/
											$amount = 0.05 * $x;

									 }
									
									 $subscription_id = $_subscription->id;
									 $product_type = '';
									 $product_id = null;
									 $type =5;
									 $start_date = $_invoice['start_date'];
									 $description = $customer->company['regulatory_label'];
									 $Amount = $subscriptionaddon->amount_recurring;
									 $taxable = 0;
												
									} 
								
									 

							}

					}   

					$subscription_coupons = SubscriptionCoupon::where('subscription_id' , $_subscription->id)->where('cycles_remaining', '>', 0)->get();
					 foreach ($subscription_coupons as $subscription_coupon) {
							 $invoice_item = InvoiceItem::create([
							'subscription_id'=>$_subscription->id,
								'type'=> 6,
							 'description'=> 'jst assumed',
							 'amount'=> 500,

							 

						 ]);
						 $subscription_coupon['cycles_remaining'] = $subscription_coupon->cycles_remaining-1;
								 

					 }    

					 echo $customer->billing_state_id; 
						 if($customer->billing_state_id && $customer->billing_state_id!= null){
							echo 'inside';
							 $taxes =  Tax::where('Company_id', $customer->company_id)->get();
							 foreach ($taxes as $tax){
								
							$tax_rate = $tax->rate;
							 echo $tax_rate;
							 }
							}
				}
				
		}



		/**
		 * GET Invoice Details
		 * 
		 * @param  Request    $request
		 * @return Response
		 */
		public function invoiceDetail(Request $request)
		{
			$customer = Customer::hash($request->hash);

			$array = [
					'billing_start' => $customer->billing_start_date_formatted,
					'billing_end'   => $customer->billing_end_date_formatted,
			];

			$date = Carbon::today()->subDays(31)->endOfDay();
			$customerInvoice = Invoice::where([
				'customer_id' => $customer->id,
				'type' => Invoice::TYPES['monthly']
			])->where('start_date', '>', $date)->get()->last();

			if($array['billing_start'] =='NA' || $array['billing_end'] =='NA' || ! $customerInvoice){
				return $this->content = array_merge([
						'charges'  => ['0','00'],
						'past_due' => ['0','00'],
						'payment'  => ['0','00'],
						'total'	   => ['0','00'],
						'due_date' => 'NA'
				], $array);
			}

			$charges = $customerInvoice->subtotal; 

			$payment = $this->getPaymentAndCreditAmount($customerInvoice);

			$pastDue = 0;
			if($customerInvoice->status == Invoice::INVOICESTATUS['closed&upaid']){
				$pastDue = $customerInvoice->total_due;
			}
			$total = ($charges - $payment) + $pastDue;

			if($total < 0){
				$total = 0;
			}
			
			$charges = $this->getAmountFormated($charges);
			$payment = $this->getAmountFormated($payment);
			$pastDue = $this->getAmountFormated($pastDue);
			$total   = $this->getAmountFormated($total);
			$dueDate = $this->getTotalDueDate($customer);


			return $this->content = array_merge([
						'charges'  => $charges, 
						'past_due' => $pastDue,
						'payment'  => $payment,
						'total'	   => $total,
						'due_date' => $dueDate
				], $array);
		}

		// public function invoiceDetail(Request $request)
		// {
		// 	$customer = Customer::hash($request->hash);

		// 	$array = [
		// 			'billing_start' => $customer->billing_start_date_formatted,
		// 			'billing_end'   => $customer->billing_end_date_formatted,
		// 	];

		// 	if($array['billing_start'] =='NA' || $array['billing_end'] =='NA'){
		// 		return $this->content = array_merge([
		// 				'charges'  => ['0','00'],
		// 				'past_due' => ['0','00'],
		// 				'payment'  => ['0','00'],
		// 				'total'	   => ['0','00'],
		// 				'due_date' => 'NA'
		// 		], $array);
		// 	}

		// 	$customerInvoice = Invoice::where('customer_id', $customer->id)->get();

		// 	if(isset($customerInvoice['0'])){
		// 		$invoices = $customerInvoice->where('start_date', $customer->billing_start);
		// 		$charges = [0];
		// 		foreach ($invoices as $invoice) {
		// 			$charges[] = $invoice->cal_total_charges;
		// 		}
		// 		$charges = array_sum($charges);
		// 		$payment = $this->getPaymentAndCreditAmount($customer);
		// 		$pastDue = $customerInvoice->where('start_date', '<', $customer->billing_start)->sum('total_due');
		// 	}else{
		// 		$charges = $payment = $pastDue = 0;
		// 	}
			
		// 	$total = ($charges - $payment) + $pastDue;

		// 	if($total < 0){
		// 		$total = 0;
		// 	}
			
		// 	$charges = $this->getAmountFormated($charges);
		// 	$payment = $this->getAmountFormated($payment);
		// 	$pastDue = $this->getAmountFormated($pastDue);
		// 	$total   = $this->getAmountFormated($total);
		// 	$dueDate = $this->getTotalDueDate($customer);


		// 	return $this->content = array_merge([
		// 				'charges'  => $charges, 
		// 				'past_due' => $pastDue,
		// 				'payment'  => $payment,
		// 				'total'	   => $total,
		// 				'due_date' => $dueDate
		// 		], $array);
		// }


		public function getTotalDueDate($customer)
		{
			$invoice = Invoice::where([['customer_id', $customer->id],['status', '1']])->first();
			if($invoice){
				$dueDate = $invoice->due_date_formatted;
			}else{
				$dueDate = $customer->billing_end_date_formatted;
			}
			return $dueDate;

		}

		public function getAmountFormated($amount){
			return explode(".", (string)self::toTwoDecimals($amount));
		}

    	public function getPaymentAndCreditAmount($customerInvoice)
		{
			return $customerInvoice->creditToInvoice->sum('amount');
		}

		// public function getPaymentAndCreditAmount($customer)
		// {
  //           $creditAmount = $customer->creditsNotAppliedCompletely->sum('pending_credits');
		// 	$id = Invoice::whereCustomerId($customer->id)->pluck('id');
		// 	$creditToInvoiceAmount = CreditToInvoice::whereIn('invoice_id', $id)->whereBetween('created_at', [date($customer->billing_start), date($customer->billing_end)])->sum('amount');

		// 	return $creditToInvoiceAmount + $creditAmount;
		// }



		/**
		 * Generates Float numbers upto 2 decimals
		 * 
		 * @param  int   $amount
		 * @return float
		 */
		protected static function toTwoDecimals($amount)
		{
				return number_format((float)$amount, 2, '.', '');
		}
}
