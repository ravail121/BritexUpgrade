<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Model\Order;

//use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function __construct(){
        $this->content = array();
    }
    public function get(Request $request){
        // $this->content = array(
        //     array('id'=>1, 'amount'=>100)
        // );

        $this-> content = Order::all();

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
