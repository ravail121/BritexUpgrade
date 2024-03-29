<?php
namespace App\Http\Controllers\Api\V1;

use Validator;
use App\Model\Ban;
use Carbon\Carbon;
use App\Model\Sim;
use App\Model\Port;
use App\Model\Plan;
use App\Model\Order;
use GuzzleHttp\Client;
use App\Model\Customer;
use App\Model\PlanToAddon;
use App\Model\CustomerNote;
use App\Model\Subscription;
use Illuminate\Http\Request;
use App\Model\OrderGroupAddon;
use App\Model\SubscriptionAddon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use App\Events\SubcriptionStatusChanged;
use App\Http\Controllers\BaseController;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\Api\V1\Traits\InvoiceCouponTrait;

/**
 * Class SubscriptionController
 *
 * @package App\Http\Controllers\Api\V1
 */
class SubscriptionController extends BaseController
{
	use InvoiceCouponTrait;

	/**
	 * Default Int
	 */
	const DEFAULT_INT = 0;

	/**
	 * Firstly validates the data and then Inserts data to subscription table
	 * @param Request $request
	 *
	 * @return Response|\Illuminate\Http\JsonResponse
	 */
    public function createSubscription(Request $request)
    {
        $validation = $this->validateData($request);
        if ($validation) {
            return $validation;
        }

        $order = Order::find($request->order_id);
        if($request->customer_id){
            if($order->customer_id != $request->customer_id){
                return $this->respond(['details' => "Order Id ".$order->id." does not belongs to this customer"]);
            }
        }
        if($request->subscription){
            $subscription = Subscription::find($request->subscription['id']);

            if($request->status === "Upgrade"){
                $data['old_plan_id'] = $subscription->plan_id;
                $data['upgrade_downgrade_date_submitted'] = Carbon::now();
                $data['plan_id'] = $request->plan_id;
                $data['upgrade_downgrade_status'] = 'for-upgrade';
                $subscription->update($data);

                return $this->respond(['subscription_id' => $subscription->id]);
            }
            return $this->respond(['same_subscription_id' => $subscription->id]);
        } else {

            $validation = $this->validateSim($request);
            if ($validation) {
                return $validation;
            }

            $request->status = $request->sim_id != null || $request->device_id ? 'shipping' : 'for-activation';

            $insertData = $this->generateSubscriptionData($request, $order);
            $subscription = Subscription::create($insertData);

            $this->storeCoupon(json_decode($request->coupon_data, true), $order, $subscription);

            if(!$subscription) {
                return $this->respondError(['subscription_id' => null]);
            }

            
            $request->headers->set('authorization', $request->api_key);

            if($subscription['status'] === 'for-activation' || $subscription['status'] === 'active'){
                event(new SubcriptionStatusChanged($subscription['id']));
            }

            if ($request->porting_number) {
                $arr = $this->generatePortData($request->porting_number, $subscription->id);
                $port = Port::create($arr);
            }

            return $this->respond([
                'success'           => 1,
                'subscription_id'   => $subscription->id
            ]);
        }
    }

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function updateSubscription(Request $request)
    {
        $data = $request->validate([
            'id'                        => 'required',
            'upgrade_downgrade_status'  => 'required',
        ]);
        $subscription = Subscription::find($data['id']);
        if(!($data['upgrade_downgrade_status'] == "sameplan")){ 
            $data['upgrade_downgrade_date_submitted'] = Carbon::now();
            if($data['upgrade_downgrade_status'] == "downgrade-scheduled"){
                $data['downgrade_date'] = Carbon::parse($subscription->customerRelation->billing_end)->addDays(1); 
                $data['new_plan_id'] = $request->new_plan_id;
                
            }else{
                $data['old_plan_id'] = $subscription->plan_id;
                $data['plan_id'] = $request->new_plan_id;
            }
            $updateSubcription = $subscription->update($data);
        }

        $removeSubcriptionAddonId = OrderGroupAddon::where([['order_group_id',$request->order_group],['subscription_addon_id', '<>', null]])->pluck('subscription_addon_id');
        if(isset($removeSubcriptionAddonId['0'])){
            $planToAddon = PlanToAddon::where('plan_id', $request->new_plan_id)->pluck('addon_id');
            if ($planToAddon->contains($request->addon_id) || $data['upgrade_downgrade_status'] == "downgrade-scheduled"){
                $subscriptionAddonData = [
                    'status'            => 'removal-scheduled',
                    'date_submitted'    => Carbon::now(),
                    'removal_date'      => Carbon::parse($subscription->customerRelation->billing_end)->addDays(1),
                ];
            }else{
                $subscriptionAddonData = [
                    'status'            => 'removed',
                    'date_submitted'    => Carbon::now(),
                    'removal_date'      => Carbon::now(),
                ];
            }

            SubscriptionAddon::whereIn('id', $removeSubcriptionAddonId)->update($subscriptionAddonData);
        }

        return $this->respond(['subscription_id' => $subscription->id]);
    }

