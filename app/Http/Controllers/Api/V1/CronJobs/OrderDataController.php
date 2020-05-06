<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use Carbon\Carbon;
use Exception;
use App\Model\Order;
use GuzzleHttp\Client;

use App\Http\Modules\ReadyCloud;

use App\Model\Subscription;
use Illuminate\Http\Request;
use App\Events\ShippingNumber;
use App\Model\CustomerStandaloneSim;
use App\Model\CustomerStandaloneDevice;
use App\Http\Controllers\BaseController;
use App\Events\SubcriptionStatusChanged;
use Illuminate\Database\Eloquent\Builder;

class OrderDataController extends BaseController
{
    public function order($orderID = null, Request $request) {
        
        if($orderID){
            $orders = Order::where('id', $orderID)->get();
        }else{
            $orders = Order::whereHas('subscriptions', function(Builder $subscription) {
                $subscription->where([['status', 'shipping'],['sent_to_readycloud', 1]])->whereNull('tracking_num');

            })->whereHas('company', function(Builder $company){
                $company->whereNotNull('readycloud_api_key');

            })->orWhereHas('standAloneDevices', function(Builder $standAloneDevice) {
                $standAloneDevice->where([['status', 'shipping'],['processed', 1]])->whereNull('tracking_num');        
                
            })->orWhereHas('standAloneSims', function(Builder $standAloneSim) {
                $standAloneSim->where([['status', 'shipping'],['processed', 1]])->whereNull('tracking_num');
            })->with('company')->orderBy('order_num', 'desc')->take(25)->get();
        }
        
        \Log::info(array("Readycloud Tracking Fetch...", $orders->count()));

        $keyUrls=[];
        foreach ($orders as $order) {
            $readyCloudApiKey = $order->company->readycloud_api_key;
            
            if(array_key_exists($readyCloudApiKey, $keyUrls)){
                $url = $keyUrls[$readyCloudApiKey];
            }else{
                $url = ReadyCloud::getOrgUrl($readyCloudApiKey);
                $keyUrls[$readyCloudApiKey] = $url;
            }
            \Log::info($readyCloudApiKey."--".$order["order_num"]);
            if($readyCloudApiKey ){
                \Log::info("Getting readycloud data for : ".$order["order_num"]);
                $orderData = $this->getOrderData($order['order_num'], $readyCloudApiKey, $url);
                if($orderData){
                    try{
                        if($orderData && isset($orderData['results'][0])){
                            foreach ($orderData['results'][0]['boxes'] as $orderDataValue) {
                                $boxesUrl = $orderDataValue['url'];
                                $boxes = $this->getOrderBoxesOrItemsData($boxesUrl, $readyCloudApiKey);
                                if(!$boxes){
                                     continue;
                                }
                                foreach ($boxes['items'] as $key => $box) {
                                    $boxdetail = $this->getOrderBoxesOrItemsData($box['url'], $readyCloudApiKey);
                                    if(!$boxdetail){
                                        continue;
                                    }
                                    $this->updateOrderDetails($boxdetail, $boxes, $request);
                                }
                            }
                        }
                        \Log::info("done");
                    }catch (Exception $e) {
                        $msg = 'exception: '.$e->getMessage();
                        \Log::info($msg);
                        continue;

                    }

                }
            }
            usleep(config('readyCloud.ready_cloud_wait_time_in_seconds')*1000000);
        }
        return $this->respond(['message' => 'Tracking Number Updated Sucessfully']); 
    }

    public function getOrderData($orderNum, $readyCloudApiKey, $url = "/")
    {
        try {
            $url = config('readyCloud.ready_cloud_base_url').$url."orders/?bearer_token=".$readyCloudApiKey.'&primary_id=BX-'.$orderNum;
            //\Log::info($url);
            $client = new Client();
            $response = $client->request('GET', $url);
            return collect(json_decode($response->getBody(), true));

        }catch (Exception $e) {
            $msg = 'ReadyCloud exception: '.$e->getMessage();
            \Log::info($msg);
            return false;

        }
    }

    public function getOrderBoxesOrItemsData($boxesUrl, $readyCloudApiKey)
    {
        $client = new Client();
        try {
            $url = config('readyCloud.ready_cloud_base_url').$boxesUrl.'?bearer_token='.$readyCloudApiKey;
            \Log::info($url);
            $response = $client->request('GET', $url);
            return collect(json_decode($response->getBody(), true));

        }catch (Exception $e) {
            \Log::error($e->getMessage());
        }
        return null;     
        
    }

    public function updateOrderDetails($boxdetail, $boxes, $request)
    {
        if($boxes['tracking_number'] != null){
            $subString = substr($boxdetail['part_number'], 0, 3);
            $partNumId = subStr($boxdetail['part_number'], 4);
            $date = Carbon::today();

            $pick_location = $boxdetail['pick_location'];
            if($subString == 'SUB') {
                $table = Subscription::whereId($partNumId)->with('customer.company', 'device', 'sim')->first();
                if($table){
                    $table_data = [
                        'status'       => 'for-activation',
                        'shipping_date'=> $date,
                        'tracking_num' => $boxes['tracking_number'],
                        'sim_card_num' => $boxdetail['code'],
                    ];
                    if (strlen($pick_location) > 0){
                        $table_data['device_imei'] = $pick_location;
                    }
                    $table->update($table_data);
                    $table['customer'] = $table->customerRelation;

                    $request->headers->set('authorization', $table->customerRelation->company->api_key);
                    event(new ShippingNumber($boxes['tracking_number'], $table));
                    event(new SubcriptionStatusChanged($table->id));
                }
            } elseif($subString == 'DEV') {
                $table = CustomerStandaloneDevice::whereId($partNumId)->with('device', 'customer.company')->first();
                if($table){
                    $table_data = [
                        'status'       => CustomerStandaloneDevice::STATUS['complete'],
                        'shipping_date'=> $date,
                        'tracking_num' => $boxes['tracking_number']
                    ];
                    if (strlen($pick_location) > 0){
                        $table_data['device_imei'] = $pick_location;
                    }
                    $table->update($table_data);
                    $request->headers->set('authorization', $table->customer->company->api_key);
                    event(new ShippingNumber($boxes['tracking_number'], $table));
                } 
            } elseif($subString == 'SIM') {
                $table = CustomerStandaloneSim::whereId($partNumId)->with('sim', 'customer.company')->first();
                if($table){
                    $table->update([
                        'status'       => CustomerStandaloneSim::STATUS['complete'],
                        'shipping_date'=> $date,
                        'tracking_num' => $boxes['tracking_number'],
                        'sim_card_num' => $boxdetail['code'],
                    ]);
                    $request->headers->set('authorization', $table->customer->company->api_key);
                    event(new ShippingNumber($boxes['tracking_number'], $table));
                }
            }
        }
    }
}
