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
		$this->content=array(array('id'=>1,'amount'=>100));
		Device::where('id',1)->get();
		Return Response()->json($this content);
	}
}