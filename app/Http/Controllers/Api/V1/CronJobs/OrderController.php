<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use Exception;
use App\Model\Tax;
use GuzzleHttp\Client;
use App\Model\Subscription;
use Illuminate\Http\Request;
use App\Model\CustomerStandaloneSim;
use App\Http\Controllers\Controller;
use App\Model\CustomerStandaloneDevice;
use App\Http\Controllers\BaseController;

class OrderController extends BaseController
{
    public function order()
    {
        $subscriptions = Subscription::with(['sim', 'device', 'plan', 'order', 'customerRelation'])->shipping()->get();
        try {
            foreach ($subscriptions as  $subscription) {
                if(($subscription->sim_id != 0) && ($subscription->device_id == 0))
                {       
                    if($subscription) {

                        return  $this->subscriptionWithSim($subscription);
                    }    
                }

                if(($subscription->device_id != 0) && ($subscription->sim_id == 0))
                {
                    if($subscription) {

                       return  $this->subscriptionWithDevice($subscription);    
                    }
                }

                if(($subscription->sim_id != 0) && ($subscription->device_id != 0))
                {
                    if($subscription) {

                       return  $this->subscriptionWithDeviceSim($subscription);
                    }        
                }
            }
            $this->standAloneDevice(); 
            $this->standAloneSim();

        } catch (Exception $e) {
            \Log::error($e->getMessage());
        }

        return $this->respond(['message' => 'Orders Shipped Successfully.']);  
    }

    public function subscriptionWithSim($subscription)
    {
        $order = $subscription->order;
        $simData['phone'] = $subscription->customerRelation['phone'];
        $simData['description']  = $subscription->sim_name.' '.'associated with'.' '. $subscription->plan['name'];
        $simData['part_number'] = 'SUB-'.$subscription->id;
        $simData['unit_amount'] = $subscription->sim['amount_w_plan'];
        if($subscription->order != '' && $subscription->sim != '') {                 
            $apiData = $this->data($order, $simData);
            $response = $this->SentToReadyCloud($apiData);

            if($response->getStatusCode() == 201) {

                Subscription::where('id', $subscription->id)->update(['sent_to_readycloud' => 1]);
            } else {

                return $this->respond(['message' => 'Something went wrong!']);
            }
        } else {
            \Log::info("----Order not present for This Subscription id {$subscription->id} associated with this Order id {$subscription->order_id}. Order Generation");
        }
    }

    public function subscriptionWithDevice($subscription)
    {
        $order = $subscription->order;
        $deviceData['phone'] = $subscription->customerRelation['phone'];
        $deviceData['description'] = $subscription->device['name'].' '.'associated with'.' '.$subscription->plan['name'];
        $deviceData['part_number'] = 'SUB-'.$subscription->id;
        $deviceData['unit_amount'] = $subscription->device['amount_w_plan'];
        if($subscription->order != '' && $subscription->device != '') {
            $apiData = $this->data($order, $deviceData);
            $response = $this->SentToReadyCloud($apiData);
            if($response->getStatusCode() == 201) {

                Subscription::where('id', $subscription->id)->update(['sent_to_readycloud' => 1]);
            } else {

                return $this->respond(['message' => 'Something went wrong!']);
            }    
        } else {
            \Log::info("----Order not present for This Subscription id {$subscription->id} associated with this Order id {$subscription->order_id}. Order Generation");
        }
    }

