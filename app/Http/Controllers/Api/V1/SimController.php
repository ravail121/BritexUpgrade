<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\model\Sim;

/**
 * 
 */
class SimController extends Controller
{
  
  function __construct()
  {
    $this->content = array();
  }
  
   public function get(Request $request)
   {
       // $this->content = array(
      //      array('id'=>1, 'amount'=>100)
      //  );
        $plan_id = $request->input('plan_id');
        if($plan_id){
          $this->content = Sim::where('plan_id', $plan_id)->get();
        }else{
           $this->content = Sim::all();
          }
        return response()->json($this->content);
    }

    public function find(request $request, $id)
    {
       $this->content = Sim::find($id);
       return response()->json($this->content);
    }
 }