<?php

namespace App\Http\Controllers\Api\V1;

use Validator;
use Carbon\Carbon;
use App\Model\Tax;
use App\Helpers\Log;
use App\Model\Order;
use App\Model\Invoice;
use App\Model\Customer;
use App\Model\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Events\AutoPayStatus;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use App\Model\CustomerStandaloneSim;
use App\Model\CustomerStandaloneDevice;
use App\Http\Controllers\BaseController;
use App\Http\Resources\CustomerCollection;

/**
 * Class CustomerController
 *
 * @package App\Http\Controllers\Api\V1
 */
class CustomerController extends BaseController
{

	/**
	 * CustomerController constructor.
	 */
	public function __construct(){
		$this->content = array();
	}

	/**
	 * @param Request $request
	 *
	 * @return array|false|\Illuminate\Http\JsonResponse|Response
	 */
	public function post(Request $request)
	{
		if ($request->billing_state_id) {
			$validate = $request->validate([
				'billing_state_id'   => 'required|string',
				'billing_fname'      => 'required|string',
				'billing_lname'      => 'required|string',
				'billing_address1'   => 'required|string',
				'billing_address2'   => 'nullable|string',
				'billing_city'       => 'required|string',
				'billing_zip'		 => 'required|string',
			]);
			$customer = Customer::find($request->id);
			if ($validate) {
				$customer->update($validate);
				!$request->billing_address2 ? $customer->update(['billing_address2' => '']) : null;
				return ['success' => 'Details Added', 'id' => $customer->billing_state_id];
			}
			return false;
		}
		$company_id = $request->get('company')->id;
		$customer_id = $request->get('customer_id');

		if(!Order::whereHash($request->order_hash)->whereCompanyId($company_id)->exists()){
			return $this->respond(['error' => 'Order Hash is not valid for the company']);
		}

		$query = Customer::where('company_id', $company_id)->where('email', $request->post('email'));
		if($customer_id){
			$query->where('id', '!=', $customer_id);
		}
		if($query->exists()){
			return $this->respond(['error' => 'Email address already exists.']);
		}
		if ($request->customer_id) {
			if($request->fname){

				$customer = $this->updateCustomer($request);

				return $this->respond(['success' => true, 'customer' => $customer]);
			}

			$order = $this->updateOrder($request);
			if (!$order) {

				return $this->respondError('Customer was not created.');
			}

			return $this->respond(['success' => true]);

		}

		$hasError = $this->validateData($request);
		if ($hasError) {

			return $hasError;
		}


		$data  = $request->except('_url');

		$order = Order::hash($data['order_hash'])->first();

		$customerData = $this->setData($order, $data);

		$customer = Customer::create($customerData);

		if (!$customer) {

			return $this->respondError("problem in creating/updating a customer");
		}
		$order->update(['customer_id' => $customer->id]);

		return $this->respond(['success' => true, 'customer' => $customer]);
	}


	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function subscription_list(Request $request)
	{

		$output       = ['success' => false , 'message' => ''];

		$company      = \Request::get('company');
		$customer_id  = $request->input(['customer_id']);
		$validation   = Validator::make($request->all(),[
			'customer_id' => 'numeric|required']);

		if($validation->fails()){
			$output['message'] = $validation->getMessageBag()->all();
			return response()->json($output);
		}

		$customer = Customer::where('id', $customer_id)->get();
		$customer = $customer[0];

		if ($customer->company_id != $company->id) {

			return Response()->json(array('error' => [' customer id does not exist']));
		}

		$data = Subscription::with(['SubscriptionAddon'])->where('customer_id', $customer_id)->get();

		$output['success'] = true;

		return response()->json($data);
	}

