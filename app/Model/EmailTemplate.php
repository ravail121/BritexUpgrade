<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $table = 'email_template';

    protected $fillable = [
        'company_id', 'code', 'from', 'to', 'subject', 'body', 'notes', 'reply_to', 'cc', 'bcc', 
    ];

    public function body($strings, $replaceWith) {
        
        $body = str_replace($strings, $replaceWith, $this->body);
        
        return $body;
    }

    public function customerBody($strings, $data) {
        
        $replaceWith = [$data['fname'], $data['lname'], $data['email']];
        
        $body = str_replace($strings, $replaceWith, $this->body);
        
        return $body;
    } 
}
