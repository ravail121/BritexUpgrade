<?php

namespace App\Http\Controllers\Api\V1;
use Validator;
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


    private function checkInvalidHash($order)
    {
        if(!count($order)){
            $this->output['message'] = 'Invalid order hash or no closed order groups found';
            return response()->json($this->output);
        }
        return null;
    }
    
}
