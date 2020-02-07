<?php

namespace App\Http\Modules;

use GuzzleHttp\Client;

class ReadyCloud {

	public static function getOrgUrl($readyCloudApiKey){

		$client = new Client();
	    $api_url = env('READY_CLOUD_URL') ;
	    $url = $api_url."?bearer_token=".$readyCloudApiKey;
	    $response = $client->request('GET', $url);
	    $url = json_decode($response->getBody())->results[0]->url;
	    return $url;
	}


}


?>