<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class CustomerLog extends Model
{
    protected $table = 'customer_log';

    protected $fillable = ['customer_id', 'content'];

    public function getCreatedAtFormattedAttribute()
    {
        return Carbon::parse($this->created_at)->format('F d, Y');
    }
}
