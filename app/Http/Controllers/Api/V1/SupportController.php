<?php

namespace App\Http\Controllers\Api\V1;

use App\Model\Support;
use App\Model\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Events\SupportEmail;

/**
 * Class SupportController
 *
 * @package App\Http\Controllers\Api\V1
 */
class SupportController extends Controller
{
	/**
	 * SupportController constructor.
	 */
	public function  __construct(){
		$this->content = array();
	}

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function get(Request $request)
    {
		$company    = \Request::get('company');
		$categories = Category::where('company_id', $company->id)->get();
		$categoriesId = $categories->pluck('id');
		$support = Support::whereIn('category_id', $categoriesId)->get();

		$this->content['categories'] = $categories;
		$this->content['support'] = $support;   

		return response()->json($this->content); 	
	}

	/**
	 * @param Request $request
	 *
	 * @return array|null
	 */
	public function sendEmail(Request $request)
	{
		$data = $request->all();
		$company    = \Request::get('company');
		$request->headers->set('authorization', $company->api_key);
		return event(new SupportEmail($data));
	}
}
