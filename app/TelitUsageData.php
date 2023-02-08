<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TelitUsageData extends Model
{
    public $table = 'telit_usage_data';

    protected $fillable = [
        'iccid', 'carrier', 'status', 'dateActivated', 'usageData', 'usageSms', 'usageVoice'
    ];
}