    /**
     * Firstly validates the data and then Inserts data to subscription_addon table
     * 
     * @param  Request    $request
     * @return Response
     */
    public function subscriptionAddons(Request $request)
    {
        $validation = $this->validateAddonData($request);
        
        if ($validation) {
            return $validation;
        }
        if($request->subscription_addon_id){

            $subscriptionAddon = SubscriptionAddon::find($request->subscription_addon_id);
            
            $planToAddon = PlanToAddon::where('plan_id', $request->plan_id)->pluck('addon_id');
            if ($planToAddon->contains($request->addon_id)){
                $date = Carbon::parse($subscriptionAddon->subscriptionDetail->customerRelation->billing_end)->addDays(1); 
                $subscriptionAddonData = [
                    'status' => 'removal-scheduled',
                    'removal_date' => Carbon::now(),
                    'date_submitted' => $date,
                ];
            }else{
                $subscriptionAddonData = [
                    'status' => 'removed',
                    'removal_date' => Carbon::now(),
                    'date_submitted' => Carbon::now(),
                ];
            }
            $subscriptionAddon->update($subscriptionAddonData);
        }else{
            $subscriptionAddon = SubscriptionAddon::create([
                'subscription_id' => $request->subscription_id,
                'addon_id'        => $request->addon_id,
                'status'          => $request->addon_subscription_id ? SubscriptionAddon::STATUSES['for-adding'] : SubscriptionAddon::STATUSES['for-adding'],
            ]);
        }

        return $this->respond(['subscription_addon_id' => $subscriptionAddon->id]);
    }


    /**
     * Returns data as array which is to be inserted in subscription table
     * 
     * @param  Request  $request
     * @param  Order  $order
     * @return array
     */
    protected function generateSubscriptionData($request, $order)
    {
        $plan  = Plan::find($request->plan_id);

        if ($request->sim_type == null) {
            $sim = Sim::find($request->sim_id);
            $request->sim_type = ($sim) ? $sim->name : null;
        }

        $output = [
	        'order_id'                         =>  $request->order_id,
	        'customer_id'                      =>  $order->customer_id,
	        'company_id'                       =>  $order->customer->company_id,
	        'order_num'                        =>  $order->order_num,
	        'plan_id'                          =>  $request->plan_id,
	        'status'                           =>  $plan && $plan->type === 4 ? 'active' : $request->status,
	        'sim_id'                           =>  $request->sim_id,
	        'sim_name'                         =>  $request->sim_type,
	        'sim_card_num'                     =>  ($request->sim_num) ?: '',
	        'device_id'                        =>  $request->device_id,
	        'device_os'                        =>  ($request->operating_system) ?: '',
	        'device_imei'                      =>  ($request->imei_number) ?: '',
	        'subsequent_porting'               =>  ($plan) ? $plan->subsequent_porting : self::DEFAULT_INT,
	        'requested_area_code'              =>  $request->area_code,
        ];

        if($plan && $plan->type === 4){
	        $output['activation_date']  = Carbon::now();
        }

        return $output;
    }

    /**
     * Returns data as array which is to be inserted in port table
     * 
     * @param  string    $portNumber
     * @param  int       $subscriptionId
     * @return array
     */
    protected function generatePortData($portNumber, $subscriptionId)
    {

        return [
            'subscription_id'             => $subscriptionId,
            'status'                      => self::DEFAULT_INT, 
            'notes'                       => '',
            'number_to_port'              => $portNumber,
            'company_porting_from'        => '',
            'account_number_porting_from' => '',
            'authorized_name'             => '',
            'address_line1'               => '',
            'address_line2'               => '',
            'city'                        => '',
            'state'                       => '',
            'zip'                         => '',
            'ssn_taxid'                   => '',
        ];
    }

