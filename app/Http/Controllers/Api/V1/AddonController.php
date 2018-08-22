<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Model\Addon;
use App\Model\PlanToAddon;

/**
 * 
 */
class AddonController extends Controller
{
	
	function __construct()
	{
		$this->content = array();
	}
  
   public function get(Request $request){

      
    $plan_id = $request->input('plan_id');
    $addon_to_plans = PlanToAddon::with(['plan', 'addon'])->whereHas('plan', function($query) use ($plan_id) {
                                                $query->where('plan_id', $plan_id);

                  })->get();

    foreach($addon_to_plans as $ap){
      array_push($this->content, $ap->addon);
    }
    
    return response()->json($this->content);


     }



    public function find(Request $request , $id){

      $this->content = Addon::find($id);
      return response()->json($this->content);
    }


}
