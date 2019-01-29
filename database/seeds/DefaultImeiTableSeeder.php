<?php

use App\Model\DefaultImei;
use Illuminate\Database\Seeder;

class DefaultImeiTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	$os = $this->getArray();
    	foreach ($os as $key => $value) {
    		DefaultImei::create([
				'type' => 1,
				'os'   => $key,
				'code' => $value,
		    ]);
            DefaultImei::create([
                'type' => 2,
                'os'   => $key,
                'code' => $value,
            ]);
    	}
    }

    protected function getArray()
    {
    	return [
    		'Android'    => '123456789012345',
    		'iOS'        => '123451234567890',
    		'Blackberry' => '098765432112345',
    		'Windows'    => '991154287221432',
    		'None'       => '834454280291462',
    	];
    }
}
