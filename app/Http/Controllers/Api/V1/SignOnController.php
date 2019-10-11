<?php

namespace App\Http\Controllers\Api\V1;

use Auth;
use Carbon\Carbon;
use App\Model\Invoice;
use App\Model\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;

class SignOnController extends BaseController
{
    
    public function signOn(Request $request)
    {
        $data = $request->validate([
            'identifier' => 'required',
            'password'   => 'required',
        ]);
        $companyId = \Request::get('company')->id;

        if ($this->isNumeric($data['identifier'])) {
            $customer = Customer::find($data['identifier']);

            if(!$customer){
                return $this->respondError("Invalid Customer ID");
            }

            $data['email'] = $customer->email;
        }else {
            $data['email'] = $data['identifier'];
            $customer = Customer::where('company_id', $companyId)->whereEmail($data['email'])->first();
        }

        if($customer->company_id != $companyId){
            return $this->respondError("Invalid Company ID");
        }

        unset($data['identifier']);

        if(Auth::validate($data))
        {
            $user = Customer::where('company_id', $companyId)->whereEmail($data['email'])->get(['id','hash', 'account_suspended']);

            $date = Carbon::today()->addDays(6)->endOfDay();
            $invoice = Invoice::where([
                ['customer_id', $user[0]->id],
                ['status', Invoice::INVOICESTATUS['closed&paid'] ],
                ['type', Invoice::TYPES['monthly']]
            ])->whereBetween('start_date', [Carbon::today()->startOfDay(), $date])->where('start_date', '!=', Carbon::today())->first();

            $user[0]['paid_monthly_invoice'] = $invoice ? 1: 0;
            return $this->respond($user[0]);
        }
        else{
            return $this->respondError("Invalid Email or Password");
        }
    }


    /**
     * [isNumeric description]
     * @param  [type]  $value [description]
     * @return boolean        [return true for nunmaric value false for rest]
     */
    private function isNumeric($value)
    {
        return (filter_var($value, FILTER_VALIDATE_INT));
    }
}