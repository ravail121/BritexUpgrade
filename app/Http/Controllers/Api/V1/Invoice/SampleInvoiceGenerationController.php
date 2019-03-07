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
    public function getInvoice()
    {
        $pdf = PDF::loadView('templates/test-invoice')->setPaper('letter', 'portrait');
        return $pdf->download('invoice.pdf');
    }




    /**
     * Generates the Invoice template and downloads the invoice.pdf file
     * 
     * @param  Request    $request
     * @return Response
     */
    public function getStatement()
    {
        $view1 = view('templates.test-statement');
        $view2 = view('templates.test-statement-2');

        $pdf = PDF::loadHTML($view1 . $view2)->setPaper('letter', 'portrait');
        return $pdf->download('statement.pdf');
    }

}
