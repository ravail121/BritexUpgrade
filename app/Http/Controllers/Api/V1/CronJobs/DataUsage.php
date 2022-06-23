<?php

namespace App\Http\Controllers\Api\V1\CronJobs;


use App\Model\CronLog;
use App\UsageData;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\Api\V1\Traits\CronLogTrait;

/**
 * Class ProcessController
 *
 * @package App\Http\Controllers\Api\V1\CronJobs
 */
class DataUsage extends BaseController
{
	use CronLogTrait;
	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
    public function check2(Request $request)
    {
        $usageData = UsageData::where('simnumber', $request->sim_card_num)->first();
        return $usageData;
    }

    public function getUsageData() {
		try {
			$offerIds = [
				317,
				318,
				319,
				320,
				321,
				325,
				327
			];

			foreach ( $offerIds as $offerId ) {
				$page = 1;
				while ( 1 ) {
					$response = $this->apiUsage( $offerId, $page );
					if ( ! $response->data ) {
						break;
					}
					$page ++;

					foreach ( $response->data as $value ) {
						$usageData = UsageData::where( 'simnumber', $value->simnumber )->first();
						if ( $usageData ) {
							UsageData::where( 'simnumber', $value->simnumber )->update( [
								'data'  => $value->data_usage,
								'voice' => $value->voice_usage,
								'sms'   => $value->sms_usage
							] );
						} else {
							UsageData::create( [
								'simnumber' => $value->simnumber,
								'data'      => $value->data_usage,
								'voice'     => $value->voice_usage,
								'sms'       => $value->sms_usage,
							] );
						}
						$logEntry = [
							'name'      => CronLog::TYPES['update-data-usage'],
							'status'    => 'success',
							'payload'   => '',
							'response'  => 'Data usage updated for sim number ' . $value->simnumber
						];
						$this->logCronEntries($logEntry);
					}
				}
			}
		} catch (\Exception $e) {
			$logEntry = [
				'name'      => 'Update Data Usage',
				'status'    => 'error',
				'payload'   => '',
				'response'  => $e->getMessage()
			];

			$this->logCronEntries($logEntry);
		}
    }

    public function apiUsage($offerid, $page){
        $ch = curl_init();
        $arr = [
			"offerid"   => $offerid,
			"limit"     => 500,
			"page"      => $page
        ];
        $url = "https://connect-api.ultramobile.com/v1/connect/getUsage";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);                //0 for a get request
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arr));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 400);
        curl_setopt($ch, CURLOPT_HTTPHEADER , array(
            'Authorization: Basic U0F6KnZlTU5PZm9tP2tDTDpsU0skdkdobmtlcEhyR0NjU3NhcipTQ2pMRUJOJmRXWg==',
            'Content-Type: application/json'
        ));
        $response = curl_exec($ch);
        curl_close ($ch);
        $b  = json_decode($response);
        return $b;
    }
}
