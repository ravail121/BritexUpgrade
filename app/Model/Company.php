<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Company
 *
 * @package App\Model
 */
class Company extends Model
{
	/**
	 *
	 */
	const Id = [
		'britex'  => 1
	];

	/**
	 * @var string
	 */
	protected $table = 'company';

	/**
	 * @var string[]
	 */
	protected $appends = [
        'usaepay_live_formatted'
    ];

	/**
	 * @return mixed
	 */
	public function device(){
    	return $this->belongsTo('App\Model\Device')->withTrashed();
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function devices()
    {
        return $this->hasMany('App\Model\Device', 'company_id', 'id');
    }

	/**
	 * @return mixed
	 */
	public function visibleDevices()
    {
        return $this->devices()->visible()->orderBy('device.sort');
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function sims()
    {
        return $this->hasMany('App\Model\Sim', 'company_id', 'id');
    }

	/**
	 * @return mixed
	 */
	public function visibleSims()
    {
        return $this->sims()->visible();
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function order()
    {
        return $this->belongsTo('App\Model\Order');
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function tax()
    {
        return $this->belongsTo('App\Model\Tax', 'company_id', 'id');
    }

	/**
	 * @return bool
	 */
	public function getUsaepayLiveFormattedAttribute()
    {
        return (bool) !$this->usaepay_live;
    }

	/**
	 * @return mixed|string|string[]
	 */
	public function getUrlFormattedAttribute()
    {
        return str_replace(['http://', 'https://', 'www.'], ['','',''], $this->url);
    }

	/**
	 * @return string|string[]|null
	 */
	public function getSupportPhoneFormattedAttribute()
    {
        $number = preg_replace("/[^\d]/","",$this->support_phone_number);
    
        $length = strlen($number);

        if($length == 10) {
            $number = preg_replace("/^1?(\d{3})(\d{3})(\d{4})$/", "$1-$2-$3", $number);
        }

        return $number;
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function carrier()
    {
        return $this->belongsToMany('App\Model\Carrier', 'company_to_carrier', 'company_id', 'carrier_id');
    }
}