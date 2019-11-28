<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Support extends Model
{
    protected $table = 'faq'  ;
    
	protected $fillable =[ 'category_id', 'question', 'description' ];

	public function category()
	{
		return $this->belongsTo('App\Model\Category');
	}
}
