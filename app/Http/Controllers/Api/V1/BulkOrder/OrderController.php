<?php

namespace App\Http\Controllers\Api\V1\BulkOrder;

use Validator;
use App\Model\Sim;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;
/**
 * Class OrderController
 *
 * @package App\Http\Controllers\Api\V1
 */
class OrderController extends BaseController
{
	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function listSims(Request $request)
	{
		try {
			$company = $request->get('company');

			$sims = Sim::whereCompanyId($company->id)->get();

			return $this->respond($sims);

		} catch(\Exception $e) {
			Log::error($e->getMessage());
			return $this->respondError($e->getMessage());
		}
	}

	public function createOrder(Request $request)
	{

	}
}