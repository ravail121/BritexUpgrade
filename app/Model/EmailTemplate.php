<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $table = 'email_template';

    protected $fillable = [
        'company_id', 'code', 'from', 'to', 'subject', 'body', 'notes', 'reply_to', 'cc', 'bcc', 
    ];
}
