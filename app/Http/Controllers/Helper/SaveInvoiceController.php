<?php

namespace App\Http\Controllers\Helper;

use PDF;
use Validator;
use App\Model\Invoice;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;
use App\Http\Controllers\Api\V1\Traits\InvoiceTrait;

class SaveInvoiceController extends BaseController
{
	use InvoiceTrait;

	const INVOICE_TEMPLATE = [
        'custom charge'   => 'custom-charge-invoice',
    ];

    public function saveInvoice(Request $request)
    {
        $data=$request->validate([
            'invoiceId'    => 'required|exists:invoice,id',
            'invoiceType'  => 'required',
        ]);


        $fileSavePath = public_path().'/uploads/non-order-invoice-pdf/'.md5($request->invoiceId);

        $invoice = Invoice::where('id', $data['invoiceId'])->with('customer', 'invoiceItem')->first();


        $pdf = PDF::loadView('templates/'.self::INVOICE_TEMPLATE[$data['invoiceType']], compact('invoice'));

        $url = $this->saveInvoiceFile($pdf, $fileSavePath);

        return $this->respond($fileSavePath.'.pdf');
    }
}
