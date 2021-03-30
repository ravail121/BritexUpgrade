<?php

namespace App\Http\Controllers;

use Validator;
use App\Support\Utilities\FileMoveTrait;
use App\Support\Responses\APIResponse;

/**
 * Class BaseController
 *
 * @package App\Http\Controllers
 */
class BaseController extends Controller
{

	use FileMoveTrait, APIResponse;

	/**
	 * Return generic json response with the given data.
	 *
	 * @param $data
	 * @param int $statusCode
	 * @param array $headers
	 * @return \Illuminate\Http\JsonResponse
	 */
	protected function respond($data, $statusCode = 200, $headers = [])
	{
		return response()->json($data, $statusCode, $headers);
	}

	/**
	 * Respond with error.
	 *
	 * @param $message
	 * @param $statusCode
	 * @return \Illuminate\Http\JsonResponse
	 */
	protected function respondError($message, $statusCode=400)
	{
		return $this->respond([
			'details' => $message
		], $statusCode);
	}

	/**
	 * @param $data
	 * @param $rules
	 *
	 * @return false|\Illuminate\Http\JsonResponse
	 */
	public function validate_input($data, $rules)
	{

		$validation = Validator::make($data, $rules);
		if($validation->fails()){
			return $this->respondError($validation->getMessageBag()->all());
		}
		return false;

	}
}