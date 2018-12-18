<?php


namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Support\Utilities\FileMoveTrait ;
use App\Support\Responses\APIResponse;


class BaseController extends Controller
{ 
    
    use FileMoveTrait;
    use APIResponse;

    public function __construct(){

    }


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

    public function validate_input($data, $rules)
    {

      $validation = Validator::make($data, $rules);
      if($validation->fails()){
        return $this->respondError($validation->getMessageBag()->all());
      }
      return false;

    }



}