	/**
	 * This function sets some data for creating a customer
	 *
	 * @param Class   $order
	 * @param array   $data
	 * @return array
	 */
	protected function setData($order, $data)
	{

		unset($data['order_hash']);

		if ($order->bizVerification) {
			$data['business_verification_id'] = $order->bizVerification->id;
			$data['business_verified']        = $order->bizVerification->approved;
		} elseif ($order->company->business_verification == 0) {
			$data['business_verification_id'] = null;
			$data['business_verified']        = 1;
		}

		$data['company_id'] = $order->company_id;
		$data['password']   = Hash::make($data['password']);
		$data['hash']       = sha1(time().rand());
		$data['pin']        = $data['pin'];

		return $data;
	}


	/**
	 * @param $request
	 *
	 * @return mixed
	 */
	protected function updateCustomer($request)
	{
		if ($request->customer_id) {
			$request['password'] = Hash::make($request['password']);
			$customer = Customer::find($request->customer_id);

			$customer->update(['fname'    => $request->fname,
			                   'lname'               => $request->lname,
			                   'email'               => $request->email,
			                   'company_name'        => $request->company_name,
			                   'phone'               => $request->phone,
			                   'alternate_phone'     => $request->alternate_phone,
			                   'password'            => $request->password,
			                   'pin'                 => $request->pin,
			                   'shipping_address1'   => $request->shipping_address1,
			                   'shipping_address2'   => $request->shipping_address2,
			                   'shipping_city'       => $request->shipping_city,
			                   'shipping_state_id'   => $request->shipping_state_id,
			                   'shipping_zip'        => $request->shipping_zip,
			                   'shipping_fname'      => $request->shipping_fname,
			                   'shipping_lname'      => $request->shipping_lname
			]);
			return $customer;
		}
	}


	/**
	 * @param $request
	 *
	 * @return mixed
	 */
	protected function updateOrder($request)
	{
		if ($request->customer_id) {
			$order = Order::hash($request->order_hash)->first();
			$customer = Customer::find($request->customer_id);
			$order->update(['customer_id' => $request->customer_id,
			                'shipping_fname'      => $customer->shipping_fname,
			                'shipping_lname'      => $customer->shipping_lname,
			                'shipping_address1'   => $customer->shipping_address1,
			                'shipping_address2'   => $customer->shipping_address2,
			                'shipping_city'       => $customer->shipping_city,
			                'shipping_state_id'   => $customer->shipping_state_id,
			                'shipping_zip'        => $customer->shipping_zip
			]);
			return $order;

		}
	}

	/**
	 * Validates the Create-customer data
	 *
	 * @param  Request $request
	 * @return Response
	 */
	protected function validateData($request) {
		return $this->validate_input($request->all(), [
			'fname'              => 'required|string',
			'lname'              => 'required|string',
			'email'              => 'required|email',
			'company_name'       => 'sometimes|required|string',
			'phone'              => 'required|string',
			'alternate_phone'    => 'nullable|string',
			'password'           => 'required|string',
			'shipping_address1'  => 'required|string',
			'shipping_address2'  => 'nullable|string',
			'shipping_city'      => 'required|string',
			'shipping_state_id'  => 'required|string|max:2',
			'shipping_zip'       => 'required|string',
			'pin'                => 'required|digits:4',
		]);
	}

