<?php
namespace App\Services;

use GuzzleHttp\Client;

class Eye4Fraud {

    public static function prepare_data($order, $creditCard, $paymentLog){
        $customer = $order->customer;
        $invoice = $order->invoice;
        $order_groups = $order->allOrderGroup;
        $line_items = array();
        $count = 1;
        foreach ($order_groups as $i => $item) {
            $device = $item->device;
            if($device){
                $line_items[$count] = array(
                    'ProductName'           => $device->name,
                    'ProductDescription'    => $device->description,
                    'ProductSellingPrice'   => $device->amount,
                    'ProductQty'            => 1,
                    'ProductCostPrice'      => $device->amount,
                );
                $count += 1;

            }
            
        }

        $post_array = array(
            //////// Required fields //////////////
            'ApiLogin'              => config('eye4fraud.api_login'),
            'ApiKey'                => config('eye4fraud.api_key'),
            'TransactionId'         => $paymentLog->transaction_num,
            'OrderDate'             => $order->date_processed,
            'OrderNumber'           => $order->order_num,
            'BillingFirstName'      => $invoice->billing_fname,
            'BillingMiddleName'     => '',
            'BillingLastName'       => $invoice->billing_lname,
            'BillingCompany'        => $invoice->business_name,
            'BillingAddress1'       => $invoice->billing_address_line_1,
            'BillingAddress2'       => $invoice->billing_address_line_2,
            'BillingCity'           => $invoice->billing_city,
            'BillingState'          => $invoice->billing_state,
            'BillingZip'            => $invoice->billing_zip,
            'BillingCountry'        => 'US',
            'BillingEveningPhone'   => $customer->phone,
            'BillingEmail'          => $customer->email,
            'IPAddress'             => '',
            'ShippingFirstName'     => $invoice->shipping_fname,
            'ShippingMiddleName'    => '',
            'ShippingLastName'      => $invoice->shipping_lname,
            'ShippingCompany'       => '',
            'ShippingAddress1'      => $invoice->shipping_address_line_1,
            'ShippingAddress2'      => $invoice->shipping_address_line_2,
            'ShippingCity'          => $invoice->shipping_city,
            'ShippingState'         => $invoice->shipping_state,
            'ShippingZip'           => $invoice->shipping_zip,
            'ShippingCountry'       => 'US',
            'ShippingEveningPhone'  => $customer->phone,
            'ShippingEmail'         => $customer->email,
            'ShippingCost'          => 0,
            'GrandTotal'            => $invoice->subtotal,
            'CCType'                => $creditCard->card_type,
            'CCFirst6'              => '',
            'CCLast4'               => $creditCard->last4,
            'CIDResponse'           => 'M',
            'AVSCode'               => 'Y',
            'LineItems'             => $line_items,
            /////////// Optional fields /////////////
            //'SiteName'                  => 'Britex',
        );
        return $post_array;
 
    }
    public static function send_order($order, $creditCard, $paymentLog){
        //\Log::info($paymentLog);
        $client = new \GuzzleHttp\Client();
        $post_data = Eye4Fraud::prepare_data($order, $creditCard, $paymentLog);
        //print_r($post_data);
        //return;
        \Log::info(["sending to eye4fraud", $order->id, $post_data["TransactionId"]]);
        $url = config('eye4fraud.api_url');
        //echo($url);
        $response = $client->post(
            $url,
            array('form_params' => $post_data)
        );
        //print_r($response);
        return $response;

    }
}

/*

$post_query = http_build_query($post_array);
$ch = curl_init('https://eye4fraud.com/api/');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_query);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
curl_close($ch);
echo $response;
*/
?>
