<?php

namespace App\Http\ViewComposer;

use App\Model\Order;
use App\Model\Company;
use Illuminate\View\View;
use App\Services\Cart\CartResponse;

class EmailComposer 
{    
    public function compose(View $view)
    {
        $orderHash  = request()->header('authorization');

        $company = Company::whereApiKey($orderHash)->first();

        $view->with('companyDetail', $company);
    }

}