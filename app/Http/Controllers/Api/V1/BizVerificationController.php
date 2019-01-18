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
            'approved' => 1,
        ];


        $businessVerification = BusinessVerification::where('order_id', $orderId)->where('approved', 1)->first();

        if ($businessVerification) {
            $businessVerification->update($dataWithoutDocs);

        } else {
            $businessVerification = BusinessVerification::create($dataWithoutDocs);
            \Log::info('Send Email Event Triggered....');
            event(new BusinessVerificationApproved($request->order_hash, $businessVerification->hash));
        }


        if($request->hasFile('doc_file')) {
            $uploadedAndInserted = $this->uploadAndInsertDocument($request->doc_file, $businessVerification);

            if (!$uploadedAndInserted) {
                return $this->respondError('File Could not be uploaded.');
            }
        }
                
        // event(new BusinessVerificationCreated($orderHash,$bizHash));
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

        if ($uploaded = $this->moveOneFile($path, $file)) {
            return BusinessVerificationDocs::create([
                'src'        => $uploaded['name'],
                'bus_ver_id' => $businessVerification->id,
            ]);
        }

        return false;
    }



    /**
     * This function resends email for business_verification
     * 
     * @param  Request    $request
     * @return Response
     */
    public function resendBusinessVerificationEmail(Request $request)
    {
        $orderHash = $request->order_hash;

        $order = Order::hash($orderHash)->first();
        if (!$order->bizVerification) {
            return $this->respond(['false' => false]);

        }
        event(new BusinessVerificationApproved($orderHash, $order->bizVerification->hash));

        return $this->respond(['email' => $order->bizVerification->email]);   
    }



    /**
     * Removes document from database
     * 
     * @param  int       $id
     * @return Response
     */
    public function removeDocument($id)
    {
        
        $validate = $this->validate_input(compact('id'), [
           'id' => 'exists:business_verification_doc',
        ]);

        if($validate){
            return $validate;
        }

        BusinessVerificationDocs::destroy($id);

        return $this->respond(['message' => 'business document removed']);
    }



}