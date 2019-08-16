<?php

namespace App\Http\Controllers\Api\V1;
use Validator;
use App\Model\Sim;
use Carbon\Carbon;
use App\Model\Plan;
use App\Model\Addon;
use App\Model\Order;
use App\Model\Device;
use App\Model\Invoice;
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
                $plans = Plan::where(
                        ['type' => $device->type, 'company_id' => $company->id, 'carrier_id' => $device->carrier_id])->get();
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

        $plan = Subscription::where([['id' , $data['subscription_id']], ['customer_id', $data['customer_id']]])->first();
        if(!$plan){
            return $this->respond('Invalid Subcription ID');
        }
        $planId = $plan->plan_id;

        $subscription       = Subscription::with('plan', 'order.allOrderGroup.orderGroupAddon')->find($data['subscription_id']);
        $plan               = Plan::find($planId);
        $accountStatus      = Customer::find($request->customer_id)->account_suspended;
 

        $activeAddon = $this->activeAddons($subscription->id);
        $removalAddon = $this->removalScheduledAddon($subscription->id);
        if (!$subscription) {
            return $this->respond('Invalid Subcription ID');
        }
        if ($subscription->upgrade_downgrade_status == "downgrade-scheduled") {
            return $this->respond('Downgrade Already Scheduled');
        } 

        $condition = [
            ['carrier_id', '=', $subscription->plan->carrier_id],
            ['company_id', '=', $subscription->plan->company_id],
            ['type', '=', $subscription->plan->type]
        ];

        $ifSuspended = ['amount_recurring' , '<', $plan->amount_recurring];
        
        if ($accountStatus == self::ACCOUNT['suspended']) {
            array_push($condition, $ifSuspended);
            $plans = Plan::where(function ($query) use ($condition, $subscription) {
                $query->where($condition)
                      ->orWhere('id', '=', $subscription->plan_id);
            })->orderBy('amount_recurring')->get();

        } else {
            $plans = Plan::where($condition)->orderBy('amount_recurring')->get();
        } 
        $plans['account_status'] = $accountStatus;
        $plans['active_plan'] = $subscription->plan_id;
        $plans['active_addons'] = $activeAddon;
        $plans['removal_scheduled_addon'] = $removalAddon;
        
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
        $allActiveAddon = SubscriptionAddon::where([['subscription_id', $subscriptionId]])->whereNotIn('status', ['removed','removal-scheduled'])->pluck('addon_id')->toArray();
        if($allActiveAddon){
            return implode(",", $allActiveAddon);
        }
        return ;
    }

    private function removalScheduledAddon($subscriptionId)
    {
        $allRemovalAddon = SubscriptionAddon::where([['subscription_id', $subscriptionId]])->whereNotIn('status', ['removed','removal-scheduled'])->pluck('addon_id')->toArray();
        $allRemovalAddon = SubscriptionAddon::where([['subscription_id', $subscriptionId],['status', 'removal-scheduled']])->pluck('addon_id')->toArray();
        if($allRemovalAddon){
            return implode(",", $allRemovalAddon);
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

        $orderData = $subscription->order->toArray();
        $orderData['invoice_id'] = null;
        $orderData['status'] = 0;
        $orderData['hash'] = md5(time());
        $orderData['order_num'] = null;
        $order = Order::create($orderData);

        $orderGroupData = [
            'plan_id'                      => $data['plan'],
            'old_subscription_plan_id'     => $data['active_plans'],
            'subscription_id'              => $data['subscription_id'],
            'order_id'                     => $order->id,
        ];

        $date = Carbon::today()->addDays(6)->endOfDay();
        $invoice = Invoice::where([['customer_id', $subscription->customerRelation->id],['type', '1'],['status','2']])->whereBetween('start_date', [Carbon::today()->startOfDay(), $date])->first();

        $paidInvoice = '0';
        if(isset($invoice)){
            $paidInvoice = '1';
        }
        
        $newPlan = Plan::find($data['plan']);
        $activePlan = Plan::find($data['active_plans']);
        $plan_prorated_amt= $newPlan->amount_recurring - $activePlan->amount_recurring;

        if($plan_prorated_amt < 0){
            $amount  = 0;
        }else{
            $amount = $plan_prorated_amt;
        }

        $orderGroupData['plan_prorated_amt'] = $amount;

        if($plan_prorated_amt > 0){
            //UPGARDE
            $orderGroupData['change_subscription'] = '1';
            $order['status'] = 'upgrade';

        }elseif ($plan_prorated_amt == 0) {
            if($data['plan'] == $data['active_plans']){
                //Added new Addon in Same Plan
                $orderGroupData ['change_subscription'] = '0';
                if(!$request->addon){
                    // return $this->respondError('Downgrade Plan');
                    $order['status'] = 'downgrade';
                }else{
                    $order['status'] = 'sameplan';
                }
            }else{
                $orderGroupData['change_subscription'] = '1';
                $order['status'] = 'upgrade';
            }
        }else{
            //DOWNGRADE
            $orderGroupData ['change_subscription'] = '-1';
            $order['status'] = 'downgrade';
        }

        $orderGroup = OrderGroup::create($orderGroupData);   

        $this->updateAddons($request, $orderGroup, $subscription, $data['active_plans']);


        if($paidInvoice == "1" && $order['status'] != 'downgrade'){
            $orderGroup = OrderGroup::create($orderGroupData);   

            $this->updateAddons($request, $orderGroup, $subscription, $data['active_plans'], 'paidInvoice');
        }

        return $this->respond($order);
    }

    public function updateAddons($request, $orderGroup, $subscription, $plan, $paidInvoice=null)
    {
        $addons = $request->addon;
        if(!$addons){
            $addons = [];
        }
        $activeAddons = [];
        if($request->active_addons){
            $activeAddons = explode(",",$request->active_addons);
            $removedAddon = array_diff($activeAddons, $addons);
            if(!empty($removedAddon) && $paidInvoice ==null){
                $this->updateRemovedAddon($removedAddon, $subscription->id, $orderGroup->id);
            }

        }

        $newAddon=array_diff($addons, $activeAddons);
        $this->insertNewAddon($newAddon, $orderGroup, $subscription);

    }

    public function updateRemovedAddon($addons, $subscription_id, $orderGroupId)
    {
        $data = ['subscription_id' => $subscription_id,
                'prorated_amt'     => 0,
                'order_group_id'   => $orderGroupId,
            ];
        foreach ($addons as $key => $addon) {
            $subscriptionAddon = SubscriptionAddon::where([['subscription_id', $subscription_id],['addon_id',$addon]])->whereIn('status', ['active', 'for-adding'])->first();
            if($subscriptionAddon){
                $data['addon_id'] = $addon;
                $data['subscription_addon_id'] = $subscriptionAddon->id;

                $orderGroupAddon = OrderGroupAddon::create($data);
            }
        }
    }

    public function insertNewAddon($addons, $orderGroup, $subscription)
    {
        foreach ($addons as $key => $addon) {
            $data = [
                'addon_id'       => $addon,
                'order_group_id' => $orderGroup->id,
            ];
            if($orderGroup->change_subscription == "-1"){
                $data ['prorated_amt'] = "0";
            }
            $data ['subscription_id'] = $subscription->id;
        
            OrderGroupAddon::create($data);

        }
    }
}