    public function subscriptionWithDeviceSim($subscription)
    {
        $order = $subscription->order;
        $simDeviceData[0]['phone'] = $subscription->customerRelation['phone'];
        $simDeviceData[0]['description']  = $subscription->sim_name.' '.'associated with'.' '. $subscription->plan['name'];
        $simDeviceData[0]['part_number'] = 'SUB-'.$subscription->id;
        $simDeviceData[0]['unit_amount'] = $subscription->sim['amount_w_plan'];

        $simDeviceData[1]['phone'] = $subscription->customerRelation['phone'];
        $simDeviceData[1]['description'] = $subscription->device['name'].' '.'associated with'.' '.$subscription->plan['name'];
        $simDeviceData[1]['part_number'] = 'SUB-'.$subscription->id;
        $simDeviceData[1]['unit_amount'] = $subscription->device['amount_w_plan'];

        if($subscription->order != '' && $subscription->device != '') {
            foreach ($simDeviceData as $simDevice) {
                $apiData = $this->data($order, $simDevice);
                $response = $this->SentToReadyCloud($apiData);    
            }
            
            if($response->getStatusCode() == 201) {

                Subscription::where('id', $subscription->id)->update(['sent_to_readycloud' => 1]);
            } else {

                return $this->respond(['message' => 'Something went wrong!']);
            }
        } else {
            \Log::info("----Order not present for This Subscription id {$subscription->id} associated with this Order id {$subscription->order_id}. Order Generation");
        }
    }

    public function standAloneDevice()
    {
        $standAloneDevices = CustomerStandaloneDevice::with(['device', 'order'])->shipping()->get();
        foreach ($standAloneDevices as $standAloneDevice) {

            if($standAloneDevice) {

                $order = $standAloneDevice->order; 
                $standAloneDeviceData['description'] = $standAloneDevice->device['name'];
                $standAloneDeviceData['part_number'] = 'DEV-'.$standAloneDevice->device['id'];
                $standAloneDeviceData['unit_amount'] = $standAloneDevice->device['amount'];
                $standAloneDeviceData['phone'] = $standAloneDevice->customer['phone'];
                if($standAloneDevice->order != '' && $standAloneDevice->device != '') {
                    $apiData = $this->data($order, $standAloneDeviceData);
                    $response = $this->SentToReadyCloud($apiData);
                    if($response->getStatusCode() == 201) {

                        CustomerStandaloneDevice::where('id', $standAloneDevice->id)->update(['processed' => 1]);
                    } else {

                            return $this->respond(['message' => 'Something went wrong!']);
                    }
                } else {
                    \Log::info("----Order not present for This standAloneDevice Id {$standAloneDevice->id} associated with this Order Id {$standAloneDevice->order_id}. Order Generation");
                }
            }
        }
            
    }

    public function standAloneSim()
    {
        $standAloneSims = CustomerStandaloneSim::with(['sim', 'order'])->shipping()->get();
        foreach ($standAloneSims as  $standAloneSim) {
            if($standAloneSim) {

                $order = $standAloneSim->order; 
                $standAloneSimData['description'] = $standAloneSim->sim['name'];
                $standAloneSimData['part_number'] = 'SIM‌-'.$standAloneSim->sim['id'];
                $standAloneSimData['unit_amount'] = $standAloneSim->sim['amount_alone'];
                $standAloneSimData['phone'] = $standAloneSim->customer['phone'];
                if($standAloneSim->order != '' && $standAloneSim->sim != '') {
                    $apiData = $this->data($order, $standAloneSimData);
                    $response = $this->SentToReadyCloud($apiData);
                    
                    if($response->getStatusCode() == 201) {

                        CustomerStandaloneSim::where('id', $standAloneSim->id)->update(['processed' => 1]);
                    } else {

                            return $this->respond(['message' => 'Something went wrong!']);
                    }
                } else {
                    \Log::info("----Order not present for This standAloneDevice Id {$standAloneDevice->id} associated with this Order Id {$standAloneDevice->order_id}. Order Generation");
                }
            }
        }    
    }

    public function data($order, $data)
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
                    "region" => $customer['billing_state_id'].' '."associated with".' '.$order->shipping_state_id,
                    "country" => "USA",
                    "phone" => $data['phone']
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
            "boxes" => [
                ["items" => [[
                    "description" => $data['description'],
                    "part_number" => $data['part_number'],
                    "quantity" => 1,
                    "unit_price" => $data['unit_amount'].' '.'USD'
                ]], 
                ],
            ],
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

    public function SentToReadyCloud($data)
    {
        $client = new Client();
        $response = $client->request('POST', env('REDY_CLOUD_URL'), [
            'headers' => ['Content-type' => 'application/json'],
            'body' => $data
        ]);

        return $response;
    }
}
