<?php

namespace App\Http\Controllers\Api\V1;

use App\Model\EmailLog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

/**
 * Class EmailLogController
 *
 * @package App\Http\Controllers\Api\V1
 */
class EmailLogController extends Controller
{
	/**
	 * @param Request $request
	 *
	 * @return mixed
	 */
	public function store(Request $request)
    {
    	$data = $this->validateData($request);

        if (!$data) {
            return $this->respondError($data);
        }     

        $emailLog = EmailLog::create($data);  

        return $emailLog;
    }

	/**
	 * @param Request $request
	 *
	 * @return mixed
	 */
	public function validateData(Request $request)
    {
        $data = $request->validate([
            'company_id'               => '',
            'customer_id'              => '',
            'staff_id'                 => '',
            'business_verficiation_id' => '',
            'type'                     => '',
            'from'                     => 'required',
            'to'                       => 'required',
            'subject'                  => 'required',
            'body'                     => 'required',
            'notes'                    => '',
            'reply_to'                 => '',
            'cc'                       => '',
            'bcc'                      => '',
        ]);

        return $data;
    }
}
