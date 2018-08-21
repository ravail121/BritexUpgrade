<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\model\Addon;

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
       // $this->content = array(
      //      array('id'=>1, 'amount'=>100)
      //  );

       $this->content= Addon::all();

        return response()->json($this->content);
     }



    public function find(Request $request , $id){

      $this->content = Addon::find($id);
      return response()->json($this->content);
    }


}
