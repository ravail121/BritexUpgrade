<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Model\Company;
use App\Model\Order;

class BusinessVerificationTest extends TestCase
{
    const HEADER_DATA = ['Authorization' => 'alar324r23423'];

    public function test_business_verification()
    {
        $company    = Company::where('api_key', self::HEADER_DATA['Authorization'])->first();
        $hash       = sha1(time());
        $insertOrder = $this->withHeaders(self::HEADER_DATA)->post('api/order?'.http_build_query([
            'hash' => $hash,
            'company_id' => $company->id
        ]));
        $order = Order::find($insertOrder->json()['id']);
        $sendVerificationRequest = $this->withHeaders(self::HEADER_DATA)->post('api/biz-verification?'.http_build_query([
            'order_hash' => $order->hash,
            'fname' => 'Test',
            'lname' => 'Test',
            'email' => md5(rand(1,9)).'@gmail.com',
            'business_name' => 'Test',
            'tax_id' => '654654654',
        ]));
        return $sendVerificationRequest->assertJson([
            'order_hash' => $order->hash
        ]);
    }

    public function test_biz_verifcation_empty_fields()
    {
        $company    = Company::where('api_key', self::HEADER_DATA['Authorization'])->first();
        $hash       = sha1(time());
        $insertOrder = $this->withHeaders(self::HEADER_DATA)->post('api/order?'.http_build_query([
            'hash' => $hash,
            'company_id' => $company->id
        ]));
        $order = Order::find($insertOrder->json()['id']);
        $sendVerificationRequest = $this->withHeaders(self::HEADER_DATA)->post('api/biz-verification?'.http_build_query([
            'order_hash' => $order->hash,
            'fname' => '',
            'lname' => '',
            'email' => '',
            'business_name' => '',
            'tax_id' => '',
        ]));
        return $sendVerificationRequest->assertJson([
            'details' => [
                "The fname field is required.",
                "The lname field is required.",
                "The email field is required.",
                "The business name field is required."
            ]
        ]);
    }

}
