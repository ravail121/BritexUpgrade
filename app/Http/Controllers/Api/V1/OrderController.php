<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;

use Validator;
use App\Model\Order;
use App\Model\Company;
use App\Model\OrderGroup;
use App\Model\PlanToAddon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Model\OrderGroupAddon;
use Illuminate\Support\Collection;
use App\Model\BusinessVerification;
use App\Http\Controllers\Controller;
//use Illuminate\Support\Facades\Auth;

class OrderController extends BaseController
{
    public function __construct(){
        $this->content = array();
    }
    public function get(Request $request){


        $hash = $request->input('order_hash');
        //$order = array(); //'order'=>array(), 'order_groups'=>array());

        $order = [];
        $ordergroups = [];
        
        if($hash){
            $order_groups = OrderGroup::with(['order', 'sim', 'device', 'device.device_image'])->whereHas('order', function($query) use ($hash) {
                        $query->where('hash', $hash);})->get();

            //print_r($order_groups);
            
            foreach($order_groups as $og){

                if($order == []){
                    
                    $order =  [
                        "id"                     => $og->order->id,
                        "order_hash"             => $og->order->hash,
                        "active_group_id"        => $og->order->active_group_id,
                        "active_subscription_id" => $og->order->active_subscription_id,
                        "order_num"              => $og->order->order_num,
                        "status"                 => $og->order->status,
                        "customer_id"            => $og->order->customer_id,
                        'order_groups'           => [],
                        'business_verification'  => null,
                        'operating_system'       => null,
                        'imei_number'            => null,
                    ];
                }

            
                
                $tmp = array(
                        'id'               => $og->id,
                        'sim'              => $og->sim,
                        'sim_num'          => $og->sim_num,
                        'sim_type'         => $og->sim_type,
                        'plan'             => $og->plan,
                        'addons'           => [],
                        'porting_number'   => $og->porting_number,
                        'area_code'        => $og->area_code,
                        'device'           => $og->device_detail,
                        'operating_system' => $og->operating_system,
                        'imei_number'      => $og->imei_number,
                    );

                

                $_addons = OrderGroupAddon::with(['addon'])->where('order_group_id', $og->id )->get();
                foreach ($_addons as $a) {
                    array_push($tmp['addons'], $a['addon']);
                }

                array_push($ordergroups, $tmp);    
            }
            if (count($order)) {

                $businessVerification = BusinessVerification::where('order_id', $order['id'])->first();
                $order['business_verification'] = $businessVerification;
            }

        }

        $order['order_groups'] = $ordergroups;
        $this->content = $order;
        // dd($this->content);
        return response()->json($this->content);


    }


     public function find(Request $request, $id)
     {
       
        $this->content = Order::find($id);
        return response()->json($this->content);
     }


    public function post(Request $request)
    {
        $validation = Validator::make(
            $request->all(),
            [
                'order_hash'       => 'string',
                'device_id'        => 'numeric',
                'plan_id'          => 'numeric',
                'sim_id'           => 'numeric',
                'sim_num'          => 'numeric',
                'sim_type'         => 'string',
                'addon_id'         => 'numeric',
                'subscription_id'  => 'numeric',
                'porting_number'   => 'string',
                'area_code'        => 'string',
                'operating_system' => 'string',
                'imei_number'      => 'digits_between:14,16',
            ]
        );


        if ($validation->fails()) {
            return response()->json($validation->getMessageBag()->all());
        }

        $data = $request->all();
        //print_r($data);

        // check hash
        if(!isset($data['order_hash'])){
            //Create new row in order table
            $order = Order::create([
                'hash' => sha1(time()),
                'company_id' => \Request::get('company')->id
            ]);
        }else{
            $order = Order::where('hash', $data['order_hash'])->get();
            if(!count($order)){
                return response()->json(['error' => 'Invalid order_hash']);
            }
            $order = $order[0];
        }


        // check active_group_id
        if(!$order->active_group_id){
            $order_group = OrderGroup::create([
                'order_id' => $order->id
            ]);

            // update order.active_group_id
            $order->update([
                'active_group_id' => $order_group->id,
            ]);

        }else{
            $order_group = OrderGroup::find($order->active_group_id);
        }


        $og_params = [];
        if(isset($data['device_id'])){
            $og_params['device_id'] = $data['device_id'];
        }

        if(isset($data['plan_id'])){
            $og_params['plan_id'] = $data['plan_id'];
            // delete all rows in order_group_addon table associated with this order
            
            $_oga = OrderGroupAddon::where('order_group_id', $order_group->id)
            ->get();

            $planToAddon = PlanToAddon::wherePlanId($data['plan_id'])->get();

            $addon_ids = [];

            foreach ($planToAddon as $addon) {
                array_push($addon_ids, $addon->addon_id);
            }


            foreach($_oga as $__oga){
                if (!in_array($__oga->addon_id, $addon_ids)) {
                    $__oga->delete();
                }
            }
            
        }

        if(isset($data['sim_id'])){
            $sim_id = $data['sim_id'];
            if($sim_id == 0){
                $sim_id = null;
            }
            $og_params['sim_id'] = $sim_id;
        }

        if(isset($data['sim_num'])){
            $og_params['sim_num'] = $data['sim_num'];
        }

        // if(isset($data['subscription_id'])){
        //     $og_params['subscription_id'] = $data['subscription_id'];
        // }

        if(isset($data['sim_type'])){
            $og_params['sim_type'] = $data['sim_type'];
        }

        if(isset($data['porting_number'])){
            $og_params['porting_number'] = $data['porting_number'];
        }

        if(isset($data['area_code'])){
            $og_params['area_code'] = $data['area_code'];
        }

        if(isset($data['operating_system'])){
            $og_params['operating_system'] = $data['operating_system'];
        }

        if(isset($data['imei_number'])){
            $og_params['imei_number'] = $data['imei_number'];
        }

        $order_group->update($og_params);

        if(isset($data['addon_id'])){
            $oga = OrderGroupAddon::create([
                'addon_id' => $data['addon_id'],
                'order_group_id' => $order_group->id
            ]);

        }

        return response()->json(['id' => $order->id, 'order_hash' => $order->hash]);
        
        
    }

    public function remove_from_order(Request $request)
    {
        /*
        Delete the input order_group_id from database.  If it is set as the active group id, then set order.active_group_id=0
        */

        $hash = $request->input('order_hash');
        $order = Order::where('hash', $hash)->get();
        if(!count($order)){
            return $this->respondError('Invalid order_hash', 400);
        }
        $order = $order[0];

        $data = $request->all();
        $order_group_id = $data['order_group_id'];
        if(!isset($order_group_id)){
            return $this->respondError('Invalid order_group_id', 400);
        }

        $og = OrderGroup::find($order_group_id);
        if(!$og){
            return $this->respondError('Invalid order_group_id', 400);
        }


        //check if this ordergroup is associated with given order_hash
        if($og->order_id != $order->id){
            return $this->respondError('Given order_group_id is not associated with provided order hash', 400);
        }


        $og->delete();
        $order->update(['active_group_id' => 0]);
        return $this->respond(['details'=>'Deleted successfully'], 204);
    }


    public function get_company(Request $request)
    {
        $apiKey = $request->Authorization;
        // $businessVerification = Company::where('api_key',$apiKey)->first()->business_verification;
        return Company::where('api_key', $apiKey)->first();
    }

    
//    public function destroy($id){
//         Order::find($id)->delete();
//         //$orders = OrderController::find($id);
//         return redirect()->back()->withErrors('Successfully deleted!');
//     }
// }
}
