<?php

namespace App\Http\Middleware;

use Closure;

use DB;
use App\Model\Company;

class APIToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

    public function handle($request, Closure $next)
    {
        $authorization='alar324r23423';
        // dd($authorization);
        if($authorization){
        $company = Company::where('api_key',$authorization)->first();
        //dd($company);
        if($company){
            $request->attributes->add(['company' => $company]);
            return $next($request);

        }

      }
      return response()->json([
        'message' => 'Invalid API Token.',
      ]);
    }
}
