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
        $order = array(); //'order'=>array(), 'order_groups'=>array());
        
        if($hash){
            $order_groups = OrderGroup::with(['order', 'sim', 'device'])->whereHas('order', function($query) use ($hash) {
                                                $query->where('hash', $hash);

                        })->get();

            
            foreach($order_groups as $og){
                $order = $og->order;
                $tmp = array(
                    'sim' => $og->sim,
                    'device' => $og->device,
                    'plan' => $og->plan
                );
                $order['order_groups'] = $tmp;
                
                
            }

         }

        $this->content = $order;
        return response()->json($this->content);


    }


     public function find(Request $request, $id)
     {
       
        $this->content = Order::find($id);
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
