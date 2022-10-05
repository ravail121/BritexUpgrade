<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CreditToInvoice extends Pivot
{
    protected $table = 'credit_to_invoice';
    
    protected $fillable = [
        'credit_id',
        'invoice_id',
        'amount',
        'description'
    ];

    public function credit()
    {
        return $this->belongsTo(Credit::class, 'credit_id', 'id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'id');
    }

    public function scopeCreditToInvoice($query)
    {
        return $query->sum('amount');
        return $query->where('applied_to_invoice', 1);
    }
}
