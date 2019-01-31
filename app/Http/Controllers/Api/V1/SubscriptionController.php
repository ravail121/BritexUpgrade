<?php

namespace App\Http\Controllers\Api\V1;

use Validator;
use App\Model\Sim;
use App\Model\Plan;
use App\Model\Order;
use App\Model\Subscription;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;

class SubscriptionController extends BaseController
{


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

        $request->status = ($request->sim_id != 0 || $request->device_id != 0) ? 'shipping' : 'for-activation' ;

        $insertData = $this->generateData($request);

        return Subscription::create($insertData);
    }




    /**
     * Returns data as array which is to be inserted in subscription table
     * 
     * @param  Request  $request
     * @return array
     */
    protected function generateData($request)
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
            'sim_name'                         =>  $request->sim_type,
            'sim_card_num'                     =>  ($request->sim_num) ?: '',
            'old_plan_id'                      =>  0,
            'new_plan_id'                      =>  0,
            'downgrade_date'                   =>  date('Y-m-d'),
            'tracking_num'                     =>  ($order->order_num) ?: 0,
        	'device_id'                        =>  $request->device_id,
        	'device_os'                        =>  ($request->operating_system) ?: '',
        	'device_imei'                      =>  ($request->imei_number) ?: '',
            'subsequent_porting'               =>  ($plan) ? $plan->subsequent_porting : 0,
            'requested_area_code'              =>  ($request->area_code) ?: 0,
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
}