    /**
     * Validates Data from Order-Group table
     * 
     * @param  Request        $request
     * @return Response       validation response
     */
    protected function validateData($request)
    {
        $validate =  $this->validate_input($request->all(), [
            'order_id'         => 'required|numeric|exists:order,id',
            'plan_id'          => 'required|numeric|exists:plan,id',
            'porting_number'   => 'nullable|string',
            'area_code'        => 'nullable|string|max:3',
            'operating_system' => 'nullable|string',
            'imei_number'      => 'nullable|digits_between:14,16',
        ]);
        if($validate){
            return $validate;
        }

        if($request->device_id != 0){
            $validate =  $this->validate_input($request->all(), [
                'device_id'        => 'nullable|numeric|exists:device,id',
            ]);
        }
        return $validate;
    }


	/**
	 * @param $request
	 *
	 * @return false|\Illuminate\Http\JsonResponse
	 */
	protected function validateSim($request)
    {
        $simNum = null;
        if($request->sim_num){
            $simNum = preg_replace("/\F$/","", $request->sim_num);
            if(preg_match("/[a-z]/i", $simNum)){
                return $this->respond(['details' => ["Invalid Sim Number"]], 400);
            }
        }
        if ($request->sim_required == 1) {
            return $this->validate_input(array_merge($request->except('sim_num'), ['sim_num' => $simNum]), [
                'sim_id'           => 'nullable|required_without:sim_num|numeric|exists:sim,id',
                'sim_num'          => 'nullable|required_without:sim_id|min:11|max:20',
                'sim_type'         => 'nullable|string',
            ]);
        }

    }

	/**
	 * Validates Data of create-subscription-addon api
	 * @param $request
	 *
	 * @return false|\Illuminate\Http\JsonResponse
	 */
    protected function validateAddonData($request)
    {
        return $this->validate_input($request->all(), [
                // 'api_key'          => 'required|string',
                'order_id'         => 'required|numeric',
                'subscription_id'  => 'required|numeric',
                'addon_id'         => 'required|numeric',
            ]
        );
    }

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function closeSubcription(Request $request)
    {
        $validation = $this->validate_input($request->all(), [
	        'id'            => 'required_without:phone_number|numeric',
            'phone_number'  => 'required_without:id|numeric',
        ] );
        if ($validation) {
            return $validation;
        }
	    $subscriptions = [];
	    $subscriptionNotFoundMessage = '';
        if($request->has('id')){
	        $subscriptions = Subscription::where([
		        ['id', $request->id],
		        ['status', Subscription::STATUS['active']]
	        ])->get();
	        $subscriptionNotFoundMessage = "No Active Subscription found with ". $request->id. " ID";

        } elseif ($request->has('phone_number')) {
	        $subscriptions = Subscription::where([
		        ['phone_number', $request->phone_number],
		        ['status', Subscription::STATUS['active']]
	        ])->get();
	        $subscriptionNotFoundMessage = "No Active Subscription found with ".$request->phone_number. " Phone Number";
        }

        if(!isset($subscriptions[0])){
            return $this->respond([
            	'message'    => $subscriptionNotFoundMessage
            ]);
        }

        foreach ($subscriptions as $key => $subscription) {
	        $subscription->update([
               'status'         => Subscription::STATUS['closed'],
               'sub_status'     => Subscription::SUB_STATUSES['confirm-closing'],
               'closed_date'    => Carbon::now(),
            ]);
        }
        return $this->respond(['success' => 1]);
    }

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function changeSim(Request $request)
    {
        $validation = $this->validate_input($request->all(), [
            "customer_id"     => 'required|numeric',
            "phone_number"    => 'required|numeric',
            "sim_num"         => 'required|min:19|max:20',
            ]
        );
        if ($validation) {
            return $validation;
        }

        $simNumber = preg_replace('/[^0-9]/', '', $request->sim_num);
        $length = strlen($simNumber);
        if($length >20 || $length < 19){
            return $this->respond(['message' => "Invalid SIM Number"]);
        } 
        
        $subcriptions = Subscription::where([
            ['phone_number', $request->phone_number],
            ['customer_id', $request->customer_id]
        ])->get();

        if(!isset($subcriptions[0])){
            return $this->respond(['message' => "Phone Number Not Found"]);
        }

        foreach ($subcriptions as $key => $subcription) {
        	if($request->has('is_ultra') && $request->is_ultra == '1') {
		        $errorMessage = $this->getSimNumberFromUltra( $request->phone_number, $simNumber, $request->customer_id );
	        } else {
		        $errorMessage = $this->getSimNumber($request->phone_number, $simNumber, $request->customer_id);
	        }

            if(!$errorMessage){
                $this->updateRecord($subcription, $simNumber, $subcription->sim_card_num);

                return $this->respond(['success' => 1]);
            }

            return $this->respond(['message' => $errorMessage]);
        }
    }

