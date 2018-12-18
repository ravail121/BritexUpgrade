<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class SystemGlobalSetting extends Model
{
    protected $table = 'system_global_setting';

    protected $fillable = [
        'site_url', 'upload_path',
    ];
}
