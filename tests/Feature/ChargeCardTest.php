<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Model\Customer;
use App\Model\Credit;
use App\Model\Subscription;
use Mockery;


class ChargeCardTest extends TestCase
{
    use DatabaseTransactions;
   /**
 * @covers MyClass::myMethod
 */
    // public function testChargeCard()
    // {
    //     $customers = Customer::where('account_suspended', 1)
    //     ->whereHas('customerCreditCards')
    //     ->inRandomOrder()
    //     ->take(100)
    //     ->get()
    //     ->filter(function($customer) {
    //         return $customer->getCreditsCountAttribute() == 0.00;
    //     });

    //     foreach ($customers as $customer) {
    //         $payload = [
    //             'amount' => '0',
    //             'description' => null,
    //             'credit_card_id' => $customer->customerCreditCards->first()->id,
    //             'payment_type' => 'Manual Payment',
    //             'customer_id' => $customer->id,
    //             'without_order' => true,
    //             'staff_id' => 1,
    //             'subscription_id' => null,
    //         ];
    //         $header=['Authorization' => $customer->customerCreditCards->first()->api_key];
    //         // Call the API endpoint
    //         $response = $this->withHeaders($header)->post('api/charge-card?'.http_build_query($payload));
           
    //         // Assert that the response is successfulx
    //         $response->assertSuccessful();
            
    //         // Get the updated customer from the database
    //         $customer = Customer::find($customer->id);
    //         // Assert that account_suspended is set to 0
    //         $this->assertEquals(0, $customer->account_suspended);

    //         // Assert that all suspended subscriptions are now active
            
    //         $this->assertTrue($customer->subscription()->where('status', Subscription::STATUS['suspended'])->doesntExist());

    //         // Assert that all past due subscriptions have been updated
    //         $this->assertTrue($customer->subscription()->where('sub_status', Subscription::SUB_STATUSES['account-past-due'])->doesntExist());
    //     }
    // }

    /**
 * @covers MyClass::myMethod
 */
    // public function testChargeCardCredit()
    // {
    //     $customers = Customer::where('account_suspended', 1)
    //     ->whereHas('customerCreditCards')
    //     // ->inRandomOrder()
    //     // ->take(50)
    //     ->where('id',30)
    //     ->get();
        

    //     foreach ($customers as $customer) {
    //         try {
    //         $beforeCount = Credit::where('customer_id',$customer->id)->count();
    //         $payload = [
    //             'amount' => '100',
    //             'description' => null,
    //             'credit_card_id' => $customer->customerCreditCards->first()->id,
    //             'payment_type' => 'Manual Payment',
    //             'customer_id' => $customer->id,
    //             'without_order' => true,
    //             'staff_id' => 1,
    //             'subscription_id' => null,
    //         ];
    //         $header=['Authorization' => $customer->customerCreditCards->first()->api_key];
    //         // Call the API endpoint
            
    //         $response = $this->withHeaders($header)->post('api/charge-card?'.http_build_query($payload));
           
    //         // Assert that the response is successfulx

    //         $afterCount = Credit::where('customer_id',$customer->id)->count();

    //         // Assert that the number of records has increased by one
    //          $this->assertEquals($beforeCount + 1, $afterCount);
    //         } catch (\Exception $e) {
    //         // Log the e>xception or print out the error message to identify which customer caused the issue
    //        dd($customer->id);
    //     }
            
    //     }
    // }


    /**
 * @covers MyClass::myMethod
 */
    public function testChargeCardFinal(){

        $amount=400;

        $customers = Customer::where('account_suspended', 1)
        ->whereHas('customerCreditCards')
        ->whereHas('orders')
        ->inRandomOrder()
        ->take(100)
        ->get()
        ->filter(function($customer) use ($amount) {
            return $customer->getCreditsCountAttribute() < $amount;
        });
        // $customers = Customer::where('account_suspended', 1)
        // // ->whereHas('customerCreditCards')
        // // ->whereHas('orders')
        // // ->inRandomOrder()
        // // ->take(100)
        // ->where('id',3641)
        // ->get()
        // ->filter(function($customer) use ($amount) {
        //     return $customer->getCreditsCountAttribute() < $amount;
        // });

        
        foreach ($customers as $customer) {
             try {
            $beforeCount = Credit::where('customer_id',$customer->id)->count();
            $payload = [
                'amount' => $amount,
                'description' => null,
                'credit_card_id' => $customer->customerCreditCards->first()->id,
                'payment_type' => 'Manual Payment',
                'customer_id' => $customer->id,
                'without_order' => true,
                'staff_id' => 1,
                'subscription_id' => null,
            ];
            $header=['Authorization' => $customer->customerCreditCards->first()->api_key];
            // Call the API endpoint
            $credits=$customer->credits_count;
            $response = $this->withHeaders($header)->post('api/charge-card?'.http_build_query($payload));
           
            // Assert that the response is successfulx

            $customer = Customer::find($customer->id);

            if($amount>=$credits){

                $this->assertEquals(0, $customer->account_suspended);

                // Assert that all suspended subscriptions are now active
                $this->assertTrue($customer->subscription()->where('status', Subscription::STATUS['suspended'])->doesntExist());

                // Assert that all past due subscriptions have been updated
                $this->assertTrue($customer->subscription()->where('sub_status', Subscription::SUB_STATUSES['account-past-due'])->doesntExist());

           }

        } catch (\Exception $e) {
            // Log the e>xception or print out the error message to identify which customer caused the issue
           dd($customer->id);
        }
            
        }

    }
}
