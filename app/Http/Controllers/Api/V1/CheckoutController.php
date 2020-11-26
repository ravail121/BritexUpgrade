<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Model\Customer;
use App\Model\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CheckoutController extends BaseController
{

    public function post(Request $request){
        $company_id = $request->get('company')->id;
        $customer_id = $request->get('customer_id');

        $query = Customer::where('company_id', $company_id)->where('email', $request->post('email'));
        if($customer_id){
            $query->where('id', '!=', $customer_id);
        }
        $exists = $query->exists();
        if($exists){
            return $this->respond(['error' => 'Email address already exists.']);
        }
        $data = $request->all();
        $hasError = $this->validate_input($data, [
            'order_hash' => 'required|string',
            'fname'              => 'required|string',
            'lname'              => 'required|string',
            'email'              => 'required|email',
            'company_name'       => 'sometimes|required|string',
            'phone'              => 'required|string',
            'alternate_phone'    => 'nullable|string',
            'password'           => 'required|string',
            'shipping_address1'  => 'required|string',
            'shipping_address2'  => 'nullable|string',
            'shipping_city'      => 'required|string',
            'shipping_state_id'  => 'required|string|max:2',
            'shipping_zip'       => 'required|string',
            'pin'                => 'required|digits:4',
        ]);

        if($hasError){
            return $hasError;
        }

        $data  = $request->except('_url');
        $order = Order::hash($data['order_hash'])->first();

        $customerData = $this->setData($order, $data);

        if($data['customer_id']){
            unset($customerData['hash']);
            $customer = Customer::where('id', $data['customer_id'])->update($customerData);
        }else{
            $customer = Customer::create($customerData);
        }

        if (!$customer) {
            return $this->respondError("problem in creating/updating a customer");
        }
        $customer = Customer::where('email', $request->get('email'))->first();
        return $this->respond(['success' => true, 'customer' => $customer]);
    }


    /**
     * This function sets some data for creating a customer
     *
     * @param Class   $order
     * @param array   $data
     * @return array
     */
    protected function setData($order, $data)
    {
        unset($data['order_hash']);
        if ($order->bizVerification) {
            $data['business_verification_id'] = $order->bizVerification->id;
            $data['business_verified']        = $order->bizVerification->approved;
        } elseif ($order->company->business_verification == 0) {
            $data['business_verification_id'] = null;
            $data['business_verified']        = 1;
        }
        $data['company_id'] = $order->company_id;
        $data['password']   = Hash::make($data['password']);
        if (!$data['customer_id']) {
            $data['hash'] = sha1(time() . rand());
        }
        $data['pin']        = $data['pin'];
        unset($data['customer_id']);
        return $data;
    }
}
