<?php

namespace App\Http\Controllers\Api\V1;

use Auth;
use Carbon\Carbon;
use App\Model\Invoice;
use App\Model\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;

/**
 * Class SignOnController
 *
 * @package App\Http\Controllers\Api\V1
 */
class SignOnController extends BaseController
{

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function signOn(Request $request)
    {
        $data = $request->validate([
            'identifier'    => 'required',
            'password'      => 'required',
        ]);
        $companyId = $request->get('company')->id;

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

        if(!isset($customer)) {
            return $this->respondError('Invalid Customer ID');
        }
        else if($customer->company_id !== $companyId){
            return $this->respondError("Invalid Company ID");
        }

        unset($data['identifier']);

		$data['company_id'] = $companyId;

        if(Auth::validate($data))
        {
            $user = Customer::where('company_id', $companyId)->whereEmail($data['email'])->get(['id', 'hash', 'account_suspended']);

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