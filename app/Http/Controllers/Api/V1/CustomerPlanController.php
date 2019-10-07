<?php

namespace App\Http\Controllers\Api\V1;

use Carbon\Carbon;
use App\Model\Plan;
use App\Model\Port;
use App\Model\Customer;
use App\Model\Subscription;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;
use App\Events\PortPending;

class CustomerPlanController extends BaseController
{
    public function get(Request $request){

        if ($request->hash) {
    	    $customer = Customer::whereHash($request->hash)->with('tax')->first();
            $subscriptionDetails = $this->getSubscriptions($customer);
            return $this->respond([
                'customer-plans' => $subscriptionDetails[0],
                'monthlyAmountDetails' => $subscriptionDetails[1],
            ]);
        } else {
            return [
                'error' => true
            ];
        }
    }

    public function getSubscriptions($customer){

    	$subscriptions = Subscription::with('plan', 'device', 'subscriptionAddonNotRemoved.addons','port')->whereCustomerId($customer->id)->orderBy('id', 'desc')->get();

        $subscriptionPriceDetails = $this->getSubscriptionPriceDetails($subscriptions, $customer->tax->rate);

    	return [$subscriptions, $subscriptionPriceDetails];
    }

    private function getSubscriptionPriceDetails($subscriptions, $rate)
    {
        $subtotal = $stateTax = $regulatoryFee = 0;
        $rate = $rate/100;

        foreach ($subscriptions as $key => $subscription) {
            if($subscription->status == Subscription::STATUS['active']){
                $subtotal += $subscription->plan->amount_recurring;
                if($subscription->plan->taxable){
                    $stateTax += $subscription->plan->amount_recurring * $rate;
                }
                if($subscription->plan->regulatory_fee_type == "1"){
                    $regulatoryFee += $subscription->plan->regulatory_fee_amount;
                }else{
                    $regulatoryFee += $subscription->plan->amount_recurring * ($subscription->plan->regulatory_fee_amount/100 );
                }

                if( isset($subscription->subscription_addon_not_removed[0]) ){
                    foreach ($subscription->subscription_addon_not_removed->addons as $addon) {
                        $subtotal += $addon->amount_recurring;
                        if($addon->taxable){
                            $stateTax += $addon->amount_recurring * $rate;
                        }
                    }
                }
            }
        }
        $subtotal = $this->toTwoDecimals($subtotal);
        $stateTax = $this->toTwoDecimals($stateTax);
        $regulatoryFee = $this->toTwoDecimals($regulatoryFee);

        $monthlyTotalAmount = $subtotal + $stateTax + $regulatoryFee;
        return [
            'subtotal'      => $subtotal,
            'stateTax'      => $stateTax,
            'regulatoryFee' => $regulatoryFee,
            'monthlyTotalAmount' => $monthlyTotalAmount
        ];
    }

    public function updatePort(Request $request)
    {
        $data =  $request->validate([
            'authorized_name'               => 'required|max:20',
            'address_line1'                 => 'required',
            'city'                          => 'required|max:20',
            'zip'                           => 'numeric|required',
            'state'                         => 'required',
            'number_to_port'                => 'numeric|required',
            'company_porting_from'          => 'required',
            'account_number_porting_from'   => 'required',
            'status'                        => 'required',
            'account_pin_porting_from'      => 'required',

        ]);
        $data['address_line2']=  $request->address_line2;
        $data['ssn_taxid']=  $request->ssn_taxid;
        $data['id'] = $request->id;
        $data['date_submitted'] = Carbon::now();
        $request->headers->set('authorization', \Request::get('company')->api_key);
        if($data['id']){
            $updatePort = Port::find($data['id'])->update($data);
            if($updatePort){
                event(new PortPending($data['id']));
                return $this->respond('sucessfully Updated');
            }
        }else{
            $data['notes'] = '';
            $data['subscription_id'] = $request->subscription_id;
            $id = Port::create($data)->id;
            event(new PortPending($id));
            return $this->respond('sucessfully Updated');
        }
        
    }

    protected static function toTwoDecimals($amount)
    {
        return number_format((float)$amount, 2, '.', '');
    }
}
