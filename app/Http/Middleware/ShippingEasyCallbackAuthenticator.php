<?php

namespace App\Http\Middleware;

use Closure;
use ShippingEasy;
use ShippingEasy_Authenticator;

class ShippingEasyCallbackAuthenticator
{

	const PARSED_METHODS = [
		'POST'
	];
	/**
	 * @param         $request
	 * @param Closure $next
	 *
	 * @return mixed|void
	 */
	public function handle($request, Closure $next)
	{
//		$json = file_get_contents('php://input');
//		$json_payload = json_decode($json);
//		$authenticator = new ShippingEasy_Authenticator("post", "/api/shipment/callback", $request->all(), $json);
//		if ($authenticator->isAuthenticated()) {
//			return $next($request);
//		}
		if (in_array($request->getMethod(), self::PARSED_METHODS) && $request->get('api_signature')) {
			return $next($request);
		}
		return response()->json([
			'message' => 'Invalid API Credentials.',
		]);
	}
}
