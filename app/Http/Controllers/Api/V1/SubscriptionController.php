<?php

namespace App\Http\Controllers\Api\V1;

use Validator;
use Carbon\Carbon;
use App\Model\Sim;
use App\Model\Port;
use App\Model\Plan;
use App\Model\Order;
use GuzzleHttp\Client;
use App\Model\Customer;
use App\Model\OrderGroup;
use App\Model\PlanToAddon;
use App\Model\Subscription;
use Illuminate\Http\Request;
use App\Model\OrderGroupAddon;
use App\Model\SubscriptionAddon;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;

class SubscriptionController extends BaseController
{
    const DEFAULT_INT = 0;


    /**
     * Firstly validates the data and then Inserts data to subscription table
     * 
     * @param  Request    $request
     * @return Response
     */
    public function createSubscription(Request $request)
    {
    	$validation = $this->validateData($request);
        if ($validation) {
            return $validation;
        }
        if($request->order_id != 0){
            $response = $this->checkOrderWithCustomer($request->order_id, $request->customer_id);
            if($response){
                return $this->respond(['details' => [$response]], 400);
            }
        }else{
                $validation =  $this->validate_input($request->all(), [
                'customer_id'      => 'required|numeric|exists:customer,id',
            ]);

            if ($validation) {
                return $validation;
            }
        }

        if($request->subscription){
            $subscription = Subscription::find($request->subscription['id']);
            $data = $request->validate([
                'order_id'  => 'required',
            ]);
            $order = Order::find($request->order_id);
            if($request->status == "Upgrade"){
                $data['old_plan_id'] = $subscription->plan_id;
                $data['upgrade_downgrade_date_submitted'] = Carbon::now();
                $data['plan_id'] = $request->plan_id;
                $data['upgrade_downgrade_status'] = 'for-upgrade';
                $data['order_num'] = $order->order_num;
                $updateSubcription = $subscription->update($data);
                return $this->respond(['subscription_id' => $subscription->id]);
            }
            return $this->respond(['same_subscription_id' => $subscription->id]);
        }else{
            $request->status = ($request->sim_id != null || $request->device_id !== null) ? 'shipping' : 'for-activation' ;

            $insertData = $this->generateSubscriptionData($request);
            $subscription = Subscription::create($insertData);

            if(!$subscription) {
                return $this->respondError(['subscription_id' => null]);
            }

            if ($request->porting_number) {
                $arr = $this->generatePortData($request->porting_number, $subscription->id);
                $port = Port::create($arr);
            }
            return $this->respond([
                'success' => 1,
                'subscription_id' => $subscription->id
            ]);
        }
    }

    protected function checkOrderWithCustomer($orderId, $customerId)
    {
        $order = Order::find($orderId);
        if($order){
            if($customerId && $order->customer_id != $customerId){
                return "Order Id ".$orderId." does not belongs to this customer";
            }
        }else{
            return "Order with order_id ".$orderId." does not exisit";
        }
    }

