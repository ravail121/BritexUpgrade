<?php

use Illuminate\Database\Seeder;
use App\Model\SystemEmailTemplateDynamicField;

class SystemEmailTemplateDynamicFieldTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = $this->getArray();
        foreach ($data as $name) {
            SystemEmailTemplateDynamicField::create([
                'name' => $name['name'],
                'code' => $name['code'],
                'description' => $name['description'], 
            ]);
        }
    }

    protected function getArray()
    {
        return [
        	
            [   'name' => 'business_verification__business_name',    
                'code' => 'biz-verification-submitted', 
                'description' => 'Name of business in application'
            ],
            [   'name' => 'business_verification__fname',     
                'code' => 'biz-verification-submitted', 
                'description' => 'Customer first name in biz verification application'
            ],
            [   'name' => 'business_verification__lname', 
                'code' => 'biz-verification-submitted', 
                'description' => 'Customer last name in biz verification application'
            ],
            [   'name' => 'business_verification__business_name',    
                'code' => 'biz-verification-approved', 
                'description' => 'Name of business in application'
            ],
            [   'name' => 'business_verification__fname',     
                'code' => 'biz-verification-approved', 
                'description' => 'Customer first name in biz verification application'
            ],
            [   'name' => 'business_verification__lname', 
                'code' => 'biz-verification-approved', 
                'description' => 'Customer last name in biz verification application'
            ],
            [   'name' => 'business_verification__fname',     
                'code' => 'account-suspension-customer', 
                'description' => 'Customer first name in biz verification application'
            ],
            [   'name' => 'business_verification__lname', 
                'code' => 'account-suspension-customer', 
                'description' => 'Customer last name in biz verification application'
            ],
            [   'name' => 'business_verification__fname',     
                'code' => 'account-suspension-admin', 
                'description' => 'Customer first name in biz verification application'
            ],
            [   'name' => 'business_verification__lname', 
                'code' => 'account-suspension-admin', 
                'description' => 'Customer last name in biz verification application'
            ],
            [   'name' => 'business_verification__fname',     
                'code' => 'one-time-invoice', 
                'description' => 'Customer first name in biz verification application'
            ],
            [   'name' => 'business_verification__lname', 
                'code' => 'one-time-invoice', 
                'description' => 'Customer last name in biz verification application'
            ],
            [   'name' => 'business_verification__fname',     
                'code' => 'monthly-invoice', 
                'description' => 'Customer first name in biz verification application'
            ],
            [   'name' => 'business_verification__lname', 
                'code' => 'monthly-invoice', 
                'description' => 'Customer last name in biz verification application'
            ],
            [   'name' => 'business_verification__fname',     
                'code' => 'biz-verification-rejected', 
                'description' => 'Customer first name in biz verification application'
            ],
            [   'name' => 'business_verification__lname', 
                'code' => 'biz-verification-rejected', 
                'description' => 'Customer last name in biz verification application'
            ],
            [   'name' => 'business_verification__business_name', 
                'code' => 'biz-verification-rejected', 
                'description' => 'Name of business in application'
            ],
            [   'name' => 'customer_fname', 
                'code' => 'reset-password', 
                'description' => 'Customer first name'
            ],
            [   'name' => 'customer_lname', 
                'code' => 'reset-password', 
                'description' => 'Customer last name'
            ],
            [   'name' => 'customer_fname', 
                'code' => 'port-complete', 
                'description' => 'Customer first name'
            ],
            [   'name' => 'customer_lname', 
                'code' => 'port-complete', 
                'description' => 'Customer last name'
            ],
        ];
    }
}
