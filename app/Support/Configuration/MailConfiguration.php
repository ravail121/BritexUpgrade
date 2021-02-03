<?php

namespace App\Support\Configuration;

use Config;
use App\Model\Company;
use Illuminate\Support\Facades\Log;
/**
 * Trait MailConfiguration
 *
 * @package App\Support\Configuration
 */
trait MailConfiguration
{
    /**
     * This method sets the Configuration of the Mail according to the Company
     *
     * @param $data
     * @return boolean
     */
    protected function setMailConfiguration($data)
    {
        $company = Company::find($data->company_id ?: $data->id);
        $config = [
            'driver'        => $company->smtp_driver,
            'host'          => $company->smtp_host,
            'port'          => $company->smtp_port,
            'username'      => $company->smtp_username,
            'password'      => $company->smtp_password,
            'encryption'    => $company->smtp_encryption,
        ];

	    Log::info('setMailConfiguration');
	    Log::info($company->id);
	    Log::info($config);

	    Config::set('mail', $config);
        return false;
    }

	/**
	 * @param $companyId
	 *
	 * @return false
	 */
	public function setMailConfigurationById($companyId)
    {
        $company = Company::find($companyId);
        $config = [
            'driver'        => $company->smtp_driver,
            'host'          => $company->smtp_host,
            'port'          => $company->smtp_port,
            'username'      => $company->smtp_username,
            'password'      => $company->smtp_password,
            'encryption'    => $company->smtp_encryption,
        ];
	    Log::info('setMailConfigurationById');
	    Log::info($company->id);
	    Log::info($config);

	    Config::set('mail', $config);
        return false;
    }

}