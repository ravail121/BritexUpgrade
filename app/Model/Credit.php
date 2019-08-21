<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    protected $table = 'credit'; 

    protected $fillable = [
        'customer_id', 
        'amount',
        'applied_to_invoice',
        'type',
        'date',
        'payment_method', 
        'description', 
        'account_level',
        'subscription_id',
        'staff_id',
        'order_id'
    ];

    protected $appends = [
        'type_description'
    ];

    public function scopeAppliedCompletely($query)
    {
        return $query->where('applied_to_invoice', 1);
    }

    public function scopeNotAppliedCompletely($query)
    {
        return $query->where('applied_to_invoice', 0);
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('M-d-Y h:i A');
    }

    public function getUsedCreditsAttribute()
    {
        return $this->usedOnInvoices->sum('amount');
    }

    public function getPendingCreditsAttribute()
    {
        return $this->amount - $this->used_credits;
    }

    public function getTypeDescriptionAttribute($value)
    {
        $value = $this->type;
        if ($value == 1) {
            return 'Payment';
        }
        elseif ($value == 2) {
            return 'Manual Credit';
        }
        return 'Closed Invoice';
    }

    public function customer()
    {
        return $this->belongsTo('App\Model\Customer', 'customer_id', 'id');
    }

    public function usedOnInvoices()
    {
        return $this->hasMany('App\Model\CreditToInvoice', 'credit_id', 'id');
    }

}
