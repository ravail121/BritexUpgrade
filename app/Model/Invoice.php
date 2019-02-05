<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'invoice';

    protected $fillable = [ 'customer_id', 'type', 'status', 'start_date', 'end_date', 'due_date', 'subtotal', 'total_due', 'prev_balance', 'payment_method', 'notes', 'business_name', 'billing_fname', 'billing_lname', 'billing_address_line_1', 'billing_address_line_2', 'billing_city', 'billing_state', 'billing_zip', 'shipping_fname', 'shipping_lname', 'shipping_address_line_1', 'shipping_address_line_2', 'shipping_city', 'shipping_state', 'shipping_zip'];

}
