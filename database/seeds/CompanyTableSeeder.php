<?php

use App\Model\Company;
use Illuminate\Database\Seeder;

class CompanyTableSeeder extends Seeder
{
    const EMAILFOOTER  = 'Need help? Calls us 1-888-406-2838';
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data['email_header'] = asset('pdf/img/logo-color.png');
        $data['email_footer'] = self::EMAILFOOTER;

        $company = Company::where('id', 1)->first();

        if(!empty($company))
        {
            $company->update($data);
        }
    }
}