	/**
	 * @param $subcription
	 * @param $simNumber
	 * @param $OldsimCardNum
	 */
	protected function updateRecord($subcription, $simNumber, $OldsimCardNum)
    {
        $subcription->update([
           'sim_card_num' => $simNumber,
        ]);

        CustomerNote::create([
            'staff_id'      => 0,
            'text'          => 'Customer changed SIM on '.$subcription->phone_number.' from '.$OldsimCardNum.' to '.$simNumber,
            'date'          => Carbon::now(),
            'customer_id'   => $subcription->customer_id,
        ]);
    }

	/**
	 * @param $phoneNumber
	 * @param $sim_number
	 * @param $customerId
	 *
	 * @return mixed|null
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	protected function getSimNumber($phoneNumber, $sim_number, $customerId)
    {
        $customer = Customer::find($customerId);

        $headers = [
            'X-API-KEY' => $customer->company->goknows_api_key,
        ];

        $client = new Client([
            'headers' => $headers
        ]);

        $errorMessage = null;
        try {
            $client->request('PUT', env('GO_KNOW_URL').$phoneNumber, [
                'form_params' => [
                    'sim_number' => $sim_number,
                ]
            ]);
        } catch (\Exception $e) {

            $responseBody = json_decode($e->getResponse()->getBody(true), true);
            $errorMessage = $responseBody['message'];
        }
        return $errorMessage;
    }

	/**
	 * @param Request $request
	 *
	 * @return mixed
	 */
	public function updateSubLabel(Request $request)
    {
        $data['label'] = $request->label;
        $data['id'] = $request->id;

        $subcriptions = Subscription::find($request->id);
        $update = $subcriptions->update(['label' => $request->label]);

        if($update){
            return $subcriptions;
        }
    }

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getSubscriptionByPhoneNumber(Request $request)
    {
        $company_id = $request->get('company')->id;
        $validation = $this->validate_input($request->all(), ["phone_number"    => 'required|numeric',]);
        if ($validation) {
            return $validation;
        }
        $subscription = Subscription::where('phone_number', $request->phone_number)
            ->where('company_id', $company_id)
            ->where('status', 'active')
            ->orderBy('id', 'desc')->select('id as subscription_id')->first();
        if(!$subscription){
            $data['subscription_id'] = 0;
        }else{
            $data['subscription_id'] = $subscription['subscription_id'];
        }
        return $data;
    }

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getSubscriptionDetails(Request $request)
	{
		$company_id = $request->get('company')->id;
		$validation = $this->validate_input($request->all(), ["id"    => 'required|numeric']);
		if ($validation) {
			return $validation;
		}
		$subscription = Subscription::where('id', $request->id)
		                            ->where('company_id', $company_id)
		                            ->orderBy('id', 'desc')->first();
		return $subscription;

    }


	/**
	 * @param Request $request
	 *
	 * @return mixed
	 */
	public function updateRequestedZip(Request $request)
	{
		try {
			$subscriptions = Subscription::find($request->id);
			$update = $subscriptions->update(['requested_zip' => $request->requested_zip]);

			if($update){
				return $subscriptions;
			}
		} catch (\Exception $e) {
			return $this->respondWithError($e->getMessage());
		}

	}


