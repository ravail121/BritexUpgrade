<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Model\Sim;
use App\Model\OrderGroup;
use App\Model\DeviceToSim;

/**
 * 
 */
class SimController extends Controller
{
  
  function __construct()
  {
    $this->content = array();
  }

  private function getSimsByOg($og)
  {
    $sims = [];
    $carrier_id = $og->plan->carrier_id;
    $device_id = $og->device_id;
    if($device_id !== 0){
      $device_to_sims =  DeviceToSim::with(['sim'])->where('device_id', $device_id)->get();
      foreach ($device_to_sims as $ds) {
        $sim = $ds->sim;
        if($sim->carrier_id == $carrier_id){
          array_push($sims, $sim);
        }

      }
    }else{

      $_sims = Sim::where('carrier_id', $carrier_id)->get();
      foreach ($_sims as $sim) {
          array_push($sims, $sim);
      }

    }

    return $sims;

  }
  
   public function get(Request $request)
   {

        $sims = [];
        $plan_id = $request->input('plan_id');
        

        $order_groups = OrderGroup::with(['order', 'sim', 'device', 'plan'])->whereHas('plan', function($query) use ($plan_id) {
                                                $query->where('id', $plan_id);

                        })->get();
        foreach ($order_groups as $og) {
          //echo $og;
            if($og->sim_id == 0){

              $sims = $this->getSimsByOg($og);
              
            }else{

              $_sims = $this->getSimsByOg($og);

              // check if og->sim_id is in $_sims. i.e. already selected
              foreach ($_sims as $sim) {
                if($sim->id == $og->sim_id){
                  $sim['selected'] = 1;
                  array_push($sims, $sim);
                }
                
              }

            }
        }

        $this->content = $sims;
        return response()->json($this->content);
    }

    public function find(request $request, $id)
    {
       $this->content = Sim::find($id);
       return response()->json($this->content);
    }
 }