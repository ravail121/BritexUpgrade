<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Model\Device;
use App\Model\DeviceToImage;
use App\Model\DeviceToType;
use App\Model\Sim;
use App\Model\Plan;


class DeviceController extends Controller
{
	public function  __construct(){
		$this->content=array();
	}

	public function get(Request $request){
		
		$show_list = ['1', '2'];

		$carrier_id = $request->input('carrier_id');

		$plan_id = $request->plan_id;
    
    	$companyId = Plan::where('id',$plan_id)->first()->company_id;

		$dev = Device::where('company_id', $companyId);
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
}
