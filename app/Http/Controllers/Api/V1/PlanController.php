<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Model\Plan;
use App\Model\Device;
use App\Model\DeviceToPlan;


/**
 * 
 */
class PlanController extends Controller
{
	
	function __construct()
	{
		$this->content = array();
	}
  
   public function get(Request $request){

      $company = \Request::get('company');
       
      $device_id = $request->input('device_id');
      

      // if($device_id){
      //   $this->content = Plan::where('device_id', $device_id)->get();
      // }else{
      //       $this->content = Plan::all();
      // }

      $plans = Plan::with(['device']);
      if($device_id){

        //get device
        $device = Device::find($device_id);
        if($device->type == 0){
          //Get plans from device_to_plan
          $device_to_plans = DeviceToPlan::with(['device', 'plan'])->where('device_id', $device_id)->whereHas('device', function($query) use( $device) {
                                  $query->where('device_id', $device->id);
                              })->whereHas('plan', function($query) use( $device) {
                                  $query->where('type', $device->type);
                              })->get();
          $plans = array();

          foreach($device_to_plans as $dp){
            array_push($plans, $dp->plan);
          }
        }else{
          $plans = $plans->where('device_id', $device_id)->where('type', $device->type)->get();
        }


      }else{

        $plans = $plans->whereHas('device', function($query) use ($company) {
                                            $query->where('company_id', $company->id);

                        })->get();

      }

      return response()->json($plans);
        
	}

	 public function find(Request $request, $id){
        $this->content = Plan::find($id);
        return response()->json($this->content);
    }


}