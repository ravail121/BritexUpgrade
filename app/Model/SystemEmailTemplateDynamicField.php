<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class SystemEmailTemplateDynamicField extends Model
{
    protected $table ='system_email_template_dynamic_field';

    public $timestamps = false;
	
	protected $fillable = [ 'code', 'name', 'description'];

}
