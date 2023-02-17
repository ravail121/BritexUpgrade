<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AttTwoUsageData
 */
class AttTwoUsageData extends Model
{

	/**
	 * @var string
	 */
    public $table = 'att_two_usage_data';

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