	/**
	 * Get customer Details
	 *
	 * @param  Request   $request
	 * @return Response
	 */
	public function customerDetails(Request $request)
	{
		$company = $request->get('company')->load('carrier');
		if ($request->tax_id) {
			$rate = Tax::where('state', $request->tax_id)
			           ->where('company_id', $company->id)
			           ->pluck('rate')
			           ->first();
			return ['tax_rate' => $rate];
		}
		$msg = $this->respond(['error' => 'Hash is required']);
		if ($request->hash) {
			$customer = Customer::where(['hash' => $request->hash])->first();
			if ($customer) {
				if($request->paid_monthly_invoice){
					$date = Carbon::today()->addDays(6)->endOfDay();
					$invoice = Invoice::where([
						['customer_id', $customer->id],
						['status', Invoice::INVOICESTATUS['closed&paid'] ],
						['type', Invoice::TYPES['monthly']]
					])->whereBetween('start_date', [Carbon::today()->startOfDay(), $date])->where('start_date', '!=', Carbon::today())->first();

					$customer['paid_monthly_invoice'] = $invoice ? 1: 0;
				}
				$excludedCompanyInfo = $company->exclude([
					'api_key',
					'sprint_api_key',
					'smtp_driver',
					'smtp_host',
					'smtp_encryption',
					'smtp_port',
					'smtp_username',
					'smtp_password',
					'primary_contact_name',
					'primary_contact_phone_number',
					'primary_contact_email_address',
					'address_line_1',
					'address_line_2',
					'city',
					'state',
					'zip',
					'usaepay_api_key',
					'usaepay_live',
					'usaepay_username',
					'usaepay_password',
					'readycloud_api_key',
					'readycloud_username',
					'readycloud_password',
					'tbc_username',
					'tbc_password',
					'apex_username',
					'apex_password',
					'premier_username',
					'premier_password',
					'opus_username',
					'opus_password'
				])->with('carrier')->first();
				$customer['company'] = $excludedCompanyInfo;
				$msg = $this->respond($customer);
			} else {
				$msg = $this->respond(['error' => 'customer not found']);

			}
		}
		return $msg;
	}

	/**
	 * Updates customer details
	 *
	 * @param  Request    $request
	 * @return Response   json
	 */
	public function update(Request $request)
	{
		$data    = $request->except('_url');
		$validation = $this->validateUpdate($data);
		if ($validation) {
			return $validation;
		}

		$data = $this->additionalData($data);

		if (isset($data['password'])) {
			return $this->updatePassword($data);
		}

		$customer = Customer::whereHash($data['hash'])->first();

		$customer->update($data);

		if (isset($data['auto_pay'])) {
			$request->headers->set('authorization', $customer->company->api_key);
			event(new AutoPayStatus($customer));
		}

		return $this->respond(['message' => 'sucessfully Updated']);
	}

	/**
	 * @param $data
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	private function updatePassword($data)
	{
		$currentPassword = Customer::whereHash($data['hash'])->first();

		if (Hash::check($data['old_password'], $currentPassword['password'])) {
			$password['password'] = bcrypt($data['password']);
			Customer::whereHash($data['hash'])->update($password);
			return $this->respond('sucessfully Updated');
		}
		else {
			return $this->respondError('Incorrect Current Password');
		}
	}

	/**
	 * @param $data
	 *
	 * @return array
	 */
	private function additionalData($data)
	{
		$data = array_replace($data,
			array_fill_keys(
				array_keys($data, 'replace_with_null'),
				null
			)
		);
		return $data;
	}

