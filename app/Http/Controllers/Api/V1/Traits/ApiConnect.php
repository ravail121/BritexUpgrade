<?php

namespace App\Http\Controllers\Api\V1\Traits;

use Exception;
use App\Helpers\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

trait ApiConnect
{

	/**
	 * @return Client
	 */
	protected function getUltraMobileClientInstance($company)
	{
		$ultraApiKey = $company->ultra_api_key;
		$ultraApiSecret = $company->ultra_api_secret;
		$token = base64_encode("$ultraApiKey:$ultraApiSecret");

		$headers = [
			'Accept'        => 'application/json',
			'Authorization' =>  'Basic ' . $token
		];

		return new Client([
			'headers' => $headers,
		]);
	}

	/**
	 * Connect with Ultra Mobile Validation Endpoint
	 * @param null   $url
	 * @param string $type
	 * @param null   $parameters
	 * @param false  $rawResponse
	 *
	 * @return \Illuminate\Support\Collection|\Psr\Http\Message\StreamInterface|string
	 */
	public function requestUltraSimValidationConnection(
		$url=null,
		$type='get',
		$parameters=null,
		$rawResponse=false,
		$company)
	{
		$client = $this->getUltraMobileClientInstance($company);

		try {
			if ($type === 'get') {
				$response = $client->get(config('internal.__BRITEX_ULTRA_MOBILE_NUMBER_VALIDATION_API_BASE_URL') . $url, [
					'query'             => $parameters,
					'timeout'           => 10.0,
					'checkout_timeout'  => 2.0
				]);
			} elseif ($type === 'post') {
				$response = $client->post(config('internal.__BRITEX_ULTRA_MOBILE_NUMBER_VALIDATION_API_BASE_URL') . $url, [
					'form_params'       => $parameters,
					'timeout'           => 2.0,
					'checkout_timeout'  => 2.0
				]);
			}

			$status = $response->getStatusCode();

			if ($status === 200) {
				return $rawResponse ? $response->getBody() : json_decode($response->getBody(), true);
			}
			return false;
		} catch (Exception $e) {
			Log::info($e->getMessage(), 'ApiConnect Error: ');
			return false;
		}

	}
}