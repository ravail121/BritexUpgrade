<?php

namespace App\Http\Controllers\Api\V1\CronJobs;

use App\Http\Controllers\BaseController;
use App\Model\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Events\ReportNullSubscriptionStartDate;
use App\Events\SendMailData;
use App\Model\InvoiceItem;

/**
 * Class ProcessController
 *
 * @package App\Http\Controllers\Api\V1\CronJobs
 */
class checkInvoice extends BaseController
{
	/**
	 * @param Request $request
	 *
	 * @return \Illuminate\Http\JsonResponse
	 */
	
	public function check()
    {
        $arr=array();
        
        try{
            // $invoices=Invoice::withSum(['invoiceItem as sumtotal' => function($query) {
            //     $query->where('type','!=', 6)->where('type','!=', 10);
            // }], 'amount')->withSum(['invoiceItem as sumcoupon' => function($query) {
            //     $query->where('type', 6);
            // }], 'amount')->withSum('creditToInvoice as sumpaid' , 'amount')->get();


            $invoices = Invoice::all();


            foreach($invoices as $invoice){


                $sumtotal=InvoiceItem::where('invoice_id',$invoice->id)->where('type','!=', 6)->where('type','!=', 10)->sum('amount');
                $sum=InvoiceItem::where('invoice_id',$invoice->id)->where('type', 6)->sum('amount');
                $invoice->sumtotal=$sumtotal;
                $invoice->sumcoupon=$sum;

                if(($invoice->sumtotal - $invoice->sumcoupon) !=  $invoice['subtotal']){

                   array_push($arr,$invoice);

                }

            }
    
    

			// $invoiceCount = $invoices->count();
              //dd($arr);

			if(count($arr)) {
				event( new SendMailData($arr) );
			}
		} catch (\Exception $e) {
			\Log::info($e->getMessage(). ' on the line '. $e->getLine());
		}

    }
}