	/**
	 * @param $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function orderUpdate($request)
	{
		$order = $request->only(
			'shipping_fname',
			'shipping_lname',
			'shipping_address1',
			'shipping_address2',
			'shipping_city',
			'shipping_state_id',
			'shipping_zip'
		);
		$order['customer_id'] = $request->id;

		Order::where('customer_id','=', $order['customer_id'])->update($order);

		return $this->respond(['message' => 'sucessfully Updated']);
	}

	/**
	 * Validates the data
	 *
	 * @param  array      $data   validation response
	 */
	protected function validateUpdate($data)
	{
		$id = null;
		if(isset($data['id'])){
			$id = $data['id'];
		}
		return $this->validate_input($data, [
			'id'				=> 'required',
			'fname'             => 'sometimes|required',
			'lname'             => 'sometimes|required',
			'email'             => 'sometimes|required|unique:customer,email,'.$id,
			'billing_fname'     => 'sometimes|required',
			'billing_lname'     => 'sometimes|required',
			'billing_address1'  => 'sometimes|required',
			'billing_city'      => 'sometimes|required',
			'password'          => 'sometimes|required|min:6',
			'hash'              => 'required',
			'shipping_address1' => 'sometimes|required',
			'shipping_city'     => 'sometimes|required',
			'shipping_zip'      => 'sometimes|required',
			'phone'             => 'sometimes|required',
			'pin'               => 'sometimes|required',
		]);
	}

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function checkEmail(Request $request)
	{
		$data =  $request->validate([
			'newEmail'   => 'required',
		]);

		if($request->hash){
			$emailCount = Customer::where([['email' , $request->newEmail], ['company_id', \Request::get('company')->id], ['id', '!=' , $request->id]])->count();
		}else{
			$emailCount = Customer::where([['email' , $request->newEmail],['company_id', \Request::get('company')->id]])->count();
		}

		return $this->respond(['emailCount' => $emailCount]);
	}

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function checkPassword(Request $request)
	{
		$data =  $request->validate([
			'hash'     => 'required',
			'password' => 'required',
		]);

		$currentPassword = Customer::whereHash($request->hash)->first();

		if(Hash::check($request->password, $currentPassword['password'])){
			return $this->respond(['status' => 0]);
		}
		else{
			return $this->respond(['status' => 1]);
		}
	}

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function customerOrder(Request $request)
	{
		$data =  $request->validate([
			'hash'       => 'required',
		]);
		$customer = Customer::whereHash($request->hash)->first();

		$customerDetails = Customer::with('creditAmount.invoice',
			'orders.allOrderGroup.plan',
			'orders.allOrderGroup.device',
			'orders.allOrderGroup.sim',
			'orders.allOrderGroup.order_group_addon.addon',
			'orders.invoice',
			'invoice.invoiceItem')->find($customer['id'])->toArray();

		$customerDetails['invoice'] = $this->getInvoiceData($customerDetails['id']);

		foreach ($customerDetails['orders'] as $key => $order) {

			foreach ($order['all_order_group'] as $orderGroupKey => $orderGroup) {
				if($orderGroup['plan']){
					$customerDetails['orders'][$key]['all_order_group'][$orderGroupKey]['plan']['subscription'] = Subscription::where([['plan_id', $orderGroup['plan']['id']],['order_id', $order['id']]])->first();
				}
				if($orderGroup['device']){
					$customerDetails['orders'][$key]['all_order_group'][$orderGroupKey]['device']['customer_standalone_device'] = CustomerStandaloneDevice::where([['device_id', $orderGroup['device']['id']],['order_id', $order['id']]])->first();
				}
				if($orderGroup['sim']){
					$customerDetails['orders'][$key]['all_order_group'][$orderGroupKey]['sim']['customer_standalone_sim'] = CustomerStandaloneSim::where([['sim_id', $orderGroup['sim']['id']],['order_id', $order['id']]])->first();
				}
			}
		}

		return $this->respond($customerDetails);
	}

