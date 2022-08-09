<?php

namespace App\Http\Middleware;

use Closure;

/**
 * The middleware to authenticate the request from ShippingEasy.
 *
 * @internal We can't use the middleware directly because the ShippingEasy library needs to be authenticated and since
 *           our system using multi tenants and we don't have company infomration during the callback,
 *           we can't use Shipping Easy Authentication mechanism.
 * @see https://shippingeasy.readme.io/docs/shipment-notification-callback
 */
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
		if (in_array($request->getMethod(), self::PARSED_METHODS) && $request->get('api_signature')) {
			return $next($request);
		}
		return response()->json([
			'message' => 'Invalid API Credentials.',
		]);
	}
}
