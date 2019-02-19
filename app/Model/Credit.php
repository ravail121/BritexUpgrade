<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    protected $table = 'credit'; 

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
        return $this->belongsTo('App\Model\Customer','customer_id','id');
    }
}
