<?php

namespace App\Http\Controllers\Api\V1;

use App\Model\Sim;
use App\Model\Plan;
use App\Model\Device;
use App\Model\DefaultImei;
use App\Model\DeviceToSim;
use App\Model\DeviceToType;
use App\Model\DeviceToImage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;

class DeviceController extends Controller
{
	public function  __construct(){
		$this->content = array();
	}

	public function get(Request $request)
	{		
		$show_list = ['1', '2'];


		$carrier_id = $request->input('carrier_id');


		$dev = Device::whereIn('show', $show_list);

		if ($request->plan_id) {
			
			$plan      = Plan::find($request->plan_id);
			$deviceIds = $plan->devices()->pluck('device_id')->toArray();
			$dev       = $dev->whereIn('id', $deviceIds);
		}

		if($carrier_id){
			$dev = $dev->where('carrier_id', $carrier_id);
		}
		
		$dev = $dev->with(['device_image', 'device_to_carrier'])->get();
		$_sims = Sim::where('show', $show_list )->get();

		$sims = array();
		foreach($_sims as $sim){
			array_push($sims, array(
				'id'=>$sim->id,
				'amount'=>$sim->amount_alone,
				'company_id'=>$sim->company_id,
				'carrier_id'=>$sim->carrier_id,
				'name'=>$sim->name,
			));
		}

		$this->content['devices'] = $dev;
		$this->content['sims'] = $sims;

		return Response()->json($this->content);
	}



	public function find(Request $request, $id){
		$this->content = Device::find($id);
        return response()->json($this->content);
	}


	public function getImei(Request $request)
	{
		$imeiNumber = DefaultImei::where('type', $request->plan_type)->where('os', $request->os)->first();
		if ($imeiNumber) {
			$this->content = ['default_imei' => $imeiNumber->code];
		} else {
			$this->content = ['default_imei' => 'not found'];
		}
		return response()->json($this->content);
	}
}
