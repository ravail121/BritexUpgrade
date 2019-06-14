<?php

namespace App\Http\Controllers\Api\V1;
use Validator;
use App\Model\Sim;
use App\Model\Plan;
use App\Model\Addon;
use App\Model\Order;
use App\Model\Device;
use App\Model\Customer;
use App\Model\OrderGroup;
use App\Model\PlanToAddon;
use App\Model\Subscription;
use App\Model\DeviceToPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Model\OrderGroupAddon;
use App\Model\SubscriptionAddon;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;
use App\Model\Customer;

/**
 * 
 */
class PlanController extends BaseController
{

    const ACCOUNT = [
        'active'    => 0,
        'suspended' => 1
    ];

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
                'customer_id'     => 'required'
            ]
        );

        $planId = Subscription::find($request->subscription_id)->plan_id;

        $subscription       = Subscription::with('plan', 'order.allOrderGroup.orderGroupAddon')->find($data['subscription_id']);
        $plan               = Plan::find($planId);
        $accountStatus      = Customer::find($request->customer_id)->account_suspended;
 

        $activeAddon = $this->activeAddons($subscription->id);

        if (!$subscription) {
            return $this->respondError('Invalid Subcription ID');
        } 

        $condition = [
            ['carrier_id', '=', $subscription->plan->carrier_id],
            ['company_id', '=', $subscription->plan->company_id],
            ['type', '=', $subscription->plan->type]
        ];

        $ifSuspended = ['amount_recurring' , '<', $plan->amount_recurring];
        
        if ($accountStatus == self::ACCOUNT['suspended']) {
            array_push($condition, $ifSuspended);
            $plans = Plan::where($condition)->orderBy('amount_recurring')->get();
        } else {
            $plans = Plan::where($condition)->orderBy('amount_recurring')->get();
        } 

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
        \Log::info($addons);
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
        $subscription = Subscription::find($data['subscription_id']);
        $orderGroup = [
            'plan_id'                      => $data['plan'],
            'old_subscription_plan_id'     => $data['active_plans'],
            'subscription_id'              => $data['subscription_id'],
        ];
        
        $newPlan = Plan::find($data['plan']);
        $activePlan = Plan::find($data['active_plans']);
        $plan_prorated_amt= $newPlan->amount_recurring - $activePlan->amount_recurring;

        if($plan_prorated_amt < 0){
            $plan_prorated_amt = 0;
        }

        $orderGroup['plan_prorated_amt'] = $plan_prorated_amt;

        $updatedOrderGroup = OrderGroup::where('order_id', $subscription->order_id)->where(function ($query) use ($data) {
                $query->where('plan_id', $data['active_plans'])
                      ->orWhere('subscription_id', '<>', null);})->first();

        $this->updateAddons($request, $updatedOrderGroup, $subscription);

        if($plan_prorated_amt != 0){
            //UPGARDE
            $orderGroup['change_subscription'] = '1';

            $updatedOrderGroup->update($orderGroup);

            $subscription->order['status'] = 'upgrade';
        }else{
            //DOWNGRADE
            $orderGroup ['change_subscription'] = '-1';

            $updatedOrderGroup->update($orderGroup);

            $subscription->order['status'] = 'downgrade';
            $subscription->order['newPlan'] = $newPlan;
            $subscription->order['subscription_id'] = $data['subscription_id'];
        }
        return $this->respond($subscription->order);
    }

    public function updateAddons($request, $updatedOrderGroup, $subscription)
    {
        if($request->addon){
            $addons = $request->addon;
            $activeAddons = [];
            if($request->active_addons){
                $activeAddons = explode(",",$request->active_addons);
                $removedAddon=array_diff($activeAddons, $addons);

                $this->updateRemovedAddon($removedAddon, $subscription->id, $updatedOrderGroup->id);
            }

            $newAddon=array_diff($addons, $activeAddons);
            $this->insertNewAddon($newAddon, $updatedOrderGroup->id, $subscription);

        }else{
            $addons = [];
        }
    }

    public function updateRemovedAddon($addons, $subscription_id, $updatedOrderGroupId)
    {
        $data = ['subscription_id' => $subscription_id];
        foreach ($addons as $key => $addon) {
            $subscriptionAddon = SubscriptionAddon::where([['subscription_id', $subscription_id],['addon_id',$addon]])->first();
            OrderGroupAddon::where([['order_group_id', $updatedOrderGroupId],['addon_id',$addon]])->update(array_merge($data,['subscription_addon_id' => $subscriptionAddon->id, ]));

        }
    }

    public function insertNewAddon($addons, $updatedOrderGroupId, $subscription)
    {
        foreach ($addons as $key => $addon) {
            $data = [
                'addon_id'       => $addon,
                'order_group_id' => $updatedOrderGroupId,
            ];
            $orderGroupAddon = OrderGroupAddon::where($data)->where('subscription_id' ,'<>', null)->delete();
            $data ['prorated_amt'] = $subscription->order->addonProRate($addon);
            $data ['subscription_id'] = $subscription->id;
        
            OrderGroupAddon::create($data);

        }
    }
}
