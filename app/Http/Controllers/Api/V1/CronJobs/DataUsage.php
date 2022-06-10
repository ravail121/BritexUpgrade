<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use App\Http\Controllers\BaseController;
use App\Model\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Events\ReportNullSubscriptionStartDate;
use App\Events\SendMailData;
use App\Model\InvoiceItem;
use App\UsageData;

/**
 * Class ProcessController
 *
 * @package App\Http\Controllers\Api\V1\CronJobs
 */
class DataUsage extends BaseController
{
	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	


    public function check2(Request $request)
    {
        
      $usageData= UsageData::where('simnumber',$request->sim_card_num)->first();

      return $usageData;


    }

    public function getUsageData()
    {
        $offerIds=[317,318,319,320,321,325,327];

        foreach($offerIds as $value2){
          $page=1;
         // echo $value2;
          while(1){
            
            $response=$this->apiUsage($value2,$page);
          
           
            if(!$response->data){
              
              break;
    
            }
            $page++;
    
            foreach($response->data as $key=>$value){
    
              $usageData=UsageData::where('simnumber',$value->simnumber)->first();
               
              if($usageData){

                UsageData::where('simnumber',$value->simnumber)->update(['data'=>$value->data_usage,'voice'=>$value->voice_usage,'sms'=>$value->sms_usage]);

              }else{

                $invoice_item = UsageData::create([
    
                  'simnumber'=> $value->simnumber,
                  'data'    => $value->data_usage,
                  'voice'   => $value->voice_usage,
                  'sms'     => $value->sms_usage,
                ]);

              }
                
    
            }

          }
       
          
    }
        

    }

    public function apiUsage($offerid,$page){
        $ch = curl_init();
        $arr=array("offerid"=>$offerid,"limit"=>500,"page"=>$page);
        $url = "https://connect-api.ultramobile.com/v1/connect/getUsage";
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_POST, 1);                //0 for a get request
        curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($arr));
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT ,0);
        curl_setopt($ch,CURLOPT_TIMEOUT, 400);
        curl_setopt($ch,CURLOPT_HTTPHEADER , array(
          'Authorization: Basic U0F6KnZlTU5PZm9tP2tDTDpsU0skdkdobmtlcEhyR0NjU3NhcipTQ2pMRUJOJmRXWg==',
          'Content-Type: application/json'
        ));
        $response = curl_exec($ch);
        //print "curl response is:" . $response;
        curl_close ($ch);
        $b=json_decode($response);
        return $b;
    }
}
