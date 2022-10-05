<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $table = 'email_log';

    protected $fillable = [
        'company_id', 'customer_id', 'staff_id', 'business_verficiation_id', 'type', 'from', 'to', 'subject', 'body', 'notes', 'reply_to', 'cc', 'bcc',
    ];
}
