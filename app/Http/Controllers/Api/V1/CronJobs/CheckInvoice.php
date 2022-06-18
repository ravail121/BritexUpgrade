<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use App\Model\Invoice;
use App\Model\InvoiceItem;
use Illuminate\Http\Request;
use App\Events\SendMailData;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\Api\V1\Traits\CronLogTrait;

/**
 * Class ProcessController
 *
 * @package App\Http\Controllers\Api\V1\CronJobs
 */
class CheckInvoice extends BaseController
{
	use CronLogTrait;
	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	
	public function check()
    {
        $arr = [];
        
        try{
            $invoices = Invoice::all();

            foreach($invoices as $invoice){


                $sumtotal = InvoiceItem::where('invoice_id', $invoice->id)->where('type', '!=', 6)->where('type', '!=', 10)->sum('amount');
                $sum = InvoiceItem::where('invoice_id', $invoice->id)->where('type', 6)->sum('amount');
                $invoice->sumtotal = $sumtotal;
                $invoice->sumcoupon = $sum;

                if(round(($invoice->sumtotal - $invoice->sumcoupon), 2) !=  $invoice['subtotal']){
                   $arr[] = $invoice;
                }
            }

			if(count($arr)) {
				event( new SendMailData($arr) );
			}
	        $logEntry = [
		        'name'      => 'Check Invoice',
		        'status'    => 'success',
		        'payload'   => json_encode($arr),
		        'response'  => 'Invoice Checked'
	        ];

	        $this->logCronEntries($logEntry);
		} catch (\Exception $e) {
	        $logEntry = [
		        'name'      => 'Check Invoice',
		        'status'    => 'error',
		        'payload'   => '',
		        'response'  => $e->getMessage(). ' on the line '. $e->getLine()
	        ];

	        $this->logCronEntries($logEntry);
			\Log::info($e->getMessage(). ' on the line '. $e->getLine());
		}
    }
}
