<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Model\Device;


class DeviceController extends Controller
{
	public function  __construct(){
		$this->content=array();
	}

	public function get(Request $request){
		//$this->content=array(array('id'=>1,'amount'=>100));
		$carrier_id = $request->input('carrier_id');
		if($carrier_id){
		  $this->content = Device::where('carrier_id', $carrier_id)->get();
		} else{
		    $this->content= Device::all();
		}
		return Response()->json($this->content);
	}

	public function find(Request $request, $id){
		$this->content = Device::find($id);
        return response()->json($this->content);
	}
}
