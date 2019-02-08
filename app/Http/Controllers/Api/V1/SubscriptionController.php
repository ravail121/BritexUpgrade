<?php

namespace App\Http\Controllers\Api\V1;

use Validator;
use App\Model\Sim;
use App\Model\Port;
use App\Model\Plan;
use App\Model\Order;
use App\Model\Subscription;
use Illuminate\Http\Request;
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

        $request->status = ($request->sim_id != null || $request->device_id !== null) ? 'shipping' : 'for-activation' ;

        $insertData = $this->generateSubscriptionData($request);

        $subscription = Subscription::create($insertData);

        if ($request->porting_number) {
            $arr = $this->generatePortData($request->porting_number, $subscription->id);
            $port = Port::create($arr);
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

        $subscriptionAddon = SubscriptionAddon::create([
            'subscription_id'  => $request->subscription_id,
            'addon_id'         => $request->addon_id,
            'status'           => 'null',
            'removal_date'     => date('Y-m-d'),

        ]);
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

        $phone  = ($order->customer_id) ? $order->customer->phone : '' ;


        if ($request->sim_type == null) {
            $sim = Sim::find($request->sim_id);
            $request->sim_type = ($sim) ? $sim->name : null ;
        }

    	return [
        	'order_id'                         =>  $request->order_id,
        	'customer_id'                      =>  $order->customer_id,
        	'plan_id'                          =>  $request->plan_id,
        	'phone_number'                     =>  $phone,
            'status'                           =>  $request->status,
            'suspend_restore_status'           =>  'active',
            'upgrade_downgrade_status'         =>  '',
        	'upgrade_downgrade_date_submitted' =>  date('Y-m-d'),
            'sim_id'                           =>  $request->sim_id,
            'sim_name'                         =>  $request->sim_type,
            'sim_card_num'                     =>  ($request->sim_num) ?: '',
            'old_plan_id'                      =>  self::DEFAULT_INT,
            'new_plan_id'                      =>  self::DEFAULT_INT,
            'downgrade_date'                   =>  date('Y-m-d'),
            'tracking_num'                     =>  ($order->order_num) ?: self::DEFAULT_INT,
        	'device_id'                        =>  $request->device_id,
        	'device_os'                        =>  ($request->operating_system) ?: '',
        	'device_imei'                      =>  ($request->imei_number) ?: '',
            'subsequent_porting'               =>  ($plan) ? $plan->subsequent_porting : self::DEFAULT_INT,
            'requested_area_code'              =>  ($request->area_code) ?: self::DEFAULT_INT,
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


}
