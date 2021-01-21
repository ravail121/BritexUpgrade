<?php


return [

	/*
	|--------------------------------------------------------------------------
	| Default Filesystem Disk
	|--------------------------------------------------------------------------
	|
	| Here you may specify the default filesystem disk that should be used
	| by the framework. The "local" disk, as well as a variety of cloud
	| based disks are available to your application. Just store away!
	|
	*/

	'__BRITEX_ULTRA_API_BASE_URL'                   => env( 'ULTRA_API_BASE_URL', 'https://ultramobile-teltik.azurewebsites.net/api/'),

	/**
	 * Ready Cloud Base URL
	 */
	'__BRITEX_READY_CLOUD_BASE_URL'                 => env( 'READY_CLOUD_BASE_URL', 'https://www.readycloud.com' ),

	/**
	 * Ready Cloud URL
	 */
	'__BRITEX_READY_CLOUD_URL'                      => env( 'READY_CLOUD_URL', 'https://www.readycloud.com/api/v2/orgs/' ),


	/**
	 * Wait time in seconds
	 */
	'__BRITEX_READY_CLOUD_WAIT_TIME_IN_SECONDS'     => env('READY_CLOUD_WAIT_TIME_IN_SECONDS', 10),
];