	/**
	 * @param $phoneNumber
	 * @param $sim_number
	 * @param $customerId
	 *
	 * @return mixed|null
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	protected function getSimNumberFromUltra($phoneNumber, $sim_number, $customerId)
	{
		$customer = Customer::find($customerId);

		$companyApiKey = $customer->company->api_key;

		$client = new Client([
			'Content-Type' => 'application/json'
		]);

		$errorMessage = null;
		try {
			$client->request('POST', config('internal.__BRITEX_ULTRA_API_BASE_URL') . 'SwapSim', [
				'form_params'   => [
					'code'          => $companyApiKey,
				],
				'body'          => [
					'old_sim_iccid' => $phoneNumber,
					'new_sim_iccid' => $sim_number,
					'company_id'    => $customer->company->id
				]
			]);
		} catch (\Exception $e) {
			$responseBody = json_decode($e->getResponse()->getBody(true), true);
			$errorMessage = $responseBody['message'];
		}
		return $errorMessage;
	}

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function validateIfTheSimIsUsed(Request $request)
	{
		$companyId = $request->get('company')->id;
		$validation = $this->validate_input($request->all(), ['sim_number'    => 'required|min:19|max:20']);
		if ( $validation ) {
			$validationErrorResponse = [
				'status'    => false,
				'message'   => 'Invalid Sim Number'
			];
			return $this->respond($validationErrorResponse);
		}
		$simNum = preg_replace("/\F$/","", $request->sim_number);
		if(preg_match("/[a-z]/i", $simNum)){
			return $this->respond( [ 'status' => false, 'message' => 'Invalid Sim Number' ]);
		}
		$subscriptionExists = Subscription::where([
			['company_id', $companyId],
			['sim_card_num', $simNum],
			['status', '!=', 'closed']
		])->exists();

		if($subscriptionExists){
			return $this->respond( [ 'status' => false, 'message' => "The SIM can't be used." ]);
		}

		return $this->respond([ 'status' => true ]);
	}

	/**
	 * @param Request $request
	 *
	 * @return array|\Illuminate\Http\JsonResponse
	 */
	public function activateSubscription(Request $request)
	{
		$data = $request->all();
		$subscription_id = $request->input('subscription_id');
		$data['phone_number'] = preg_replace('/[^\dxX]/', '', $data['phone_number']);
		$ban_number = $data['ban_number'];
		$company_id = $request->get('company')->id;
		try {

			$validation = Validator::make($data, [
				'subscription_id'  => [
					'required',
                    'numeric',
					Rule::exists('subscription', 'id')->where(function ($query) use ($company_id){
						return $query->where([
							['status', 'for-activation'],
							['company_id', $company_id]
						]);
					})
				],
				'phone_number'  => [
					'required',
					Rule::unique('subscription')->ignore($subscription_id)->where(function ($query) use ($company_id) {
						return $query->where([
							['status', '!=', 'closed']
						]);
					})
				],
				'ban_number'            => [
					'required',
					Rule::exists('ban', 'number')->where(function ($query) use ($company_id){
						return $query->where( 'company_id', '=', $company_id);
					}),
				],
			]);


			if ( $validation->fails() ) {
				$errors = $validation->errors();
				$validationErrorResponse = [
					'status' => 'error',
					'data'   => $errors->messages()
				];
				return $this->respond($validationErrorResponse, 422);
			}


			$ban = Ban::where([['number', $ban_number], ['company_id', $company_id]])->first();

			$activationData = [
				'status'          => 'active',
				'activation_date' => Carbon::today()->toDateString(),
				'ban_id'          => $ban->id,
				'phone_number'    => $data['phone_number']
			];

			$updateSubscription = Subscription::where('id', $subscription_id)->update($activationData);
			if( !$updateSubscription )
			{
				$validationErrorResponse = [
					'status' => 'error',
					'data'   => 'Failed to activate subscription'
				];
				return $this->respond($validationErrorResponse, 400);
			}

			return response()->json( [
				'status'   => 'success',
				'messages' => 'Subscription activated'
			] );
		} catch (\Exception $e) {
			Log::info($e->getMessage() . ' on line number: '.$e->getLine() . ' activate subscription');
			return [
				'status'  => 'error',
				'message' => $e->getMessage()
			];
		}
	}

	/**
	 * @param Request $request
	 *
	 * @return array|\Illuminate\Http\JsonResponse
	 */
	public function queryActiveSubscriptionWithAddon(Request $request) {
		$data = $request->all();
		$addonId = $request->input('addon_id');
		try {

			$validation = Validator::make($data, [
				'addon_id'  => [
					'required',
					'numeric',
					'exists:addon,id'
				]
			]);


			if ( $validation->fails() ) {
				$errors = $validation->errors();
				$validationErrorResponse = [
					'status' => 'error',
					'data'   => $errors->messages()
				];
				return $this->respond($validationErrorResponse, 422);
			}
			$subscriptions = Subscription::where('status', '!=', 'closed')
			                             ->whereHas('subscriptionAddon', function(Builder $subscriptionAddon) use ($addonId) {
												$subscriptionAddon->where('addon_id', $addonId);
										})->get(['id', 'company_id', 'phone_number', 'status', 'sim_card_num', 'device_imei']);

			return response()->json( [
				'status'   => 'success',
				'data'      => $subscriptions
			] );
		} catch (\Exception $e) {
			Log::info($e->getMessage() . ' on line number: '.$e->getLine() . ' query active subscription with addon');
			return [
				'status'  => 'error',
				'message' => $e->getMessage()
			];
		}

	}

}