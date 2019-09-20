<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use Exception;
use App\Model\Tax;
use App\Model\Order;
use GuzzleHttp\Client;
use App\Model\Invoice;
use App\Model\Subscription;
use Illuminate\Http\Request;
use App\Model\CustomerStandaloneSim;
use App\Http\Controllers\Controller;
use App\Model\CustomerStandaloneDevice;
use App\Http\Controllers\BaseController;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\Api\V1\Invoice\InvoiceController;

class OrderController extends BaseController
{
    public function order()
    {
        $orders = Order::where('status', '1')->with('subscriptions', 'standAloneDevices', 'standAloneSims', 'customer', 'invoice.invoiceItem')->whereHas('subscriptions', function(Builder $subscription) {
            $subscription->where([['status', 'shipping'],['sent_to_readycloud', 0 ]]);
        })->orWhereHas('standAloneDevices', function(Builder $standAloneDevice) {
            $standAloneDevice->where([['status', 'shipping'],['processed', 0 ]]);
        })->orWhereHas('standAloneSims', function(Builder $standAloneSim) {
            $standAloneSim->where([['status', 'shipping'],['processed', 0 ]]);
        })->with('company')->get();

        // $orders = Order::where('id', '7346')->get();

        try {
            foreach ($orders as $orderKey => $order) {
                $readyCloudApiKey = $order->company->readycloud_api_key;
                $subscriptionRow = array();
                $standAloneDeviceRow = array();
                $standAloneSimRow = array();

                foreach ($order->subscriptions as $key => $subscription) {
                    // $subscriptionRow[$key]['items'] = $this->subscriptions($subscription);
                    $responseData = $this->subscriptions($subscription, $order->invoice->invoiceItem);
                    if($responseData){
                        $subscriptionRow[$key] = $responseData;
                    }
                }

                foreach ($order->standAloneDevices as $key => $standAloneDevice) {
                    // $standAloneDeviceRow[$key]['items'] = $this->standAloneDevice($standAloneDevice);
                    $standAloneDeviceRow[$key] = $this->standAloneDevice($standAloneDevice, $order->invoice->invoiceItem);
                }

                foreach ($order->standAloneSims as $key => $standAloneSim) {
                    // $standAloneSimRow[$key]['items'] = $this->standAloneSim($standAloneSim);
                    $standAloneSimRow[$key] = $this->standAloneSim($standAloneSim, $order->invoice->invoiceItem);
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

    public function subscriptions($subscription, $invoiceItem) 
    {
        if(($subscription->sim_id != 0) && ($subscription->device_id == 0)) { 
            return  $this->subscriptionWithSim($subscription, $invoiceItem);
        }

        if(($subscription->device_id != 0) && ($subscription->sim_id == 0)) {
            return  $this->subscriptionWithDevice($subscription, $invoiceItem);
        }

        if(($subscription->sim_id != 0) && ($subscription->device_id != 0)) {
            return $this->subscriptionWithSimAndDevice($subscription, $invoiceItem);
            // $simData = $this->subscriptionWithSim($subscription);
            // $deviceData = $this->subscriptionWithDevice($subscription);
            // return [$simData, $deviceData];
        }
    }

    public function subscriptionWithSim($subscription, $invoiceItem)
    {

        $amount = $invoiceItem->where(
            'subscription_id', $subscription->id)
        ->where(
            'product_type', InvoiceController::SIM_TYPE 
        )->where(
            'product_id',  $subscription->sim_id
        )->sum('amount');

        return [
            'description' => $subscription->sim_name.' '.'associated with'.' '. $subscription->plan['name'],
            'part_number' => 'SUB-'.$subscription->id,
            'unit_price' => $amount.' USD',
            'quantity'    =>   '1',
        ];
    }

    public function subscriptionWithDevice($subscription, $invoiceItem)
    {
        $amount = $invoiceItem->where(
            'subscription_id', $subscription->id)
        ->where(
            'product_type', InvoiceController::DEVICE_TYPE 
        )->where(
            'product_id',  $subscription->device_id
        )->sum('amount');

        return [
            'description' => $subscription->device['name'].' '.'associated with'.' '.$subscription->plan['name'],
            'part_number' => 'SUB-'.$subscription->id,
            'unit_price' => $amount.' USD',
            'quantity'    =>   '1',
        ];
    }

    public function subscriptionWithSimAndDevice($subscription, $invoiceItem)
    {
        $amount = $invoiceItem->where('subscription_id', $subscription->id)->whereIn('product_type', [InvoiceController::DEVICE_TYPE, InvoiceController::SIM_TYPE])->sum('amount');

        return [
            'description' => $subscription->sim_name.' '.'associated with'.' '.$subscription->plan['name'].' and '.$subscription->device['name'],
            'part_number' => 'SUB-'.$subscription->id,
            'unit_price' => $amount.' USD',
            'quantity'    =>   '1',
        ];
    }

    public function standAloneDevice($standAloneDevice, $invoiceItem)
    {
        $invoiceItemAmount = $invoiceItem->where(
            'product_type', InvoiceController::DEVICE_TYPE 
        )->where(
            'product_id',  $standAloneDevice->device_id
        );

        foreach ($invoiceItemAmount->toArray() as $key => $value) {
            $amount = $value['amount'];
            break;
        }
        return [
            'description' => $standAloneDevice->device['name'],
            'part_number' => 'DEV-'.$standAloneDevice->id,
            'unit_price' => $amount.' USD',
            'quantity'    =>   '1',
        ];
    }

    public function standAloneSim($standAloneSim, $invoiceItem)
    {
        $invoiceItemAmount = $invoiceItem->where(
            'product_type', InvoiceController::SIM_TYPE 
        )->where(
            'product_id',  $standAloneSim->sim_id
        );

        foreach ($invoiceItemAmount->toArray() as $key => $value) {
            $amount = $value['amount'];
            break;
        }
        return [
            'description' => $standAloneSim->sim['name'],
            'part_numbe0r' => 'SIM‌-'.$standAloneSim->id,
            'unit_price' => $amount.' USD',
            'quantity'    =>   '1',
        ];
    }

    public function data($order, $row)
    {
        $taxes = $order->invoice->invoiceItem->where('type', Invoice::InvoiceItemTypes['taxes'])->sum('amount');

        $shippingAmount = $order->invoice->invoiceItem->where('description', InvoiceController::SHIPPING)->sum('amount');

        // $totalAmount = $order->invoice->invoiceItem
        // ->whereIn('product_type',[InvoiceController::DEVICE_TYPE,InvoiceController::SIM_TYPE])
        // ->sum('amount');

        // $totalAmount += $taxes;
        $company = $order->company;
        $customer = $order->customer;

        $json = [
            "primary_id" => "BX-".$order->order_num,
            "ordered_at" => $order->created_at_format,
            "billing" => [
                // "subtotal" => " USD",
                "shipping" => $shippingAmount." USD",
                "tax" => $taxes." USD",
                "total" => $order->invoice->subtotal." USD",
            ],
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
                    'email' => $customer->email,
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