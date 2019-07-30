<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use Exception;
use App\Model\Order;
use GuzzleHttp\Client;
use App\Model\Subscription;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\CustomerStandaloneSim;
use App\Model\CustomerStandaloneDevice;
use Illuminate\Database\Eloquent\Builder;

class OrderDataController extends Controller
{
    public function order() {
        
        $orders = Order::whereHas('subscriptions', function(Builder $subscription) {
            $subscription->where([['status', 'shipping'],['sent_to_readycloud', 1]])->orWhereNull('tracking_num');

        })->orWhereHas('standAloneDevices', function(Builder $standAloneDevice) {
            $standAloneDevice->where([['status', 'shipping'],['processed', 1]])->orWhereNull('tracking_num');        
        })->orWhereHas('standAloneSims', function(Builder $standAloneSim) {
            $standAloneSim->where([['status', 'shipping'],['processed', 1]])->orWhereNull('tracking_num');
        })->get();

        foreach ($orders as $order) {
            
           $orderData = $this->getOrderData($order['order_num']);
            if($orderData && isset($orderData['results'][0])){
                foreach ($orderData['results'][0]['boxes'] as $orderDataValue) {

                    $boxesUrl = $orderDataValue['url']; 

                    $boxes = $this->getOrderBoxesOrItemsData($boxesUrl);
                    if(!$boxes){
                         continue;
                    }
                    $boxdetail = $this->getOrderBoxesOrItemsData($boxes['items'][0]['url']);
                    if(!$boxdetail){
                        continue;
                    }

                    $this->updateOrderDetails($boxdetail, $boxes);
                }
            }
        }
        return $this->respond(['message' => 'Tracking Number Updated Sucessfully']); 
    }

    public function getOrderData($orderNum)
    {
        try {
            $client = new Client();
            
            $response = $client->request('GET', env('READY_CLOUD_URL').'&primary_id=BX-'.$orderNum);

            return collect(json_decode($response->getBody(), true));

        }catch (Exception $e) {
            \Log::error($e->getMessage());
        }
    }

    public function getOrderBoxesOrItemsData($boxesUrl)
    {
        $client = new Client();
    try {
        $response = $client->request('GET', 'https://www.readycloud.com'.$boxesUrl.'?bearer_token='.env('READY_CLOUD_TOKEN'));
        return collect(json_decode($response->getBody(), true));

    }catch (Exception $e) {
        \Log::error($e->getMessage());
    }
    return null;     
        
    }

    public function updateOrderDetails($boxdetail, $boxes)
    {
        $subString = substr($boxdetail['part_number'], 0, 3);

        $partNumId = subStr($boxdetail['part_number'], 4);

        if($subString == 'SUB') {
            $table = Subscription::find($partNumId);

        } elseif($subString == 'SIM') {
            $table = CustomerStandaloneDevice::find($partNumId);

        } elseif($subString == 'DEV') {
            $table = CustomerStandaloneSim::find($partNumId);
        }
        if($table){
            $table->update(['tracking_num' => $boxes['tracking_number']]);
        }
    }
}
