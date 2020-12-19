<?php

namespace App\Http\Controllers\Api\V1;

use App\Model\Order;
use Illuminate\Http\Request;
use App\Model\CustomerStandaloneSim;
use App\Model\CustomerStandaloneDevice;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\Api\V1\Traits\InvoiceCouponTrait;

/**
 * Class StandaloneRecordController
 *
 * @package App\Http\Controllers\Api\V1
 */
class StandaloneRecordController extends BaseController
{
	use InvoiceCouponTrait;

	/**
	 *
	 */
	const DEFAULT_STATUS       = 'shipping';

	/**
	 *
	 */
	const DEFAULT_TRACKING_NUM = 1;

	/**
	 *
	 */
	const DEFAULT_PROSSED = 0;

	/**
	 * @var string[]
	 */
	public $rules;

	/**
	 * @var
	 */
	public $data;

    /**
     * Sets default rules for all functions for validation
     */
    public function __construct()
    {
        $this->rules = [
            // 'api_key'     => 'required|string',
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

        $this->data = $this->setData($request);

        $record = CustomerStandaloneDevice::create(array_merge($this->data, [
            'device_id' => $request->device_id,
            'imei'      => 'null', 
        ]));

        $order = Order::find($request->order_id);
        $this->storeCoupon(json_decode($request->coupon_data), $order);

        return $this->respond(['device_id' => $record->id]);
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

        $this->data = $this->setData($request);

        $record = CustomerStandaloneSim::create(array_merge($this->data, [
            'sim_id'  => $request->sim_id, 
            'sim_num' => 'null', 
        ]));

        $order = Order::find($request->order_id);
        $this->storeCoupon(json_decode($request->coupon_data), $order);

        return $this->respond(['sim_id' => $record->id]);
    }

    /**
     * Sets data as array
     * 
     * @param  Request $request
     * @return array
     */
    protected function setData($request)
    {
        $order = Order::find($request->order_id);
        return [
            'customer_id'  => $request->customer_id,
            'order_id'     => $request->order_id,
            'order_num'    => $order->order_num,
            'status'       => self::DEFAULT_STATUS,
            'processed'    => self::DEFAULT_PROSSED,
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
