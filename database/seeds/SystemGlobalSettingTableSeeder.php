<?php

use Illuminate\Database\Seeder;
use App\Model\SystemGlobalSetting;

class SystemGlobalSettingTableSeeder extends Seeder
{
    const SITE_URL    = 'britex.pw';
    const UPLOAD_PATH = '/var/wwww/html';

    
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        SystemGlobalSetting::create([
            'site_url'    => self::SITE_URL,
            'upload_path' => self::UPLOAD_PATH,
        ]);
    }
}
