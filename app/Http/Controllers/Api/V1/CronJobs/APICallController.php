<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use App\Http\Controllers\Controller;
use App\Model\TelitUsageData;

/**
 * Class APICallController
 */
class APICallController extends Controller
{
	/**
	 * @return void
	 */
    public function callAuthentication(){
        $authResponse = $this->authentication();
        $authResponse = json_decode($authResponse);
        $session = $authResponse->auth->params->sessionId;
        $this->changeOrganization($session);
        $response = $this->getAllSims($session,null,1);
        $i=1;
        $data = [];
        while($i==1){
            $data[] = $response;
            if($response['end']){
                $i = 0;
            }else{
                $response = $this->getAllSims($session, $response['iterator'], 2);
            }
        }
        echo 'Done';
    }

	/**
	 * @return void
	 */
    public function getPlans(){
        $telitUsage = TelitUsageData::get();
        $authResponse = $this->authentication();
        $authResponse = json_decode($authResponse);
        $session = $authResponse->auth->params->sessionId;
        $this->changeOrganization($session);
        $check = 70;
        $i=1;
        foreach($telitUsage as $usage){
            if($i == $check){
                $check = $check+70;

                $authResponse = $this->authentication();
                $authResponse = json_decode($authResponse);
                $session = $authResponse->auth->params->sessionId;
                $this->changeOrganization($session);
            }
            $this->getAllPlans($session,$usage->iccid);
            $i++;
        }

        echo 'Done';
    }

	/**
	 * @return bool|string
	 */
    public function authentication(){
        
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => config('internal.__BRITEX_TELIT_API_BASE_URL'),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
        "auth": {
            "command": "api.authenticate",
            "params": {
                "username": "'.config('internal.__BRITEX_TELIT_API_USERNAME').'",
                "password": "'.config('internal.__BRITEX_TELIT_API_PASSWORD').'"
            }
        }
        }',
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

	/**
	 * @param $sessionId
	 *
	 * @return bool|string
	 */
    public function changeOrganization($sessionId){
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL             => config('internal.__BRITEX_TELIT_API_BASE_URL'),
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_ENCODING        => '',
        CURLOPT_MAXREDIRS       => 10,
        CURLOPT_TIMEOUT         => 0,
        CURLOPT_FOLLOWLOCATION  => true,
        CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST   => 'POST',
        CURLOPT_POSTFIELDS      =>'{
            "1": {
                "command": "session.org.switch",
                "params": {
                    "key": "TELTIK_COMMUNICATION"
                }
            }
        }',
        CURLOPT_HTTPHEADER => array(
            'sessionId: '.$sessionId,
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

	/**
	 * @param $sessionId
	 * @param $iterator1
	 * @param $ch
	 *
	 * @return array|void
	 */
    public function getAllSims($sessionId, $iterator1=null, $ch){
        $iterator = 'new';
        if(isset($iterator1)){
            $iterator = $iterator1;
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL             => config('internal.__BRITEX_TELIT_API_BASE_URL'),
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_ENCODING        => '',
        CURLOPT_MAXREDIRS       => 10,
        CURLOPT_TIMEOUT         => 0,
        CURLOPT_FOLLOWLOCATION  => true,
        CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST   => 'POST',
        CURLOPT_POSTFIELDS      =>'{
            "1": {
                "command": "cdp.connection.search",
                "params": {
                    "offset": 0,
                    "limit": 200,
                    "query": "apn:nxt20.net",
                    "useSearch": true,
                    "iterator": "'.$iterator.'"
                }
            }
        }',
        CURLOPT_HTTPHEADER => array(
            'sessionId: '.$sessionId,
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response1 = json_decode($response);
        foreach($response1 as $value){
            if(!$value->success){
                $data['end'] = true;
                return $data;
            }
            $data['iterator'] = $value->params->iterator;
            $data['count'] = $value->params->count;
          
                foreach($value->params->result as $result){
                if(!TelitUsageData::where('iccid',$result->iccid)->exists()){
                    $usage = new TelitUsageData();
                    $usage->iccid = $result->iccid;
                    $usage->carrier = $result->carrier;
                    $usage->status = $result->status;
                    $usage->date_activated = $result->dateActivated;
                    $usage->save();
                }
            }
            $data['end'] = false;
            return $data;
        }
    }

	/**
	 * @param $sessionId
	 * @param $iccid
	 *
	 * @return true
	 */
    public function getAllPlans($sessionId, $iccid){
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL             => config('internal.__BRITEX_TELIT_API_BASE_URL'),
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_ENCODING        => '',
        CURLOPT_MAXREDIRS       => 10,
        CURLOPT_TIMEOUT         => 0,
        CURLOPT_FOLLOWLOCATION  => true,
        CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST   => 'POST',
        CURLOPT_POSTFIELDS      => '{
            "1": {
                "command": "cdp.connection.find",
                "params": {
                    "iccid": "'.$iccid.'"

                }
            }
        }',
        CURLOPT_HTTPHEADER => array(
            'sessionId: '.$sessionId,
            'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response1 = json_decode($response);
        foreach($response1 as $value){
            if(!$value->success){
                return true;
            }
            $data = [];
            if(isset($value->params->usageMonthData)){
                $data['usage_data'] = $value->params->usageMonthData;
            }
            if($data){
                TelitUsageData::where('iccid', $iccid)->update($data);
            }
        }
        return true;
    }
}
