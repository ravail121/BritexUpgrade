<?php

namespace App\Http\Requests;

use LVR\CreditCard\CardCvc;
use LVR\CreditCard\CardNumber;
use LVR\CreditCard\CardExpirationDate;
use LVR\CreditCard\CardExpirationYear;
use LVR\CreditCard\CardExpirationMonth;
use Illuminate\Foundation\Http\FormRequest;

class CreditCardRequest extends FormRequest
{
    
    public $cardNumber;


    /**
     * Gets Card Number
     * 
     * @param string $cardNumber
     */
    public function __construct($cardNumber)
    {
        $this->cardNumber = $cardNumber;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'billing_fname'    => 'required',
            'billing_lname'    => 'required',
            'amount'           => 'required',
            'billing_address1' => 'required|max:5000',
            'billing_address2' => 'nullable|max:5000',
            'billing_city'     => 'required|max:50',
            'billing_state_id' => 'required|string|max:2',
            'billing_zip'      => 'required|digits:5',
            'payment_card_no'  => ['required', new CardNumber],
            'expires_mmyy'     => ['required', new CardExpirationDate('m/y')],
            'payment_cvc'      => ['required', new CardCvc($this->cardNumber)],
        ];
    }
}
