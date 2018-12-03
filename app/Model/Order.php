<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'order';

    protected $fillable = [
        'hash', 'company_id', 'active_group_id'
    ];

    public function order_group(){
    	return $this->belongsTo('App\Model\OrderGroup')->withTrashed();
    }

    public function OG()
    {
     return $this->hasOne('App\Model\OrderGroup', 'id', 'active_group_id');
   }
      
}
