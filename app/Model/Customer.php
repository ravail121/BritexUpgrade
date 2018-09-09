<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table ='customer'; 
    protected $fillable=[
     'billing_address1', 'billing_address2', 'billing_city','billing_state_id','shipping_address1','shipping_address2','shipping_state_id','hash','shipping_city',
    ];
}
