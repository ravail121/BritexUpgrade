<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Support\Validation\UsaEpayTransaction;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\libs\Constants\ConstantInterface;

class Controller extends BaseController implements ConstantInterface
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, UsaEpayTransaction;
}
