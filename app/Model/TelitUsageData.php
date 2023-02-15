<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TelitUsageData
 */
class TelitUsageData extends Model
{

	/**
	 * @var string
	 */
    public $table = 'telit_usage_data';

	/**
	 * @var string[]
	 */
    protected $fillable = [
        'iccid',
	    'carrier',
	    'status',
	    'date_activated',
	    'usage_data'
    ];
}
