<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use Carbon\Carbon;
use Exception;
use App\Model\Order;
use GuzzleHttp\Client;
use App\Model\Subscription;
use App\Events\ShippingNumber;
use App\Model\CustomerStandaloneSim;
use App\Model\CustomerStandaloneDevice;
use App\Http\Controllers\BaseController;
use App\Events\SubcriptionStatusChanged;
use Illuminate\Database\Eloquent\Builder;

class OrderDataController extends BaseController
{
    public function order($orderID = null) {
        
        if($orderID){
            $orders = Order::where('id', '6368')->get();
        }else{
            $orders = Order::whereHas('subscriptions', function(Builder $subscription) {
                $subscription->where([['status', 'shipping'],['sent_to_readycloud', 1]])->whereNull('tracking_num');

            })->orWhereHas('standAloneDevices', function(Builder $standAloneDevice) {
                $standAloneDevice->where([['status', 'shipping'],['processed', 1]])->whereNull('tracking_num');        
                
            })->orWhereHas('standAloneSims', function(Builder $standAloneSim) {
                $standAloneSim->where([['status', 'shipping'],['processed', 1]])->whereNull('tracking_num');
            })->with('company')->take(30);
        }
        

        foreach ($orders as $order) {
            $readyCloudApiKey = $order->company->readycloud_api_key;
            $orderData = $this->getOrderData($order['order_num'], $readyCloudApiKey);
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
                        $this->updateOrderDetails($boxdetail, $boxes);
                    }
                }
            }
        }
        return $this->respond(['message' => 'Tracking Number Updated Sucessfully']); 
    }

    public function getOrderData($orderNum, $readyCloudApiKey)
    {
        try {
            $client = new Client();
            
            $response = $client->request('GET', env('READY_CLOUD_URL').$readyCloudApiKey.'&primary_id=BX-'.$orderNum);

            return collect(json_decode($response->getBody(), true));

        }catch (Exception $e) {
            \Log::error($e->getMessage());
        }
    }

    public function getOrderBoxesOrItemsData($boxesUrl, $readyCloudApiKey)
    {
        $client = new Client();
    try {
        $response = $client->request('GET', env('READY_CLOUD_BASE_URL').$boxesUrl.'?bearer_token='.$readyCloudApiKey);
        return collect(json_decode($response->getBody(), true));

    }catch (Exception $e) {
        \Log::error($e->getMessage());
    }
    return null;     
        
    }

    public function updateOrderDetails($boxdetail, $boxes)
    {
        if($boxes['tracking_number'] != null){
            $subString = substr($boxdetail['part_number'], 0, 3);
            $partNumId = subStr($boxdetail['part_number'], 4);

            if($subString == 'SUB') {
                $table = Subscription::whereId($partNumId)->with('customer', 'device', 'sim')->first();
                if($table){
                    $date = Carbon::today();
                    $table->update([
                        'status'       => 'for-activation',
                        'shipping_date'=> $date,
                        'tracking_num' => $boxes['tracking_number'],
                        'device_imei'  => $boxdetail['pick_location'],
                        'sim_card_num' => $boxdetail['code'],
                    ]);
                    event(new ShippingNumber($boxes['tracking_number'], $table));
                    event(new SubcriptionStatusChanged($table->id));
                }
            } elseif($subString == 'DEV') {
                $table = CustomerStandaloneDevice::whereId($partNumId)->with('device')->first();
                if($table){
                    $table->update([
                        'status'       => CustomerStandaloneDevice::STATUS['complete'],
                        'shipping_date'=> $date,
                        'tracking_num' => $boxes['tracking_number'],
                        'device_imei'  => $boxdetail['pick_location'],
                    ]);
                    event(new ShippingNumber($boxes['tracking_number'], $table));
                } 
            } elseif($subString == 'SIM') {
                $table = CustomerStandaloneSim::whereId($partNumId)->with('sim')->first();
                if($table){
                    $table->update([
                        'status'       => CustomerStandaloneSim::STATUS['complete'],
                        'shipping_date'=> $date,
                        'tracking_num' => $boxes['tracking_number'],
                        'sim_card_num' => $boxdetail['code'],
                    ]);
                    event(new ShippingNumber($boxes['tracking_number'], $table));
                }
            }
        }
    }
}