	/**
	 * @param $customerId
	 *
	 * @return mixed
	 */
	public function getInvoiceData($customerId)
	{
		return Invoice::whereCustomerId($customerId)
		              ->with('order', 'refundInvoiceItem')
		              ->where( function( $query ){
			              $query->where(function( $subQuery ){
				              $subQuery->has('order');
			              })
			                    ->orWhere(function( $subQuery ){
				                    $subQuery->has('refundInvoiceItem');
			                    });
		              }
		              )->get();
	}

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function listCustomers(Request $request)
	{
		try {
			$perPage = $request->has('per_page') ? (int) $request->get('per_page') : 25;
			$customers = new CustomerCollection(
				Customer::where('company_id', $request->get('company')->id)
				        ->paginate($perPage)
			);
			return $this->respond($customers);
		} catch (\Exception $e) {
			Log::info($e->getMessage(), 'List Customers');
			$response = [
				'status'    => 'error',
				'data'      => $e->getMessage()
			];
			return $this->respond($response, 503);
		}
	}


	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function createCustomerForBulkOrder(Request $request)
	{
		try {
			$requestCompany = $request->get('company');
			$validator = Validator::make( $request->all(), [
				'fname'                         => 'required|string',
				'lname'                         => 'required|string',
				'email'                         => [
					'required',
					'email',
					Rule::unique('customer')->where(function ($query) use ($requestCompany) {
						return $query->where('company_id', $requestCompany->id);
					})
				],
				'phone'                         => 'required|string',
				'alternate_phone'               => 'nullable|string',
				'password'                      => 'required|string',
				'shipping_address1'             => 'required|string',
				'shipping_address2'             => 'nullable|string',
				'shipping_city'                 => 'required|string',
				'shipping_state_id'             => 'required|string|max:2',
				'shipping_zip'                  => 'required|string',
				'pin'                           => 'required|digits:4',
				'billing_state_id'              => 'nullable|string|max:2',
				'billing_fname'                 => 'required_with:billing_state_id|string',
				'billing_lname'                 => 'required_with:billing_state_id|string',
				'billing_address1'              => 'required_with:billing_state_id|string',
				'billing_address2'              => 'nullable|string',
				'billing_city'                  => 'required_with:billing_state_id|string',
				'billing_zip'		            => 'required_with:billing_state_id|string',
				'primary_payment_method'		=> 'integer',
				'primary_payment_card'		    => 'integer',
				'auto_pay'		                => 'integer',
				'surcharge'		                => 'integer',
				'csv_invoice_enabled'		    => 'integer'
			] );

			if ($validator->fails()) {
				$errors = $validator->errors();
				return response()->json( [
					'status' => 'error',
					'data'   => $errors->messages(),
				], 422 );
			}
			$customerData = [
				'company_id'            => $requestCompany->id,
				'fname'                 => $request->fname,
				'lname'                 => $request->lname,
				'email'                 => $request->email,
				'company_name'          => $request->company_name,
				'phone'                 => $request->phone,
				'alternate_phone'       => $request->alternate_phone,
				'password'              => Hash::make($request->get('password')),
				'pin'                   => $request->pin,
				'shipping_address1'     => $request->shipping_address1,
				'shipping_address2'     => $request->shipping_address2,
				'shipping_city'         => $request->shipping_city,
				'shipping_state_id'     => $request->shipping_state_id,
				'shipping_zip'          => $request->shipping_zip,
				'shipping_fname'        => $request->shipping_fname,
				'shipping_lname'        => $request->shipping_lname,
				'hash'                  => sha1(time().rand())
			];
			if($request->has('primary_payment_method')) {
				$customerData['primary_payment_method'] = $request->get('primary_payment_method');
			}
			if($request->has('primary_payment_card')) {
				$customerData['primary_payment_card'] = $request->get('primary_payment_card');
			}
			if($request->has('auto_pay')) {
				$customerData['auto_pay'] = $request->get('auto_pay');
			}
			if($request->has('surcharge')) {
				$customerData['surcharge'] = $request->get('surcharge');
			}
			if($request->has('csv_invoice_enabled')) {
				$customerData['csv_invoice_enabled'] = $request->get('csv_invoice_enabled');
			}
			if($request->has('billing_state_id')) {
				$customerData['billing_state_id'] = $request->get('billing_state_id');
				$customerData['billing_fname'] = $request->get('billing_fname');
				$customerData['billing_lname'] = $request->get('billing_lname');
				$customerData['billing_address1'] = $request->get('billing_address1');
				$customerData['billing_address2'] = $request->get('billing_address2');
				$customerData['billing_city'] = $request->get('billing_city');
				$customerData['billing_zip'] = $request->get('billing_zip');
			}
			$customer = Customer::create($customerData);
			if (!$customer) {
				$errorResponse = [
					'status'    => 'error',
					'data'      => 'Customer was not created'
				];
				return $this->respond($errorResponse, 503);
			} else {
				$successResponse = [
					'status'    => 'success',
					'data'      => $customer
				];
				return $this->respond($successResponse);
			}
		} catch (\Exception $e) {
			Log::info($e->getMessage(), 'Create Customers');
			$response = [
				'status'    => 'error',
				'data'      => $e->getMessage()
			];
			return $this->respond($response, 503);
		}
	}
}