<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Model\Plan;

/**
 * 
 */
class PlanController extends Controller
{
	
	function __construct()
	{
		$this->content = array();
	}
  
   public function get(Request $request){
       // $this->content = array(
      //      array('id'=>1, 'amount'=>100)
      //  );

       
       $device_id = $request->input('device_id');
       if($device_id){
        $this->content = Plan::where('device_id', $device_id)->get();
       }else{
            $this->content = Plan::all();
        }
        return response()->json($this->content);
        
	}

	 public function find(Request $request, $id){
        $this->content = Plan::find($id);
        return response()->json($this->content);
    }


}