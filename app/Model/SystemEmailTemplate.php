<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class SystemEmailTemplate extends Model
{
    protected $table ='system_email_template';

    public $timestamps = false;
	
	protected $fillable = [ 'code', 'name', 'description'];
}
