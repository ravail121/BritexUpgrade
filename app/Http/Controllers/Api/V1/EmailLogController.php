<?php

namespace App\Http\Controllers\Api\V1;

use App\Model\EmailLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

class EmailLogController extends Controller
{
    public function store(Request $request) 
    {
    	$data = $this->validateData($request);

        if (!$data) {
            
            return $this->respondError($data);
        }     

        $emailLog = EmailLog::create($data);  

        return $emailLog;
    }

    public function validateData(Request $request)
    {
        $data=$request->validate([
            'company_id'               => '',
            'customer_id'              => 'required',
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
