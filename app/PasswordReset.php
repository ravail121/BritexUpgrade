<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{

	/**
	 * @var string
	 */
	public $table = 'password_reset';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email',
	    'token',
	    'company_id',
	    'created_at'
    ];

    public $timestamps = false;
}
