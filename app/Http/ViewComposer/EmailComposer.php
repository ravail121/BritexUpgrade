<?php

namespace App\Http\ViewComposer;

use App\Model\Company;
use Illuminate\View\View;
use App\Services\Cart\CartResponse;

class EmailComposer 
{    
    public function compose(View $view)
    {
        $apiKey  = request()->header('authorization');
        \Log::info($apiKey);
        \Log::info('Test');

        $company = Company::whereApiKey($apiKey)->first();
        \Log::info($company);

        $view->with(
            [
                'companyDetail' => $company,
                'templates'     => request()->header('templates')
            ]
        );
    }

}