<?php

namespace App\Http\Controllers\Api\V1;
use Validator;
use App\Model\Sim;
use App\Model\Plan;
use App\Model\Order;
use App\Model\Device;
use App\Model\Customer;
use App\Model\OrderGroup;
use App\Model\PlanToAddon;
use App\Model\Subscription;
use App\Model\DeviceToPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Model\SubscriptionAddon;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;

/**
 * 
 */
class PlanController extends BaseController
{
    function __construct()
    {
        $this->content = array();
    }

    public function get(Request $request)
    {
        $company = \Request::get('company');

        $plans = [];

        if ($device_id = $request->input('device_id')) {
            $device = Device::find($device_id);

            if ($device->type == 0) {
                //Get plans from device_to_plan
                // $device_to_plans = DeviceToPlan::with(['device', 'plan'])->where('device_id', $device_id)
                        /*->whereHas('device', function($query) use( $device) {
                            $query->where('device_id', $device->id);
                        })->whereHas('plan', function($query) use( $device) {
                            $query->where('type', $device->type);
                        })*/
                //     ->get();
                // $plans = array();

                // foreach ($device_to_plans as $dp) {
                //     array_push($plans, $dp->plan);
                // }
                $plans = $device->plans;
                
            } else {
                $plans = Plan::where(['type' => $device->type, 'company_id' => $company->id])->get();
            }
        } else {
            $plans = Plan::where('company_id', $company->id)->get();
        }
        return response()->json($plans);
    }

    public function find(Request $request, $id)
    {
        $this->content = Plan::find($id);
        return response()->json($this->content);
    }

    public function check_area_code(Request $request)
    {
        /*
        Check porting
        */
        $validation = Validator::make(
            $request->all(),
            [
                'order_hash' => 'required|string',
                'plan_id' => 'required|numeric'
            ]
        );

        if ($validation->fails()) {
            return response()->json($validation->getmessagebag()->all());
        }

        $data = $request->all();
        $plan_id = $data['plan_id'];
        $hash = $data['order_hash'];

        $order = Order::with(['OG'])->where('hash', $hash)->whereHas(
            'OG',
            function($query) use ($plan_id) {
                $query->where('plan_id', $plan_id);
            }
        )->get();

        if (!count($order)) {
            return response()->json(array('error' => ['invalid order_hash or plan_id']));
        } else {
            $order = $order[0];
            $area_code = $order->og->area_code;
            $plan = Plan::find($order->og->plan_id);
            $plan_area_code = $plan->area_code;

            if ($plan_area_code == 0 && $area_code != '') {
                return response()->json(array('show_area_code' => false, 'clear_area_code' => true));
            } else if ($plan_area_code == 1 && $area_code != '') {
                return response()->json(array('show_area_code' => true, 'area_code' => $area_code, 'require_area_code' => false));
            } else if ($plan_area_code == 2 && $area_code != '') {
                return response()->json(array('show_area_code' => true, 'area_code' => $area_code, 'require_area_code' => true));
            } else if ($plan_area_code == 0 && $area_code == '') {
                return response()->json(['show_area_code' => false]);
            } else if ($plan_area_code == 1 && $area_code == '') {
                return response()->json(array('show_area_code' => true, 'require_area_code' => false));
            } else if ($plan_area_code == 2 && $area_code == '') {
                return response()->json(array('show_area_code' => true, 'require_area_code' => true));
            }

            return response()->json([]);
        }
    }

    public function compatiblePlans(Request $request)
    {
        $data = $request->validate(
            [
                'subscription_id' => 'required',
            ]
        );

        $subscription = Subscription::with('plan', 'order.allOrderGroup.orderGroupAddon')->find($data['subscription_id']);

        $activeAddon = $this->activeAddons($subscription->id);

        if (!$subscription) {
            return $this->respondError('Invalid Subcription ID');
        }

        $plans = Plan::where(
            [
                ['carrier_id', '=', $subscription->plan->carrier_id],
                ['company_id', '=', $subscription->plan->company_id],
                ['type', '=', $subscription->plan->type],
            ]
        )->orderBy('amount_recurring')->get();

        $plans['active_plan'] = $subscription->plan_id;
        $plans['active_addons'] = $activeAddon;

        return $this->respond($plans);
    }

    public function compatibleAddons(Request $request)
    {
        $data = $request->validate(
            [
                'plan_id' => 'required',
            ]
        );

        $addons = PlanToAddon::with('addon')->where(
            [
                ['plan_id', '=', $data['plan_id']],
            ]
        )->get();

        return $addons;
    }

    public function activeAddons($subscriptionId)
    {
        $allActiveAddon = SubscriptionAddon::whereSubscriptionId($subscriptionId)->pluck('addon_id')->toArray();
        if($allActiveAddon){
            return implode(",", $allActiveAddon);
        }
        return ;
    }

    public function checkPlan(Request $request)
    {
        $data = $request->validate(
            [
                'plan'          => 'required',
                'active_plans'  => 'required',
                'id'            => 'required',
                'subscription_id' => 'required',
            ]
        );
        $subcription = Subscription::find($data['subscription_id']);
        $orderGroup = [
            'plan_id'                      => $data['plan'],
            'old_subscription_plan_id'     => $data['active_plans'],
            'subscription_id'              => $data['subscription_id'],
        ];

        $newPlan = Plan::find($data['plan']);
        $activePlan = Plan::find($data['active_plans']);
        $orderGroup['plan_prorated_amt'] = $subcription->order->planProRate($orderGroup['plan_id']);

        if($newPlan->amount_recurring > $activePlan->amount_recurring){
            $orderGroup['change_subscription'] = '1';

            $updatedOrderGroup = OrderGroup::where([['order_id', $subcription->order_id],['plan_id', $data['active_plans']]])->update($orderGroup);
            \Log::info($subcription->order->hash);
            return $this->respond($subcription->order);
        }else{
            $orderGroup = $orderGroup['change_subscription'] = '-1';
            $updatedOrderGroup = OrderGroup::where([['order_id', $subcription->order_id],['plan_id', $data['active_plans']]])->update($orderGroup);
                return $this->respond($subcription->order);
        }
    }
}
