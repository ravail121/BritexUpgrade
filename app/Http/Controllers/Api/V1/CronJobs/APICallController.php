<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use App\Helpers\Log;
use App\Http\Controllers\Controller;
use App\TelitUsageData;
use Illuminate\Http\Request;

class APICallController extends Controller
{
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
                $i=0;
            }else{
                $response = $this->getAllSims($session,$response['iterator'],2);
            }
        }
        // dd($data);
     //   $response = $this->getAllSims($session,$response['iterator'],2);
        // dd($response);
        echo 'Done';
    }
    public function getPlans(){
        ini_set('max_execution_time', 18000);
        $telitUsage = TelitUsageData::get();
        $authResponse = $this->authentication();
        $authResponse = json_decode($authResponse);
        $session = $authResponse->auth->params->sessionId;
        $this->changeOrganization($session);
        $check = 70;
        $i=1;
        foreach($telitUsage as $usage){
            // Log::info('Get Plans: '.$i.' SessionId: '.$session);
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


        // $authResponse = $this->authentication();
        // $authResponse = json_decode($authResponse);
        // $session = $authResponse->auth->params->sessionId;
        // $this->changeOrganization($session);
        // $telitUsage = TelitUsageData::skip(175)->take(25)->get();
        // foreach($telitUsage as $usage){
        //     $this->getAllPlans($session,$usage->iccid);
        // }
        echo 'Done';
    }
    public function authentication(){
        
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.devicewise.com/api',
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
                "username": "davidg@amcest.com",
                "password": "Amcest321!"
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
    public function changeOrganization($sessionId){
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.devicewise.com/api',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
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
    public function getAllSims($sessionId,$iterator1=null,$ch){
        // Log::info('Start getting sims');
        $iterator = 'new';
        if(isset($iterator1)){
            $iterator = $iterator1;
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.devicewise.com/api',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
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
        foreach($response1 as $key=>$value){
            if(!$value->success){
                $data['end'] = true;
                // Log::info('No Sim Found Exiting getting sims');
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
                    $usage->dateActivated = $result->dateActivated;
                    $usage->save();
                }
            }

            
            
            $data['end'] = false;
            // Log::info('Exiting getting sims');
            return $data;
        }

    }
    public function getAllPlans($sessionId,$iccid){
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.devicewise.com/api',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
            "1": {
                "command": "cdp.connection.find",
                "params": {
                    "iccid": "'.$iccid.'"

                }
            }
        }
        ',
        CURLOPT_HTTPHEADER => array(
            'sessionId: '.$sessionId,
            'Content-Type: application/json'
        ),
        ));

        $response = curl_exec($curl);
        // Log::info($response);

        curl_close($curl);
        $response1 = json_decode($response);
        foreach($response1 as $key=>$value){
            if(!$value->success){
                return true;
            }
            $data = [];
            if(isset($value->params->usageData)){
                $data['usageData'] = $value->params->usageData->last30;
            }
            if(isset($value->params->usageSms)){
                $data['usageSms'] = $value->params->usageSms->last30;
            }
            if(isset($value->params->usageVoice)){
                $data['usageVoice'] = $value->params->usageVoice->last30;
            }
            TelitUsageData::where('iccid',$iccid)->update($data);
        }
        return true;
    }
}
