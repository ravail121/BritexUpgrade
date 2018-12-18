<?php

namespace App\Http\Controllers\Api\V1;

use Validator;
use App\Model\Order;
use App\Model\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use App\Model\BusinessVerification;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use App\Model\BusinessVerificationDocs;
use App\Http\Controllers\BaseController;
use App\Events\BusinessVerificationCreated;
use App\Events\BusinessVerificationApproved;

class BizVerificationController extends BaseController
{ 

	public function __construct()
	{
		$this->content = array();

	}

	public function post(Request $request)
	{
		$hasError = $this->validateData($request);

        if ($hasError) {
            return $hasError;
        }

		$order = $this->getOrderId($request->order_hash);
        if (!$order) {
            return $this->respondError('Oops! Something, went wrong...');
        }
        $orderId = $order->id;

		$dataWithoutDocs = $request->except(['order_hash','doc_file']) + [
			'hash'     => sha1(time()),
			'order_id' => $orderId,
		];

        $businessVerification = BusinessVerification::updateOrCreate(['order_id' => $orderId], $dataWithoutDocs);

		
		if($request->hasFile('doc_file')) {
            $uploadedAndInserted = $this->uploadAndInsertDocument($request->doc_file, $businessVerification);

            if ($uploadedAndInserted) {
                return $this->respondError('File Could not be uploaded.');

            }
		}
				
        // event(new BusinessVerificationCreated($orderHash,$bizHash));

		event(new BusinessVerificationApproved($request->order_hash, $businessVerification->hash));
			
		return $this->respond(['order_hash' => $request->order_hash]);
	}


	public function confirm(Request $request) {

  		$orderHash = Order::hash($request->orderHash)->first();
		$bizHash   = BusinessVerification::where('hash', $request->businessHash)->first();

		if($orderHash && $bizHash) {
			if($bizHash->approved == 0) {
				BusinessVerification::where('hash', $request->businessHash)->update(['approved' => 1]);    
			}

		} else {
            return $this->respondError("Invalid User");
		}

		return redirect()->to(env('CHECKOUT_URL').$request->businessHash.'&order_hash='.$orderHash); 
	}



    /**
     * Fethces the Order-id
     * @param  String        $orderHash
     * @return int
     */
    protected function getOrderId($orderHash)
    {
        return Order::hash($orderHash)->first();
    }




    /**
     * Validates the Business Verification Data
     * @param  Request      $request
     * @return Response
     */
	protected function validateData($request)
	{
		return $this->validate_input($request->all(), [
		   'fname'         => 'required|string',
		   'lname'         => 'required|string',
		   'email'         => 'required|string',
		   'business_name' => 'required|string',
		   'tax_id'        => 'required|numeric',
		   'doc_file'      => 'required|file',
		   'order_hash'    => 'required|string',
		]);
	}



    /**
     * Uploads the document to desired path and inserts the file name to database
     * @param  String        $file                  [doc-file]
     * @param  \App\BusinessVerification    $businessVerification
     * @return boolean
     */
    protected function uploadAndInsertDocument($file, $businessVerification)
    {
        $path = BusinessVerificationDocs::directoryLocation($businessVerification->order->company_id, $businessVerification->id);

        // if (!File::makeDirectory($path, $mode = 0777, true, true)) {
        //     return true;
        // }

        \Log::info(File::makeDirectory($path, $mode = 0777, true, true));

        if ($uploaded = $this->moveOneFile($path, $file)) {
            return BusinessVerificationDocs::create([
                'src'        => $uploaded['name'],
                'bus_ver_id' => $businessVerification->id,
            ]);
        }

        return false;
    }



}