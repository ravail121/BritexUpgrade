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
      //if($request->header('Authorization')){
        if (true){
        //$company = Company::where('api_key',$request->header('Authorization'))->first();
        $company = Company::where('api_key','alar324r23423')->first();
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