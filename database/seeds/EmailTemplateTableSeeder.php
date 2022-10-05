<?php

use App\Model\Company;
use App\Model\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateTableSeeder extends Seeder
{
    const CODE     = 'biz-verification-approved';
    const SUBJECT  = 'Business Verification Approved';
    const FROM     = 'admin@teltik.pw';
    const TO       = 'USER_EMAIL';
    const BODY     = 'Dear [FIRST_NAME] [LAST_NAME],<br><br>Congratulations, your business [BUSINESS_NAME] has been approved!<br><br>Click <a href="[HERE]">here</a> to complete your order.';

    const REJECTEDBODY = 'Dear [FIRST_NAME] [LAST_NAME],<br>We are sorry, your business [BUSINESS_NAME] has been rejected!<br>';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	$companies = Company::all();
    	foreach ($companies as $company) {
    		EmailTemplate::create([
        		'company_id' => $company->id,
        		'code'       => self::CODE,
        		'from'       => self::FROM,
        		'to'         => self::TO,
                'subject'    => self::SUBJECT,
                'body'       => self::BODY,
            ]);

            EmailTemplate::create([
                'company_id' => $company->id,
                'code'       => 'biz-verification-rejected',
                'from'       => self::FROM,
                'to'         => self::TO,
                'subject'    => 'Business Verification Rejected',
                'body'       => self::REJECTEDBODY,
            ]);
    	}
    }
}
