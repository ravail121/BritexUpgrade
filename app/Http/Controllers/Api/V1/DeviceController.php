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
		$this->content= Device::all();
		Return Response()->json($this->content);
	}

	public function find(Request $request, $id){
		$this->content= Device::where('id',$id)->get()[0];
		return Response()->json($this->content);
	}
}
