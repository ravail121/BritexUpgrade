<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'invoice';

    protected $fillable = [ 'customer_id', 'type', 'status', 'start_date', 'end_date', 'due_date', 'subtotal', 'total_due', 'prev_balance', 'payment_method', 'notes', 'business_name', 'billing_fname', 'billing_lname', 'billing_address_line_1', 'billing_address_line_2', 'billing_city', 'billing_state', 'billing_zip', 'shipping_fname', 'shipping_lname', 'shipping_address_line_1', 'shipping_address_line_2', 'shipping_city', 'shipping_state', 'shipping_zip'
	];

	public function order()
	{
		return $this->belongsTo(Order::class);
	}

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

	public function invoiceItem()
   	{
        return $this->hasMany('App\Model\InvoiceItem');
    }


    /**
     * Fetches Invoice Details from invoice table if status < 2
     * 
     * @param  Query   $query      
     * @param  int     $customerId
     * @return query
     */
    public function scopeGetDues($query, $customerId)
    {
        return $query->where('customer_id' , $customerId)->where('status', '<', 2);
    }



    /**
     * Returns total_due amount with 2 decimal places
     * 
     * @return float 
     */
    public function getTotalAttribute()
    {
        return self::toTwoDecimals($this->total_due);
    }


    public function getTypeNotOneAttribute()
    {
        return ($this->type != 1 && $this->start_date > $this->customer->billing_end) ;

    }

    public function getSubTotalAmountAttribute()
    {
        $sub_total = 0;

        if ($this->start_date == $this->customer->billing_start) {
            $sub_total = $this->subtotal;
        }
        return self::toTwoDecimals($sub_total);
    }


    public function getPastDueAttribute()
    {
        $pastDue = 0;

        if ($this->status == 0 && strtotime($this->start_date) < strtotime($this->customer->billing_start)) {

            $pastDue = $this->subtotal;
        }
        return self::toTwoDecimals($pastDue);
    }



    /**
     * [toTwoDecimals description]
     * @param  [type] $amount [description]
     * @return [type]         [description]
     */
    public static function toTwoDecimals($amount)
    {
        return number_format((float)$amount, 2, '.', '');
    }

}
