<?php

use App\Model\DefaultImei;
use Illuminate\Database\Seeder;

class DefaultImeiTableSeeder extends Seeder
{

    const DEFAULT_IMEI_CODE = '353790070239305';
    const DEFAULT_PLAN_TYPE = 1;
    const DEFAULT_SORTING = 0;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	$os = $this->getArray();
    	foreach ($os as $os) {
    		DefaultImei::create([
				'type' => self::DEFAULT_PLAN_TYPE,
				'os'   => $os,
                'code' => self::DEFAULT_IMEI_CODE,
				'sort' => self::DEFAULT_SORTING,
		    ]);
    	}
    }

    protected function getArray()
    {
    	return [
    		'android',
    		'ios',
    		'blackberry',
    		'windows',
    		'none',
    	];
    }
}
