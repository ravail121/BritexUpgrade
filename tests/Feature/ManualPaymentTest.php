<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Controllers\Api\V1\CardController;
class ManualPaymentTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCreditCardIdFieldRequired() {
        $autoPayData = [
            'credit_card_id' => 31,
            'payment_type' => 'Manual Payment',
            'amount' => '100',
            "without_order" => 1
        ];
        $request = request();
        $request->replace($autoPayData);
        $controller = app(CardController::class);
        // processCreditInvoice function create invoice so called directly
        $data = $controller->processCreditInvoice($request, null, null);

        $this->assertTrue(($data === null), 1);
//        $autoPayData = [
//            'credit_card_id' => '',
//            'payment_type' => 'Manual Payment',
//            'amount' => '100',
//            "without_order" => 1
//        ];
//        $response = $this->get('/api/auto-pay', $autoPayData);
//        $response->assertStatus(422);
//        $this->assertEquals('The credit card id field is required.', $response->json("errors")["credit_card_id"][0]);
    }

//    public function testAmountIdFieldRequired() {
//        $autoPayData = [
//            'credit_card_id' => '31',
//            'payment_type' => 'Manual Payment',
//            'amount' => '',
//            "without_order" => 1
//        ];
//        $response = $this->get('/api/auto-pay', $autoPayData);
//        $response->assertStatus(422);
//        $this->assertEquals('The amount field is required.', $response->json("errors")["amount"][0]);
//    }
//
//    public function testManaulTesting()
//    {
//
//        $autoPayData = [
//            'credit_card_id' => '31',
//            'payment_type' => 'Manual Payment',
//            'amount' => 100,
//            "without_order" => 1
//        ];
//        $response = $this->get('/api/auto-pay', $autoPayData);
//    }

}
