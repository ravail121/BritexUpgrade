<?php

namespace App\Http\Controllers\Api\V1;

use App\Model\Addon;
use App\Model\PlanToAddon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

/**
 * Class AddonController
 *
 * @package App\Http\Controllers\Api\V1
 */
class AddonController extends Controller
{
	/**
	 * AddonController constructor.
	 */
	public function __construct()
	{
		$this->content = array();
	}

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function get(Request $request)
	{
		$plan_id = $request->input('plan_id');
		$addon_to_plans = PlanToAddon::with(['plan', 'addon'])->whereHas('plan', function($query) use ($plan_id) {
			$query->where('plan_id', $plan_id);
		})->get();

		foreach($addon_to_plans as $ap){
			array_push($this->content, $ap->addon);
		}

		return response()->json($this->content);
	}

	/**
	 * @param Request $request
	 * @param         $id
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function find(Request $request, $id)
	{
		$this->content = Addon::find($id);
		return response()->json($this->content);
	}
}
