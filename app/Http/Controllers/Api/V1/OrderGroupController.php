<?php

namespace App\Http\Controllers\Api\V1;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Model\Order;
use App\Model\Plan;
use App\Model\Device;
use App\Model\Tax;
use App\Model\DeviceToSim;
use App\Model\Sim;
use App\Model\OrderGroup;
use App\Model\PlanToAddon;
use App\Model\Coupon;
use App\Model\OrderCoupon;
use App\Model\Customer;


class OrderGroupController extends Controller
{

    public function __construct(){
        $this->content = array();
        $this->output = ['success' => false, 'message' => ''];
    }
    

 public function put(Request $request)
    {
    /*
        Closing/Opening a group
    */
    $validation = Validator::make($request->all(),[
     'order_hash'=>'required|string',
     'action'=>'required|numeric',
     'order_group_id'=>'numeric'
     ]);


    if($validation->fails()){
      return response()->json($validation->getmessagebag()->all());
    }

    $data = $request->all();
    $hash = $data['order_hash'];
    $action = $data['action'];
    

    $order = Order::with('OG')->where('hash', $hash);
    if($action == 1){
        $order = $order->whereHas('OG', function($query) use ($hash) { $query->where('closed', 0); })->get();
        $resp = $this->checkInvalidHash($order);
        if(!is_null($resp)) { return $resp; }
        $order = $order[0];
        //echo $order->og;
        ##close active group
        $order->og->closed = 1;
        $order->og->save();
        //OrderGroup::find($order->og->id)->update(['closed'=>1]);
        $order->update(['active_group_id'=>0]);
        $this->output['success'] = true;


    }else if($action == 2){
        $order = $order->get();
        $resp = $this->checkInvalidHash($order);
        if(!is_null($resp)) { return $resp; }
        $order = $order[0];
         if(isset($data['order_group_id'])){
                $ogi = $data['order_group_id'];
                $order->active_group_id = $ogi;
                $order->save();
                //$order->update(['active_group_id'=>$ogi ]);
                $og = OrderGroup::find($ogi);
                $og->closed = 0;
                $og->save(); //->update(['closed'=>0]);
                $this->output['success'] = true;

        }else{
            $this->output['message'] = 'Please provide order_group_id';
        }

    }else{
        $this->output['message'] = 'Invalid action';
    }

    
    return response()->json($this->output);

    }

    public function taxrate(Request $request)
    {
       
        $rate = Tax::where('state', $request->id)->pluck('rate')->first();
        return $rate;
    }

    public function getSim(Request $request)
    {
        
        return OrderGroup::find($request->id)->sim_num;
        
    }

    public function editSim(Request $request)
    {
        $newSimNumber   = $request->newSimNumber;

        $orderGroupId   = $request->orderGroupId;
        $orderGroup     = OrderGroup::find($orderGroupId);
        
        $newSimNumber   ? $orderGroup->update(['sim_num' => $newSimNumber])  : null;

        return ['new_sim_num' => $newSimNumber];

    }

    

    public function edit(Request $request)
    {
        $orderGroup = OrderGroup::find($request->order_group_id);

        if ($orderGroup->closed == 1) {
            if ($orderGroup->device_id != null) {
                $this->content = $this->filterDevices($orderGroup);
                
            } elseif ($orderGroup->plan_id != null) {
                $this->content = $this->filterPlans($orderGroup);
            }
        }
        return response()->json($this->content);
    }


    private function checkInvalidHash($order)
    {
        if(!count($order)){
            $this->output['message'] = 'Invalid order hash or no closed order groups found';
            return response()->json($this->output);
        }
        return null;
    }

    protected function filterPlans($orderGroup)
    {
        $plans = [];
        $addons = [];
        $data = [];

        if ($orderGroup->sim_type) {
            $data['sim_num'] = $orderGroup->sim_num;
            
        }

        $simId = $this->getSimId($orderGroup);
        $sim   = Sim::find($simId);
        $planIds = Plan::where('carrier_id', $sim->carrier_id)->pluck('id')->toArray();

        if ($orderGroup->addons) {
            $planId = $orderGroup->plan_id;
            $addonToPlans = PlanToAddon::with(['plan', 'addon'])->whereHas('plan', function($query) use ($planId) {
                    $query->where('plan_id', $planId);
                })->get();

            foreach($addonToPlans as $ap){
                array_push($addons, $ap->addon);
            }
            $data['addons'] = $addons;
            foreach ($orderGroup->addons as $addon) {

                $planAddonIds = PlanToAddon::where('addon_id', $addon->id)->pluck('plan_id')->toArray();
            }
            $planIds = array_intersect($planIds, $planAddonIds);
        }
        foreach ($planIds as $id) {
            $plans[] = Plan::find($id);
        }
        $data['plans'] = $plans;

        return $data;
    }

    protected function filterDevices($orderGroup)
    {
        $data    = [];
        $devices = Device::whereIn('show', ['1', '2']);

        if ($orderGroup->plan != null) {
            $simId = $this->getSimId($orderGroup);


            $data            = $this->filterPlans($orderGroup);
            $deviceToSimIds  = DeviceToSim::where('sim_id', $simId)->pluck('device_id')->toArray();
            $plan            = Plan::find($orderGroup->plan_id);
            $deviceIds       = $plan->devices()->pluck('device_id')->toArray();
            $ids             = array_intersect($deviceIds, $deviceToSimIds);

            $devices = $devices->whereIn('id', $ids); 
            $data['devices'] = $devices->with(['device_image', 'device_to_carrier'])->get();
            

        } else {
            $data['devices'] = $devices->with(['device_image', 'device_to_carrier'])->get();
        }


        return $data;
    }


    protected function getSimId($orderGroup)
    {
        if ($orderGroup->sim_id) {
            $simId = $orderGroup->sim_id;
                
        } elseif ($orderGroup->sim_type) {
            $simName = strtolower($orderGroup->sim_type);
            $sims = Sim::all();
            foreach ($sims as $sim) {
                if (strtolower($sim->name) === $simName) {
                    $simId = $sim->id;
                }
            }
        }
        return $simId;
        
    }
    
}
