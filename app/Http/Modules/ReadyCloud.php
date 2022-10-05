<?php

namespace App\Http\Modules;

use GuzzleHttp\Client;

/**
 * Class ReadyCloud
 *
 * @package App\Http\Modules
 */
class ReadyCloud
{
	/**
	 * @param $readyCloudApiKey
	 *
	 * @return mixed
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public static function getOrgUrl($readyCloudApiKey)
	{
		$client = new Client();
	    $api_url = config('internal.__BRITEX_READY_CLOUD_URL');
	    $url = $api_url."?bearer_token=".$readyCloudApiKey;
	    $response = $client->request('GET', $url);
	    $url = json_decode($response->getBody())->results[0]->url;
	    return $url;
	}
}