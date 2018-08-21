<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Model\Order;
use App\Model\OrderGroup;
//use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function __construct(){
        $this->content = array();
    }
    public function get(Request $request){


        $hash = $request->input('order_hash');
        $orders = array();
        
        $order_groups = OrderGroup::with(['order', 'sim', 'device'])->whereHas('order', function($query) use ($hash) {
                                       if($hash){
                                            $query->where('hash', $hash);
                                        }

                    })->get();

        foreach($order_groups as $og){
            $tmp = $og->order;
            $tmp['sim'] = $og->sim;
            $tmp['device'] = $og->device;
            $tmp['plan'] = $og->plan;
            array_push($orders, $tmp);
        }

        $this->content = $orders;
        return response()->json($this->content);


    }


     public function find(Request $request, $id)
     {
       
        $this->content = Order::where('id',$id)->get()[0];
        return response()->json($this->content);
     }

//  public function add(Request $request)
//     {


//         $post= new Order;
//         $post->all = $request->all();

//         $post->save();

//     }
    
//    public function destroy($id){
//         Order::find($id)->delete();
//         //$orders = OrderController::find($id);
//         return redirect()->back()->withErrors('Successfully deleted!');
//     }
// }
}
