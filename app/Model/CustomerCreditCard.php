<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CustomerCreditCard extends Model
{
    protected $table = 'customer_credit_cards'; 

    protected $fillable = [
        'token',
        'api_key',
        'customer_id', 
        'cardholder',
        'number',
        'expiration',
        'cvc',
        'billing_address1', 
        'billing_address2', 
        'billing_city',
        'billing_state_id',
        'billing_zip',
    ];

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }
}
