<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    private $apiToken;
    public function __construct()
    {
    	$this->apiToken =uniqid(base64_encode(str_random(20)));
    }
    
    
   

}




