<?php

namespace Tests\Feature;

use App\Model\PaymentLog;
use App\Services\Payment\UsaEpay;
use Tests\TestCase;
use App\Http\Controllers\Api\V1\CardController;
class ManualPaymentTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testManualPaymentLog() {
        $cardData = [
            'credit_card_id' => 31,
            'payment_type' => 'Manual Payment',
            'amount' => '10.00',
            "without_order" => 1
        ];
        $request = request();
        $request->replace($cardData);
        $trans = new UsaEpay();
        $trans->refnum = rand(100000000,100000000000);
        $trans->error = 'Approved';
        $trans->exp = '';
        $trans->amount = $cardData['amount'];
        $controller = app(CardController::class);
        // processCreditInvoice function create invoice so called directly
        $data = $controller->processCreditInvoice($request, $trans, null);
        $this->assertNull($data, 'No invoice created');
        $paymentLog = PaymentLog::where('transaction_num', $trans->refnum)->first();
        $this->assertNotNull($paymentLog, "Payment log has been maintained");
        $this->assertEquals($paymentLog->error, $trans->error);
        $this->assertEquals($paymentLog->amount, $trans->amount);
    }

}
