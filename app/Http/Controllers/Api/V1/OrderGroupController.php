<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Model\Order;
use App\Model\OrderGroup;


class OrderGroupController extends Controller
{
    public function __construct(){
        $this->content = array();
    }
    

 public function put(Request $request)
    {
        $data = $request->all();
        $hash = $data['order_hash'];
        $action = $data['action'];
        $order_group_id = $data['order_group_id'];

        $order = Order::where('hash', $hash)->get();
        if(!count($order)){
            return response()->json(array('error' => ['Invalid order hash']));
        }else{

            echo $order[0];
            if($action == 1){
                ##close active group
            }

            return response()->json('d');
        }

    }
    
}
