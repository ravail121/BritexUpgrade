<?php

namespace App\Http\Controllers\Api\V1;

use App\Model\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Model\BusinessVerification;
use App\Model\BusinessVerificationDocs;
use App\Http\Controllers\BaseController;
use App\Events\BusinessVerificationCreated;
use App\Events\BusinessVerificationApproved;

/**
 * Class BizVerificationController
 *
 * @package App\Http\Controllers\Api\V1
 */
class BizVerificationController extends BaseController
{

	/**
	 * BizVerificationController constructor.
	 */
	public function __construct()
    {
        $this->content = array();
    }

	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse|Response
	 */
	public function post(Request $request)
    {
        $order = $this->getOrderId($request->order_hash);
        $request->headers->set('authorization', $order->company->api_key);
        $hasError = $this->validateData($request);

        if ($hasError) {
            return $hasError;
        }

        
        if (!$order) {
            return $this->respondError('Oops! Something, went wrong...');
        }
        $orderId = $order->id;

        $dataWithoutDocs = $request->except(['order_hash','doc_file']) + [
            'hash'     => sha1(time()),
            'order_id' => $orderId,
            // 'approved' => 1,
        ];


        $businessVerification = BusinessVerification::where('order_id', $orderId)->where('approved', 0)->first();

        if ($businessVerification) {
            $businessVerification->update($dataWithoutDocs);

        } else {
            $businessVerification = BusinessVerification::create($dataWithoutDocs);
            event(new BusinessVerificationCreated($request->order_hash, $businessVerification->hash));
        }
        
        
        if($request->hasFile('doc_file')) {
            $uploadedAndInserted = $this->uploadAndInsertDocument($request->doc_file, $businessVerification);

            if (!$uploadedAndInserted) {
                return $this->respondError('File Could not be uploaded.');
            }
        }
        return $this->respond(['order_hash' => $request->order_hash]);
    }
    
    /**
     * Approves the Business of Customer
     * 
     * @param  Request    $request
     * @return Response
     */
    public function approveBusiness(Request $request) 
    {
        $msg = 'Something went wrong';

        $businessVerification = BusinessVerification::where('hash', $request->business_hash)->first();

        if($businessVerification) {
            if($businessVerification->approved == 0) {

                $response = $businessVerification->update(['approved' => 1]);
                event(new BusinessVerificationApproved($businessVerification->hash));

                if ($response) {
                    $msg = 'Approved Successfully';
                }

            } else {
                $msg = 'Business is already verified';
            }

        } else {
            $msg = 'Invalid User';
        }

        return $this->respond(['message' => $msg]); 
    }

    /**
     * Fetches the Order-id
     * @param  String        $orderHash
     * @return int
     */
    protected function getOrderId($orderHash)
    {
        return Order::hash($orderHash)->first();
    }

	/**
	 * Validates the Business Verification Data
	 * @param $request
	 *
	 * @return false|\Illuminate\Http\JsonResponse
	 */
    protected function validateData($request)
    {
        return $this->validate_input($request->all(), [
		   'fname'         => 'required|string',
		   'lname'         => 'required|string',
		   'email'         => 'required|string',
		   'business_name' => 'required|string',
		   //'tax_id'        => 'sometimes|string',
		   //'doc_file'      => 'file|max:500000',
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
        event(new BusinessVerificationCreated($orderHash, $order->bizVerification->hash));

        return $this->respond(['email' => $order->bizVerification->email]);   
    }

	/**
	 * Removes document from database
	 * @param $id
	 *
	 * @return \Illuminate\Http\JsonResponse
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