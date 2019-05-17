<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use App\Model\BusinessVerificationDocs;
use App\Http\Controllers\BaseController;

class TestingImageController extends BaseController
{ 

    public function __construct()
    {

        $this->content = array();

    }

    public function post(Request $request)
    {
        $data = $request->all();
        if($data['file']) {
            $uploadedAndInserted = $this->insertFile($data['file']);
            \Log::info('.............functio 1 testMethod.......');
            \Log::info($uploadedAndInserted);

            if (!$uploadedAndInserted) {
                return $this->respondError('File Could not be uploaded.');
            }
        }
        return response()->json(['test' => 'success']);
    }

     protected function insertFile($file)
    {
        $path = BusinessVerificationDocs::directoryLocation($file);
        \Log::info('.............functio 2 testMethod.......');
        \Log::info($path);
        if ($uploaded = $this->moveOneFile($path, $file)) {
            return response()->json(['test' => 'working']);
        }

        return false;
    }
}