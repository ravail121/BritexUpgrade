<?php

namespace App\Http\Controllers\Api\V1;

use App\Model\Order;
use App\Model\OrderGroup;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\CustomerStandaloneSim;
use App\Model\CustomerStandaloneDevice;
use App\Http\Controllers\BaseController;

class StandaloneRecordController extends BaseController
{

    const DEFAULT_STATUS       = 'shipping';
    const DEFAULT_TRACKING_NUM = 1;


    public $rules;

    public $data;



    /**
     * Sets default rules for all functions for validation
     */
    public function __construct()
    {
        $this->rules = [
            'api_key'     => 'required|string',
            'customer_id' => 'required|numeric',
            'order_id'    => 'required|numeric',
        ];
    }



    /**
     * Inserts data to customer_standalone_device table
     *  
     * @param  Request $request
     * @return json response
     */
    public function createDeviceRecord(Request $request)
    {
    	$hasError = $this->validateDeviceRecord($request);
	    if($hasError){
	      return $hasError;
	    }

	    $order = Order::find($request->order_id);

        $this->data = $this->setData($request, $order->order_num);

        $record = CustomerStandaloneDevice::create(array_merge($this->data, [
            'device_id' => $request->device_id,
            'imei'      => 'null', 
        ]));


        return $this->respond($record);
    }



    /**
     * Inserts data to customer_standalone_sim table
     *  
     * @param  Request $request
     * @return json response
     */
    public function createSimRecord(Request $request)
    {
        $hasError = $this->validateSimRecord($request);
        if($hasError){
          return $hasError;
        }

        $order = Order::find($request->order_id);

        $this->data = $this->setData($request, $order->order_num);

        $record = CustomerStandaloneSim::create(array_merge($this->data, [
            'sim_id'  => $request->sim_id, 
            'sim_num' => 'null', 
        ]));

        return $this->respond($record);
    }




    /**
     * Sets data as array
     * 
     * @param  Request $request
     * @param  int $orderNum
     * @return array
     */
    protected function setData($request, $orderNum)
    {
        return [
            'customer_id'  => $request->customer_id,
            'order_id'     => $request->order_id,
            'tracking_num' => self::DEFAULT_TRACKING_NUM,
            'status'       => self::DEFAULT_STATUS,
        ];
    }





    /**
     * Validates standalone_device_record_data
     * 
     * @param  Request   $request
     * @return Response
     */
    protected function validateDeviceRecord($request)
    {
		return $this->validate_input($request->all(), array_merge($this->rules, [ 
	      'device_id'   => 'required|numeric',
	    ]));

    }



    /**
     * Validates standalone_sim_record_data
     * 
     * @param  Request   $request
     * @return Response
     */
    protected function validateSimRecord($request)
    {
        return $this->validate_input($request->all(), array_merge($this->rules, [
          'sim_id' => 'required|numeric',
        ]));

    }

}
