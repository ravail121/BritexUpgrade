<?php

namespace App\Http\Controllers\Api\V1\Invoice;

use PDF;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SampleInvoiceGenerationController extends Controller
{
      /**
     * Generates the Invoice template and downloads the invoice.pdf file
     * 
     * @param  Request    $request
     * @return Response
     */
    public function get()
    {
        $pdf = PDF::loadView('templates/test-invoice')->setPaper([0,0,950,950], 'landscape');
        return $pdf->stream('invoice.pdf');
        // return view('templates.test-invoice');
    }
}
