<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    protected $table = 'credits'; 

    protected $fillable = [
        'customer_id', 
        'amount',
        'applied_to_invoice',
        'type',
        'date',
        'payment_method', 
        'description', 
        'account_level',
        'subscription_id',
    ];

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }
}