    public function updateSubscription(Request $request)
    {
        $data=$request->validate([
            'id'  => 'required',
            'upgrade_downgrade_status'  => 'required',
        ]);
        $order = Order::whereHash($request->order_hash)->first();
        $data['order_id'] = $order->id;
        $data['order_num'] = $order->order_num;
        $subscription = Subscription::find($data['id']);

        
        $data['upgrade_downgrade_date_submitted'] = Carbon::now();
        if($data['upgrade_downgrade_status'] == "downgrade-scheduled"){
            $data['downgrade_date'] = Carbon::parse($subscription->customerRelation->billing_end)->addDays(1); 
            $data['new_plan_id'] = $subscription->new_plan_id;
        }else{
            $data['old_plan_id'] = $subscription->plan_id;
            $data['plan_id'] = $request->new_plan_id;
        }

        $updateSubcription = $subscription->update($data);

        $removeSubcriptionAddonId = OrderGroupAddon::where([['order_group_id',$request->order_group],['subscription_addon_id', '<>', null]])->pluck('subscription_addon_id');
        if(isset($removeSubcriptionAddonId['0'])){
            $subscriptionAddonData = [
                'status'            => 'removal-scheduled',
                'date_submitted'    => Carbon::now(),

                'removal_date'      => Carbon::parse($subscription->customerRelation->billing_end)->addDays(1),
            ];

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
                'status'          => $request->addon_subscription_id ? SubscriptionAddon::STATUSES['for-adding'] : SubscriptionAddon::STATUSES['active'],
                // 'removal_date'    => date('Y-m-d')
            ]);
            // if($request->addon_subscription_id){
            //     return $this->respond(['new_subscription_addon_id' => $subscriptionAddon->id]);
            // }
        }

        return $this->respond(['subscription_addon_id' => $subscriptionAddon->id]);
    }


    /**
     * Returns data as array which is to be inserted in subscription table
     * 
     * @param  Request  $request
     * @return array
     */
    protected function generateSubscriptionData($request)
    {
        $order = Order::find($request->order_id);
        $plan  = Plan::find($request->plan_id);
        
        // $phone  = ($order->customer_id) && !$request->porting_number ? $order->customer->phone : '';
        // $phone = '';

        if ($request->sim_type == null) {
            $sim = Sim::find($request->sim_id);
            $request->sim_type = ($sim) ? $sim->name : null ;
        }

    	return [
        	'order_id'                         =>  $request->order_id,
        	'customer_id'                      =>  $order ? $order->customer_id : $request->customer_id,
            'order_num'                        =>  $order ? $order->order_num : null,
        	'plan_id'                          =>  $request->plan_id,
            'status'                           =>  $request->status,
            'upgrade_downgrade_status'         =>  '',
            'sim_id'                           =>  $request->sim_id,
            'sim_name'                         =>  $request->sim_type,
            'sim_card_num'                     =>  ($request->sim_num) ?: '',
            'old_plan_id'                      =>  self::DEFAULT_INT,
            'new_plan_id'                      =>  self::DEFAULT_INT,
        	'device_id'                        =>  $request->device_id,
        	'device_os'                        =>  ($request->operating_system) ?: '',
        	'device_imei'                      =>  ($request->imei_number) ?: '',
            'subsequent_porting'               =>  ($plan) ? $plan->subsequent_porting : self::DEFAULT_INT,
            'requested_area_code'              =>  $request->area_code,
        ];
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
        $simNum = null;
        if($request->sim_num){
            $simNum = preg_replace("/\F$/","",$request->sim_num);
            if(preg_match("/[a-z]/i", $simNum)){
                return $this->respond(['details' => ["Invalid Sim Number"]], 400);
            }
        }
    	return $this->validate_input(array_merge($request->except('sim_num'), ['sim_num' => $simNum]), [
                'order_id'         => 'required|numeric',
                'device_id'        => 'nullable|numeric|exists:device,id',
                'plan_id'          => 'required|numeric|exists:plan,id',
                'sim_id'           => 'nullable|required_without:sim_num|numeric|exists:sim,id',
                'sim_num'          => 'nullable|required_without:sim_id|min:19|max:20',
                'sim_type'         => 'nullable|string',
                'porting_number'   => 'nullable|string',
                'area_code'        => 'nullable|string|max:3',
                'operating_system' => 'nullable|string',
                'imei_number'      => 'nullable|digits_between:14,16',
            ]
        );
    }

    /**
     * Validates Data of create-subscription-addon api
     * 
     * @param  Request        $request
     * @return Response       validation response
     */
    protected function validateAddonData($request)
    {
        return $this->validate_input($request->all(), [
                'api_key'          => 'required|string',
                'order_id'         => 'required|numeric',
                'subscription_id'  => 'required|numeric',
                'addon_id'         => 'required|numeric',
            ]
        );
    }

    public function closeSubcription(Request $request)
    {
        $validation = $this->validate_input($request->all(), [
                'phone_number' => 'required|numeric',
            ]
        );
        if ($validation) {
            return $validation;
        }
        $subcriptions = Subscription::where([
            ['phone_number', $request->phone_number],
            ['status', Subscription::STATUS['active']]
        ])->get();

        if(!isset($subcriptions[0])){
            return $this->respond(['message' => "No Active Subcription found with ".$request->phone_number. " Phone Number"]);
        }

        foreach ($subcriptions as $key => $subcription) {
            $subcription->update([
               'status' => Subscription::STATUS['closed'],
               'sub_status' => Subscription::SUB_STATUSES['confirm-closing'],
               'closed_date' => Carbon::now(),
            ]);
        }

        return $this->respond(['success' => 1]);
    }

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
            return $this->respond(['details' => ["Invalid Sim Number"]], 400);
        } 
        
        $subcriptions = Subscription::where([
            ['phone_number', $request->phone_number],
            ['customer_id', $request->customer_id]
        ])->get();

        if(!isset($subcriptions[0])){
            return $this->respond(['message' => "Phone Number Not Found"]);
        }

        foreach ($subcriptions as $key => $subcription) {

            $errorMessage = $this->getSimNumber($request->phone_number, $simNumber, $request->customer_id);

            if(!$errorMessage){
                $subcription->update([
                   'sim_card_num' => $simNumber,
                ]);
                return $this->respond(['success' => 1]);
            }

            return $this->respond(['message' => $errorMessage]);
        }
    }

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
}