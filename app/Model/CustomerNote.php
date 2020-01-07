<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CustomerNote extends Model
{
    protected $table = 'customer_note';

    protected $fillable = [
        'customer_id',
        'staff_id',
        'date',
        'text',
    ];
}
