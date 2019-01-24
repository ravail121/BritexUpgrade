<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Model\Sim;
use App\Model\Plan;
use App\Model\Order;
use App\Model\OrderGroup;
use App\Model\DeviceToSim;

/**
 * 
 */
class SimController extends BaseController
{
  
  function __construct()
  {
    $this->content = array();
  }

  private function getSimsByOg($og, $plan)
  {
    $company = \Request::get('company');

    $sims = [];
    $_sims = [];
    $carrier_id = $plan->carrier_id;
    $device_id = $og->device_id;

    if($device_id != 0){

      $device_to_sims =  DeviceToSim::with(['sim'])->where('device_id', $device_id)
                        ->whereHas('sim', function($query) use ($company, $carrier_id) {
                                                $query->where(
                                                  [
                                                      ['carrier_id', $carrier_id],
                                                      ['company_id', $company->id]
                                                  ]
                                                );

                        })
                        ->get();
      foreach ($device_to_sims as $sim) {
          array_push($sims, $sim->sim);
      }

    }else{
      $_sims = Sim::where(
          [
            ['carrier_id', $carrier_id],
            ['company_id', $company->id],
          ]
      )->get();
    }

    foreach ($_sims as $sim) {
        array_push($sims, $sim);
    }

    return $sims;

  }
  
   public function get(Request $request)
   {
  
      if(!$request->input('order_hash')){
        return $this->respondError('Invalid order hash');
      }

      $order_hash = $request->input('order_hash');
      $sims = [];
      $plan_id = $request->input('plan_id');

      $order = Order::where('hash', $order_hash)->get();
      if(count($order) < 1){
        return $this->respondError('Invalid order hash');
      }
      $order = $order[0];

      $plan = Plan::where('id', $plan_id)->get();
      if(count($plan) < 1){
        return $this->respondError('Invalid plan');
      }
      $plan = $plan[0];
        


      $order_group = OrderGroup::with(['order', 'sim', 'device', 'plan'])->where('id', $order->active_group_id)->get();

      if (count($order_group)) {

        $og = $order_group[0];

        if($og->sim_id == 0) {
          $sims = $this->getSimsByOg($og, $plan);
          
        } else {
          $_sims = $this->getSimsByOg($og, $plan);
          $sims = $_sims;

          // check if og->sim_id is in $_sims. i.e. already selected
          foreach ($_sims as $sim) {
            if($sim->id == $og->sim_id){
              $sim['selected'] = 1;
              array_push($sims, $sim);
            }
          }

        }
      } else {
        $sims = $this->getSimsByCarrierId($plan);
      }



        $this->content = $sims;
        return response()->json($this->content);
    }

    public function find(request $request, $id)
    {
       $this->content = Sim::find($id);
       return response()->json($this->content);
    }

    private function getSimsByCarrierId($plan)
    {
      $sims = [];
      $company = \Request::get('company');

      $_sims = Sim::where(
        [
          ['carrier_id', $plan->carrier_id],
          ['company_id', $company->id],
        ]
      )->get();

      foreach ($_sims as $sim) {
        array_push($sims, $sim);
      }

      return $sims;

    }
 }