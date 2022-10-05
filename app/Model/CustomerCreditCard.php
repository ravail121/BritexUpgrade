<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CustomerCreditCard
 *
 * @package App\Model
 */
class CustomerCreditCard extends Model
{

	/**
	 *
	 */
	const DEFAULT = [
        'notDefault' =>  0,
        'default'    =>  1,
    ];

	/**
	 * @var string
	 */
	protected $table = 'customer_credit_card';

	/**
	 * @var string[]
	 */
	protected $fillable = [
        'token',
        'api_key',
        'customer_id', 
        'default', 
        'cardholder',
        'number',
        'expiration',
        'last4',
        'card_type',
        'cvc',
        'billing_address1', 
        'billing_address2', 
        'billing_city',
        'billing_state_id',
        'billing_zip',
    ];

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function customer()
    {
        return $this->belongsTo('App\Model\Customer');
    }

	/**
	 * @return string
	 */
	public function addPrefixSlash()
    {
        $month = substr($this->expiration, 0, -2);
        $year  = substr($this->expiration, -2);
        if ($month < 10) {
            $month = '0'.$month;
        }
        return $month.'/'.$year;
    }

	/**
	 * @return false|string
	 */
	public function getLastFourAttribute()
    {
        return substr($this->last4, -4);

    }

	/**
	 * @return string
	 */
	public function getCardInfoAttribute()
    {
        return $this->card_type ." ". $this->getLastFourAttribute();

    }
}
