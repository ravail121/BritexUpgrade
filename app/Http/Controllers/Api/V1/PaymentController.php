<?php

namespace App\Http\Controllers\Api\V1;

use App\Model\Order;
use Illuminate\Http\Request;
use App\Services\Payment\UsaEpay;
use App\Http\Controllers\Controller;
use App\libs\Constants\ConstantInterface;

class PaymentController extends Controller implements ConstantInterface
{

    public $tran;


    public function __construct(UsaEpay $tran)
    {
        $this->tran = $tran;
    }



    /**
    * This function inserts data to customer_credit_card table
    * 
    * @param  Request    $request 
    * @return string     Json Response
    */
    public function chargeNewCard(Request $request)
    { 
        $this->setConstantData($request);
        $validation = $this->validateCredentials($request);

        if ($validation->fails()) {
            return response()->json([
                'message' => $validation->getMessageBag()->all()
            ]);
        }

        if (!$request->order_hash) {
            return response()->json([
                'message' => 'order_hash is required'
            ]);
        }


        $order = Order::hash($request->order_hash)->first();
        $this->tran = $this->setUsaEpayData($this->tran, $request);

        if($this->tran->Process()) {
            $order->update(['status' => 1]);
            $msg = $this->transactionSuccessful($request, $this->tran);
      
        } else {
            $msg = $this->transactionFail($order->id, $this->tran);
        }

        return $msg; 
    }





    /**
    * This function sets the variable with constant values
    * 
    * @param  array    $array
    * @return boolean
    */
    protected function setConstantData($request)
    {
        $request->key         = env('SOURCE_KEY');
        $request->usesandbox  = self::TRAN_TRUE;
        $request->invoice     = self::TRAN_INVOICE;
        $request->isrecurring = self::TRAN_TRUE; 
        $request->savecard    = self::TRAN_TRUE; 
        $request->billcountry = self::TRAN_BILLCOUNTRY;

        return true;
    }

}
