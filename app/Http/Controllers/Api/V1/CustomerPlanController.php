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

class CustomerPlanController extends BaseController
{
    public function get(Request $request){

        if ($request->hash) {
    	    $customerId = Customer::whereHash($request->hash)->first(['id']);

            return $this->respond(['customer-plans' => $this->getSubscriptions($customerId['id'])]);
        } else {
            return [
                'error' => true
            ];
        }
    }

    public function getSubscriptions($customerId){

    	$subscriptions = Subscription::with('plan', 'device', 'subscriptionAddonNotRemoved.addons','port')->whereCustomerId($customerId)
            ->get();
    	
    	return $subscriptions;
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
        if($data['id']){
            $updatePort = Port::find($data['id'])->update($data);
            if($updatePort){
                return $this->respond('sucessfully Updated');
            }
        }else{
            $data['notes'] = '';
            $data['subscription_id'] = $request->subscription_id;
            Port::create($data);
            return $this->respond('sucessfully Updated');
        }
        
    }
}
