<?php

namespace App\Http\Controllers\Api\V1;

use Validator;
use Carbon\Carbon;
use App\Model\Sim;
use App\Model\Port;
use App\Model\Plan;
use App\Model\Order;
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
            return $this->respond(['subscription_id' => $subscription->id]);
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
        	'customer_id'                      =>  $order->customer_id,
            'order_num'                        =>  $order->order_num,
        	'plan_id'                          =>  $request->plan_id,
            'status'                           =>  $request->status,
            'sub_status'                       =>  'active',
            'upgrade_downgrade_status'         =>  '',
        	'upgrade_downgrade_date_submitted' =>  date('Y-m-d'),
            'sim_id'                           =>  $request->sim_id,
            'sim_name'                         =>  $request->sim_type,
            'sim_card_num'                     =>  ($request->sim_num) ?: '',
            'old_plan_id'                      =>  self::DEFAULT_INT,
            'new_plan_id'                      =>  self::DEFAULT_INT,
            'downgrade_date'                   =>  date('Y-m-d'),
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
    	return $this->validate_input($request->all(), [
                'api_key'          => 'required|string',
                'order_id'         => 'required|numeric',
                'device_id'        => 'numeric',
                'plan_id'          => 'numeric',
                'sim_id'           => 'numeric',
                'sim_num'          => 'numeric',
                'sim_type'         => 'string',
                'porting_number'   => 'string',
                'area_code'        => 'string',
                'operating_system' => 'string',
                'imei_number'      => 'digits_between:14,16',
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
            ['status', '<>' , Subscription::STATUS['closed']]
        ])->get();

        if(!isset($subcriptions[0])){
            return $this->respond(['message' => "No Subcription found with ".$request->phone_number. " Phone Number"]);
        }

        foreach ($subcriptions as $key => $subcription) {
            $subcription->update([
               'status' => Subscription::STATUS['closed'],
               'sub_status' => Subscription::SUB_STATUSES['confirm-closing'],
               'closed_date' => Carbon::now(),
            ]);
        }

        return $this->respond(['message' => "Subcription Updated Sucessfully"]);
    }
}
