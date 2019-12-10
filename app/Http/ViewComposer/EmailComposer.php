<?php

namespace App\Http\ViewComposer;

use App\Model\Company;
use Illuminate\View\View;
use App\Services\Cart\CartResponse;

class EmailComposer 
{    
    public function compose(View $view)
    {
        $company = \Request::get('company');
        if(! isset($company->id)){
            $apiKey  = request()->header('authorization');
            $company = Company::whereApiKey($apiKey)->first();
        }

        $view->with(
            [
                'companyDetail' => $company,
                'templates'     => request()->header('templates')
            ]
        );
    }

}