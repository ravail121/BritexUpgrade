<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use Exception;
use App\Model\Tax;
use App\Model\Order;
use GuzzleHttp\Client;
use App\Model\Subscription;
use Illuminate\Http\Request;
use App\Model\CustomerStandaloneSim;
use App\Http\Controllers\Controller;
use App\Model\CustomerStandaloneDevice;
use App\Http\Controllers\BaseController;
use Illuminate\Database\Eloquent\Builder;

class OrderController extends BaseController
{
    public function order()
    {
        $orders = Order::where('status', '1')->with('subscriptions', 'standAloneDevices', 'standAloneSims')->whereHas('subscriptions', function(Builder $subscription) {
            $subscription->where([['status', 'shipping'],['sent_to_readycloud', 0 ]]);
        })->orWhereHas('standAloneDevices', function(Builder $standAloneDevice) {
            $standAloneDevice->where([['status', 'shipping'],['processed', 0 ]]);
        })->orWhereHas('standAloneSims', function(Builder $standAloneSim) {
            $standAloneSim->where([['status', 'shipping'],['processed', 0 ]]);
        })->with('company')->get();

        try {
            foreach ($orders as $orderKey => $order) {
                $readyCloudApiKey = $order->company->readycloud_api_key;
                $subscriptionRow = array();
                $standAloneDeviceRow = array();
                $standAloneSimRow = array();

                foreach ($order->subscriptions as $key => $subscription) {
                    // $subscriptionRow[$key]['items'] = $this->subscriptions($subscription);
                    $responseData = $this->subscriptions($subscription);
                    if($responseData){
                        $subscriptionRow[$key] = $responseData;
                    }
                }

                foreach ($order->standAloneDevices as $key => $standAloneDevice) {
                    // $standAloneDeviceRow[$key]['items'] = $this->standAloneDevice($standAloneDevice);
                    $standAloneDeviceRow[$key] = $this->standAloneDevice($standAloneDevice);
                }

                foreach ($order->standAloneSims as $key => $standAloneSim) {
                    // $standAloneSimRow[$key]['items'] = $this->standAloneSim($standAloneSim);
                    $standAloneSimRow[$key] = $this->standAloneSim($standAloneSim);
                }
                $row[0]['items'] = array_merge($subscriptionRow, $standAloneDeviceRow, $standAloneSimRow);
                    $apiData = $this->data($order, $row);

                    $response = $this->SentToReadyCloud($apiData, $readyCloudApiKey);
                    if($response->getStatusCode() == 201) {
                        $order->subscriptions()->update(['sent_to_readycloud' => 1]);
                        $order->standAloneDevices()->update(['processed' => 1]);
                        $order->standAloneSims()->update(['processed' => 1]);
                    } else {
                            return $this->respond(['message' => 'Something went wrong!']);
                    }
            }            
        }catch (Exception $e) {
            \Log::error($e->getMessage());
        }

        return $this->respond(['message' => 'Orders Shipped Successfully.']); 
    }

    public function subscriptions($subscription) 
    {
        if(($subscription->sim_id != 0) && ($subscription->device_id == 0)) { 
            return  $this->subscriptionWithSim($subscription);
        }

        if(($subscription->device_id != 0) && ($subscription->sim_id == 0)) {
            return  $this->subscriptionWithDevice($subscription);
        }

        if(($subscription->sim_id != 0) && ($subscription->device_id != 0)) {
            return $this->subscriptionWithSimAndDevice($subscription);
            // $simData = $this->subscriptionWithSim($subscription);
            // $deviceData = $this->subscriptionWithDevice($subscription);
            // return [$simData, $deviceData];
        }

    }

    public function subscriptionWithSim($subscription)
    {
        return [
            'description' => $subscription->sim_name.' '.'associated with'.' '. $subscription->plan['name'],
            'part_number' => 'SUB-'.$subscription->id,
            'unit_amount' => $subscription->sim['amount_w_plan'],
            'quantity'    =>   '1',
        ];
    }

    public function subscriptionWithDevice($subscription)
    {
        return [
            'description' => $subscription->device['name'].' '.'associated with'.' '.$subscription->plan['name'],
            'part_number' => 'SUB-'.$subscription->id,
            'unit_amount' => $subscription->device['amount_w_plan'],
            'quantity'    =>   '1',
        ];
    }

    public function subscriptionWithSimAndDevice($subscription)
    {
        return [
            'description' => $subscription->sim_name.' '.'associated with'.' '.$subscription->plan['name'].' and '.$subscription->device['name'],
            'part_number' => 'SUB-'.$subscription->id,
            'unit_amount' => $subscription->device['amount_w_plan'],
            'quantity'    =>   '1',
        ];
    }

    public function standAloneDevice($standAloneDevice)
    {
        return [
            'description' => $standAloneDevice->device['name'],
            'part_number' => 'DEV-'.$standAloneDevice->id,
            'unit_amount' => $standAloneDevice->device['amount'],
            'quantity'    =>   '1',];
    }

    public function standAloneSim($standAloneSim)
    {
        return [
            'description' => $standAloneSim->sim['name'],
            'part_number' => 'SIM‌-'.$standAloneSim->id,
            'unit_amount' => $standAloneSim->sim['amount_alone'],
            'quantity'    =>   '1',
        ];
    }

    public function data($order, $row)
    {
        
        $company = $order->company;
        $customer = $order->customer;

        $json = [
            "primary_id" => "BX-".$order->order_num,
            "ordered_at" => $order->created_at_format,
            "shipping" => [
                "ship_to"=> [
                    "first_name" => $order->shipping_fname,
                    "last_name" => $order->shipping_lname,
                    "address_1" => $order->shipping_address1,
                    "address_2" => $order->shipping_address2,
                    "city" => $order->shipping_city,
                    "post_code" => $order->shipping_zip,
                    "region" => $order->shipping_state_id,
                    "country" => "USA",
                    "phone" =>  $order->customer->phone,
                ], 
            "ship_from" => [
                    "company" => $company->name,
                    "address_1" => $company->address_line_1,
                    "address_2" => $company->address_line_2,
                    "city" => $company->city,
                    "post_code" => $company->zip,
                    "region" => $company->state,
                    "country" => "USA",
                    "phone" => $company->support_phone_number
                ],
                "ship_type" => "Priority Mail",
                "ship_via" => "Stamps.com"     
            ],
            "boxes" => $row,
            "source" => [
                "name" => "<value>"
            ],
            "message" => "<value>",
            "tags" => [
                "<value>",
                "<value>"
            ],
        ]; 

        return json_encode($json);
    }

    public function SentToReadyCloud($data, $readyCloudApiKey)
    {
        $client = new Client();
        $response = $client->request('POST', env('READY_CLOUD_URL').$readyCloudApiKey, [
            'headers' => ['Content-type' => 'application/json'],
            'body' => $data
        ]);

        return $response;
    }
}