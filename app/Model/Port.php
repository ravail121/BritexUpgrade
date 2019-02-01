<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Port extends Model
{
    protected $table = 'port';

    protected $fillable = [
        'subscription_id',
        'status', 
        'notes',
        'number_to_port',
        'company_porting_from',
        'account_number_porting_from',
        'authorized_name',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'zip',
        'ssn_taxid',
    ];
}
