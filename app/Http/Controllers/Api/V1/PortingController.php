<?php

namespace App\Http\Controllers\Api\V1;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Model\Order;
use App\Model\Plan;
use App\Model\OrderGroup;


class PortingController extends Controller
{
    public function __construct(){
        $this->content = array();
    }
    

 public function check(Request $request)
    /*
        Check porting
    */
    {

        $validation = Validator::make(
            $request->all(),
            [
                'order_hash' => 'required|string',
                'plan_id' => 'required|numeric'
            ]
        );
        if ($validation->fails()) {
            return response()->json($validation->getMessageBag()->all());
        }
        $data = $request->all();

        $hash = $data['order_hash'];
        $plan_id = $data['plan_id'];

        $order = Order::with(['OG'])->where('hash', $hash)->whereHas('OG', function($query) use ($plan_id) {
                                                $query->where('plan_id', $plan_id);

                        })->get();
        if(!count($order)){
            return response()->json(array('error' => ['Invalid order_hash or plan_id']));
        }else{
            $order = $order[0];
            $porting_number = $order->og->porting_number;
            $plan = Plan::find($order->og->plan_id);

            if($plan->signup_porting == 1 && $porting_number != ''){
                return response()->json(array('show_porting' => true, 'porting_number' => $porting_number));

            }else if($plan->signup_porting == 1 && $porting_number == ''){
                return response()->json(array('show_porting' => true));

            }else if($plan->signup_porting == 0 && $porting_number != ''){
                return response()->json(array('show_porting' => false, 'clear_porting_number' => true));
            
            }else if($plan->signup_porting == 0 && $porting_number == ''){
                return response()->json(array('show_porting' => false));
            }

            return response()->json([]);
        }

    }
    
}
