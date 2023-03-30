<?php

namespace App\Http\Controllers\Api\V1\Traits;


use App\Events\InvoiceEmail;
use App\Model\Customer;
use App\Model\CustomerCreditCard;
use App\Model\Invoice;
use App\Model\InvoiceItem;
use App\Model\Order;
use Carbon\Carbon;

/**
 * @author Prajwal Shrestha
 * Trait BulkOrderTrait
 *
 * @package App\Http\Controllers\Api\V1\Traits
 */
trait CardTrait
{
	/**
	 * @param      $request
	 * @param null $command
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	protected function processTransaction($request, $command = null)
	{
		$order = Order::where('customer_id', $request->customer_id)->first();
		if ($order) {
			$this->tran = $this->setUsaEpayData($this->tran, $request, $command);
			if($this->tran->Process()) {
				if($request->without_order){
					$this->response = $this->transactionSuccessfulWithoutOrder($request, $this->tran);
				}else{
					$this->response = $this->transactionSuccessful($request, $this->tran);
					$data    = $this->setInvoiceData($order, $request);
					$invoice = Invoice::create($data);

					if ($invoice) {
						$orderCount = Order::where( [
							[ 'status', 1 ],
							[ 'company_id', $order->company_id ]
						] )->max( 'order_num' );
						$order->update( [
							'status'         => 1,
							'invoice_id'     => $invoice->id,
							'order_num'      => $orderCount + 1,
							'date_processed' => Carbon::today()
						] );
					}
				}
			} else {
				$this->response = $this->transactionFail($order, $this->tran);
				if($request->without_order){
					return response()->json(['message' => ' Card  ' . $this->tran->result . ', '. $this->tran->error, 'transaction' => $this->tran]);
				}
			}
		} else {
			$this->response = $this->transactionFail(null, $this->tran);
			if($request->without_order){
				return response()->json(['message' => ' Card  ' . $this->tran->result . ', '. $this->tran->error, 'transaction' => $this->tran]);
			}
		}
		return $this->respond($this->response);
	}

	/**
	 * This function charges now card without order which is done by admin
	 * from admin portal
	 * inserts data to credits and customer_credit_cards table
	 * @param $request
	 * @param $tran
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	protected function transactionSuccessfulWithoutOrder($request, $tran)
	{
		$credit = $this->createCredits($request, $tran);
		$invoice = $this->processCreditInvoice($request, $tran, $credit);
		$this->payUnpaiedInvoice($tran->amount, $request, $credit);

		$response = response()->json(['success' => true, 'transaction' => $tran]);
		return $response;
	}

	/**
	 * @param $data
	 * @param $tran
	 * @param $credit
	 *
	 * @return null
	 */
	public function processCreditInvoice($data, $tran, $credit)
	{
		$card = CustomerCreditCard::find($data['credit_card_id']);
		$customer = Customer::find($card->customer_id);
		// create invoice other than payment_type is "Manual Payment"
		if(!empty($data->get('payment_type')) && $data->get('payment_type') != 'Manual Payment') {
			$staff_id = $data->staff_id;
			if($staff_id){

			}else{
				$staff_id = null;
			}
			$invoiceStartDate = $this->getInvoiceDates($customer);
			$invoiceEndDate = $this->getInvoiceDates($customer, 'end_date');
			$invoiceDueDate = $this->getInvoiceDates($customer, 'due_date', true);
			$invoice = [
				'staff_id'                  => $staff_id,
				'customer_id'               => $card->customer_id,
				'type'                      => '2',
				'start_date'                => $invoiceStartDate,
				'end_date'                  => $invoiceEndDate,
				'status'                    => '2',
				'subtotal'                  => $data['amount'],
				'total_due'                 => '0',
				'prev_balance'              => '0',
				'payment_method'            => '1',
				'notes'                     => '',
				'due_date'                  => $invoiceDueDate,
				'business_name'             => $customer->company_name,
				'billing_fname'             => $customer->fname,
				'billing_lname'             => $customer->lname,
				'billing_address_line_1'    => $card->billing_address1,
				'billing_address_line_2'    => $card->billing_address2,
				'billing_city'              => $card->billing_city,
				'billing_state'             => $card->billing_state_id,
				'billing_zip'               => $card->billing_zip,
				'shipping_fname'            => $customer->shipping_fname,
				'shipping_lname'            => $customer->shipping_lname,
				'shipping_address_line_1'   => $customer->shipping_address1,
				'shipping_address_line_2'   => $customer->shipping_address2,
				'shipping_city'             => $customer->shipping_city,
				'shipping_state'            => $customer->shipping_state_id,
				'shipping_zip'              => $customer->shipping_zip
			];

			$newInvoice = Invoice::create($invoice);
			$credit->update(['invoice_id' => $newInvoice->id]);

			$type = '9';
			$product_type = $data['payment_type'] ?: 'Manual Payment';
			$description = $data['description'] ?: 'Manual Payment';
			if ($data['payment_type'] == 'Custom Charge') {
				$product_type = '';
				$type = '3';
				$credit->update(['applied_to_invoice' => 1]);
				/**
				 * Add to credit_to_invoice table
				 */
				$credit->usedOnInvoices()->create([
					'invoice_id'    => $newInvoice->id,
					'amount'        => $newInvoice->subtotal,
					'description'   => "{$newInvoice->subtotal} applied on invoice id {$newInvoice->id}",
				]);
			}

			$invoiceItem = [
				'invoice_id'        => $newInvoice->id,
				'product_type'      => $product_type,
				'type'              => $type,
				'subscription_id'   => $data['subscription_id'],
				'start_date'        => Carbon::today(),
				'description'       => $description,
				'amount'            => $data['amount'],
				'taxable'           => '0',
			];

			$newInvoiceItem = InvoiceItem::create($invoiceItem);
			$paymentLog = $this->createPaymentLogs(null, $tran, 1, $card, $newInvoice);
			if ($data['payment_type'] == 'Custom Charge') {
				$invoice = $newInvoice;
				$pdf = PDF::loadView('templates/custom-charge-invoice', compact('invoice'));
				event(new InvoiceEmail($invoice, $pdf, 'custom-charge'));
			}

			return $newInvoice;
		}
		$order = new Order();
		$order->customer_id = $card->customer_id;
		$paymentLog = $this->createPaymentLogs($order, $tran, 1, $card, null);
		return null;
	}


}