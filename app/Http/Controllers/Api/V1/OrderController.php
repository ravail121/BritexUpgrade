<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Models\Order;

//use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function __construct(){
        $this->content = array();
    }

    public function get(Request $request){
        $this->content = array(
            array('id'=>1, 'amount'=>100)
        );

        Order::where('id',1)->get();

        return response()->json($this->content);
    }

    
}
