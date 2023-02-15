<?php


return [

	/**
	 * All the internal constants
	 */

	'__BRITEX_ULTRA_API_BASE_URL'                                           => env( 'ULTRA_API_BASE_URL', 'https://ultramobile-teltik.azurewebsites.net/api/'),

	/**
	 * Ready Cloud Base URL
	 */
	'__BRITEX_READY_CLOUD_BASE_URL'                                         => env( 'READY_CLOUD_BASE_URL', 'https://www.readycloud.com' ),

	/**
	 * Ready Cloud URL
	 */
	'__BRITEX_READY_CLOUD_URL'                                              => env( 'READY_CLOUD_URL', 'https://www.readycloud.com/api/v2/orgs/' ),


	/**
	 * Wait time in seconds
	 */
	'__BRITEX_READY_CLOUD_WAIT_TIME_IN_SECONDS'                             => env('READY_CLOUD_WAIT_TIME_IN_SECONDS', 10),


	/**
	 * Ultra Mobile Number Validation URL
	 */
	'__BRITEX_ULTRA_MOBILE_NUMBER_VALIDATION_API_BASE_URL'                  => env( 'ULTRA_MOBILE_NUMBER_VALIDATION_API_BASE_URL', 'https://connect-api.ultramobile.com/v1/'),

	/**
	 * TELIT API Base URL
	 */
	'__BRITEX_TELIT_API_BASE_URL'                                           => env( 'TELIT_API_BASE_URL', 'https://api.devicewise.com/api'),

	/**
	 * Telit API Username
	 */
	'__BRITEX_TELIT_API_USERNAME'                                           => env( 'TELIT_API_USERNAME', 'davidg@amcest.com'),

	/**
	 * Telit API Password
	 */
	'__BRITEX_TELIT_API_PASSWORD'                                           => env( 'TELIT_API_PASSWORD', 'Amcest321!'),
];
