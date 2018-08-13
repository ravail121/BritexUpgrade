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

        $this->content = Sim::all();

        return response()->json($this->content);
    }

    public function find(request $request, $id)
    {
       $this->content = Sim::where('id',$id)->get()[0];
       return response()->json($this->request);
    }
 }