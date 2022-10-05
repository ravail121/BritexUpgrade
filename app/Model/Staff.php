<?php

namespace App\Model;

use App\Model\Company;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $table = 'staff';

    protected $fillable = [
    	'company_id',
    	'level',
    	'name',
    	'email',
    	'password',
    	'reset_hash',
    	'phone',
    	'remember_token',
    ];

    public function company()
    {
    	return $this->belongsTo('App\Company');
    }
}
