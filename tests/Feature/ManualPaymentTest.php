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
//    public function testCreditCardIdFieldRequired() {
//        $cardData = [
//            'credit_card_id' => 31,
//            'payment_type' => 'Manual Payment',
//            'amount' => '10',
//            "without_order" => 1
//        ];
//        $request = request();
//        $request->replace($cardData);
//        $controller = app(CardController::class);
//        // processCreditInvoice function create invoice so called directly
//        $data = $controller->processCreditInvoice($request, null, null);
//        $this->assertNull($data, 'No invoice created');
//        $cardData = [
//            'credit_card_id' => '',
//            'payment_type' => 'Manual Payment',
//            'amount' => '100',
//            "without_order" => 1
//        ];
//
//        $response = $this->post('/api/charge-card', $cardData,['token' => 'W19bR6gCPkHnr9ckN1znQphN8CUKQodlhXDroydDl1yYCOFqst8zV20VInKs']);
//
//
//        $response->assertStatus(400);
//        dd($response);
//        $this->assertEquals('The credit card id field is required.', $response->json("errors")["credit_card_id"][0]);
//    }

//    public function testAmountIdFieldRequired() {
//        $cardData = [
//            'credit_card_id' => '31',
//            'payment_type' => 'Manual Payment',
//            'amount' => '',
//            "without_order" => 1
//        ];
//        $response = $this->get('/api/charge-card', $cardData);
//        $response->assertStatus(422);
//        $this->assertEquals('The amount field is required.', $response->json("errors")["amount"][0]);
//    }
//
//    public function testManaulTesting()
//    {
//
//        $cardData = [
//            'credit_card_id' => '31',
//            'payment_type' => 'Manual Payment',
//            'amount' => 100,
//            "without_order" => 1
//        ];
//        $response = $this->get('/api/charge-card', $cardData);
//    }

}
