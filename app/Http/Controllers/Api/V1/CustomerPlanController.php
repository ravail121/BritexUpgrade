<?php

namespace App\Http\Controllers\Api\V1;

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

        $InvoiceController = new InvoiceController();
        $innvoice = $InvoiceController->invoiceDetails($request);

    	$customerId = Customer::whereHash($request->hash)->first(['id']);

    	return $this->respond(['customer-invoice' =>$innvoice,'customer-plans' => $this->getSubscriptions($customerId['id'])]);
    }

    public function getSubscriptions($customerId){

    	$subscriptions = Subscription::with('plan', 'device', 'subscriptionAddon.addons','port')->whereCustomerId($customerId)->get();
    	
    	return $subscriptions;
    }

    public function updatePort(Request $request)
    {

        $data =  $request->validate([
            'id'                            => 'required',
            'authorized_name'               => 'required|max:20',
            'address_line1'                 => 'required',
            'address_line2'                 => 'sometimes|required',
            'city'                          => 'required|max:20',
            'zip'                           => 'numeric|required',
            'state'                         => 'required',
            'ssn_taxid'                     => 'sometimes|required',
            // 'sim_card_number'               => 'required',
            'number_to_port'                => 'numeric|required',
            'company_porting_from'          => 'required',
            'account_number_porting_from'   => 'required',
            'status'                        => 'required',
            'account_pin_porting_from'      => 'required',

        ]);

        $updatePort = Port::find($data['id'])->update($data);
        if($updatePort){
            return $this->respond('sucessfully Updated');
        }
    }
}
