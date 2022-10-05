<?php

use App\Model\Staff;
use App\Model\Company;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class StaffTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

	public function run()
    {
    	$company = Company::first();
    	if($company){
	        Staff::create([
	    		'company_id' => $company->id,
	    		'level'		 => rand(1,3),
	    		'fname' 	 => Str::random(10),
	    		'lname' 	 => Str::random(10),
	    		'email' 	 => 'britex.test1@gmail.com',
	    		'password' 	 => bcrypt('qwerty'),
	    		'phone'		 =>	rand(1,3),
	    		'reset_hash' =>	Str::random(10),
	        ]);
    	}
    }
    
}
