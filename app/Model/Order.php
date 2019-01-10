<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'order';

    protected $fillable = [
        'hash', 'customer_id', 'company_id', 'active_group_id'
    ];

    public function order_group(){
    	return $this->belongsTo('App\Model\OrderGroup')->withTrashed();
    }

    public function bizVerification()
    {
        return $this->hasOne(BusinessVerification::class);
    }

    public function OG()
    {
        return $this->hasOne('App\Model\OrderGroup', 'id', 'active_group_id');
    }

    public function scopeHash($query, $hash)
    {
        return $query->where('hash', $hash);
    }

    public function customer()
    {
        return $this->hasOne('App\Model\Customer', 'id', 'customer_id');
    }
    public function company()
    {
        return $this->hasOne('App\Model\Company', 'id', 'company_id');
    }
}
