<?php

use App\Model\Company;
use App\Model\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateTableTempSeeder extends Seeder
{
    const FROM     = 'admin@teltik.pw';
    const TO       = 'USER_EMAIL';
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
                'code'       => 'biz-verification-rejected',
                'from'       => self::FROM,
                'to'         => self::TO,
                'subject'    => 'Business Verification Rejected',
                'body'       => self::REJECTEDBODY,
            ]);
    	}
    }
}
