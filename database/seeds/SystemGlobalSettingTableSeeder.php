<?php

use Illuminate\Database\Seeder;
use App\Model\SystemGlobalSetting;

class SystemGlobalSettingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	$data = [
    		'site_url'    => 'britex.pw',
    		'upload_path' => '/var/wwww/html',
    	];
        SystemGlobalSetting::create($data);
    }
}
