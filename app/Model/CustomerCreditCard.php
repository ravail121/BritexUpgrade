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
        'default', 
        'cardholder',
        'number',
        'expiration',
        'last4',
        'card_type',
        'cvc',
        'billing_address1', 
        'billing_address2', 
        'billing_city',
        'billing_state_id',
        'billing_zip',
    ];

    public function customer()
    {
        return $this->belongsTo('App\Model\Customer');
    }

    public function addPrefixSlash()
    {
        $month = substr($this->expiration, 0, -2);
        $year  = substr($this->expiration, -2);
        if ($month < 10) {
            $month = '0'.$month;
        }
        return $month.'/'.$year;
    }
}
