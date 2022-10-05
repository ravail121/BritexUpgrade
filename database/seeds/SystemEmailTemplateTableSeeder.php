<?php

use Illuminate\Database\Seeder;
use App\Model\SystemEmailTemplate;

class SystemEmailTemplateTableSeeder extends Seeder
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
            SystemEmailTemplate::create([
                'name' => $name['name'],
                'code' => $name['code'],
                'description' => $name['description'], 
            ]);
        }
    }

    protected function getArray()
    {
        return [
        	
            [   'name' => 'Business Verification Submitted',    
                'code' => 'biz-verification-submitted', 
                'description' => 'Event trigger when business verification submitted by customer'
            ],
            [   'name' => 'Business Verification Approved',     
                'code' => 'biz-verification-approved', 
                'description' => 'Event trigger when admin marks the business verification as approved'
            ],
            [   'name' => 'Subscriptions are Suspended', 
                'code' => 'account-suspension-customer', 
                'description' => 'Email to customer that account suspended'
            ],
            [   'name' => 'Subscriptions are Suspended', 
                'code' => 'account-suspension-admin', 
                'description' => 'Email to admin that account suspended'
            ],
            [   'name' => 'Download Invoice', 
                'code' => 'one-time-invoice', 
                'description' => 'Event trigger to generate one time invoice'
            ],
            [   'name' => 'Check Monthly Invoice', 
                'code' => 'monthly-invoice', 
                'description' => 'Event trigger to generate one time invoice'
            ],
            [   'name' => 'Reset Password', 
                'code' => 'reset-password', 
                'description' => 'Reset Password'
            ],
            [   'name' => 'Business Verification Rejected', 
                'code' => 'biz-verification-rejected', 
                'description' => 'Event triggerwhen admin mark the business verification as rejected'
            ],
            [   'name' => 'Port Complete', 
                'code' => 'port-complete', 
                'description' => 'Event triggerwhen admin mark the porting as complete'
            ],
        ];
    }
}
