<?php

namespace App\Http\Controllers\Api\V1;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Model\Order;
use App\Model\OrderGroup;
use App\Model\OrderGroupAddon;
//use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
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
            $order_groups = OrderGroup::with(['order', 'sim', 'device'])->whereHas('order', function($query) use ($hash) {
                                                $query->where('hash', $hash);

                        })->get();

            //print_r($order_groups);
            
            foreach($order_groups as $og){

                if($order == []){
                    
                    $order =  [
                        "id" => $og->order->id,
                        "active_group_id"=> $og->order->active_group_id,
                        "active_subscription_id"=> $og->order->active_subscription_id,
                        "order_num"=> $og->order->order_num,
                        "status"=> $og->order->status,
                        "customer_id"=> $og->order->customer_id,
                        'order_groups'=> []
                    ];
                }
               

                
                $tmp = array(
                        'sim' => $og->sim,
                        'sim_num' => $og->sim_num,
                        'sim_type' => $og->sim_type,
                        'device' => $og->device,
                        'plan' => $og->plan,
                        'addons' => [],
                        'porting_number' => $og->porting_number,
                        'area_code' => $og->area_code
                    );

                $_addons = OrderGroupAddon::with(['addon'])->where('order_group_id', $og->id )->get();
                foreach ($_addons as $a) {
                    array_push($tmp['addons'], $a['addon']);
                }

                array_push($ordergroups, $tmp);

                
                
            }

         }

        $order['order_groups'] = $ordergroups;
        $this->content = $order;
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
                'order_hash' => 'string',
                'device_id' => 'numeric',
                'plan_id' => 'numeric',
                'sim_id' => 'numeric',
                'sim_num' => 'numeric',
                'sim_type' => 'string',
                'addon_id' => 'numeric',
                'subscription_id' => 'numeric',
                'porting_number' => 'string',
                'area_code' => 'string'
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
            $_oga = OrderGroupAddon::where('order_group_id',$order_group->id)
            ->get();

            foreach($_oga as $__oga){
                $__oga->delete();
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

        if(isset($data['sim_type'])){
            $og_params['sim_type'] = $data['sim_type'];
        }

        if(isset($data['porting_number'])){
            $og_params['porting_number'] = $data['porting_number'];
        }

        if(isset($data['area_code'])){
            $og_params['area_code'] = $data['area_code'];
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
    
//    public function destroy($id){
//         Order::find($id)->delete();
//         //$orders = OrderController::find($id);
//         return redirect()->back()->withErrors('Successfully deleted!');
//     }
// }
}
