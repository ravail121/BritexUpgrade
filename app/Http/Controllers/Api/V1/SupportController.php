<?php

namespace App\Http\Controllers\Api\V1;

use App\Model\Support;
use App\Model\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Events\SupportEmail;

class SupportController extends Controller
{
	public function  __construct(){
		$this->content = array();
	}

    public function get(Request $request)
    {
		$categories = Category::all();

		$support = Support::all();

		$this->content['categories'] = $categories;

		$this->content['support'] = $support;   

		return response()->json($this->content); 	
		}
		
		public function sendEmail(Request $request)
		{
			$data = $request->all();
			$company    = \Request::get('company');
			$request->headers->set('authorization', $company->api_key);
			return event(new SupportEmail($data));
		}
}
