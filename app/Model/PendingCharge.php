<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PendingCharge extends Model
{
    protected $table = 'pending_charge';
    protected $fillable = ['invoice_id'];


    public function Customer()
    {
     return $this->hasOne('App\Model\Customer', 'id');
   	}
}
