<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Teltik</title>
    <link href="https://teltik.pw/pdf/css/82style.css" type="text/css" rel="stylesheet">

</head>

<body>
    <div style='margin-bottom:500px;' class="wrapper">
        <div class="container" style="width: 100%; float: none; margin: 0px auto;">
            <div style='position:relative;top:100px;' class="boxmain">
                <div class="head" style="padding: 0px 0px 0px;">
                    <div class="containerin">
                        <div class="logo" style="width: 100%; text-align: center;">
                            <img src="{{ isset($invoice->customer->company->logo) ? $invoice->customer->company->logo : '' }}" style="padding: -10px 0px 15px 0px; width: 200px;" alt="logo">
                        </div>
                        <div style='margin-top:20px' class="invoice">
                            <h2>REFUND</h2>
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tbody>
                                    <tr>
                                        <td>Invoice No.</td>
                                        <td width="20px"></td>
                                        <td class="detail">{{ $invoice['id'] }}</td>
                                    </tr>
                                    <tr>
                                        <td>Period Beginning</td>
                                        <td width="20px"></td>
                                        <td class="detail">@date($invoice['start_date'])</td>
                                    </tr>
                                    <tr>
                                        <td>Period Ending</td>
                                        <td width="20px"></td>
                                        <td class="detail">@date($invoice['end_date'])</td>
                                    </tr>
                                    <tr>
                                        <td>Due Date</td>
                                        <td width="20px"></td>
                                        <td class="detail">@date($invoice['due_date'])</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <br><br><br><br>
                        <!-- Customer Info -->
                        <div style='position:absolute; left:0; right:0; margin: auto; top: 100px; border-color: transparent;' class="linksfooter">
                            <h3>Customer Info</h3>
                            <div class="customer_info">
                                <p><span>{{ $invoice->customer['company_name'] }},</span></p>
                                <p><span>{{ $invoice->customer['full_name'] }},</span></p>
                                <p><span>{{ $invoice->customer['shipping_address1'] }}</span></p>
                                <p><span>{{ $invoice->customer['zip_address'] }}</span></p>
                            </div>
                        </div>
                        
                        <div style='position:absolute; right:15px; margin: auto; top: 100px; border-color: transparent; box-shadow:none;' class="bill_info">
                            <h2>Refund Date</h2>
                            <h3>{{ $invoice->createdAtFormatted }}</h3>
                        </div>
                    
                        <div class="info">
                                <h2>Important Information</h2>
                                <p>1. You are 
                                    <strong>
                                        @if ($invoice->customer['auto_pay'])
                                        
                                        @else 
                                            not
                                        @endif
                                    </strong> 
                                    enrolled in Autopay. Amount will 
                                    <strong>
                                        @if ($invoice->customer['auto_pay'])
                                    
                                        @else 
                                            not
                                        @endif    
                                    </strong> be forwarded for automatic processing.</p>
                                <p>2. Pay online <a href="{{ isset($invoice['reseller_domain']) ? $invoice['reseller_domain'] : '' }}">teltik.pw</a></p>
                            </div>
                    </div>
                </div>
                <br><br><br><br>
                <div class="account_info">
                    <div class="containerin">
                        <center>Refund Summary</center>
                        <table>
                            <thead>
                                <tr>
                                    <th>Payment Mode</th>
                                    <th>Refund Amount</th>
                                    <th>Total Amount</th>
                                    <th>Refund Date</th>
                                </tr>
                            </thead>
                            <tbody>

                            <tr class="tfootQ">
                                <td>CARD {{-- {{ $paymentRefundLog->paymentLog->paymentMode }} --}}</td>
                                <td>$ {{ $paymentRefundLog->amount }}</td>
                                <td>$ {{ $paymentRefundLog->paymentLog->amount }}</td>
                                <td>{{ $paymentRefundLog->createdAtFormatted }}</td>
                            </tr>
                            </tbody>
                            <tr>
                                <td colspan="7" class="lh0">
                                    <div class="total_img">
                                        <img src="https://teltik.pw/pdf/img/shape.png" alt="">
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="7" class="lh0">
                                    <div class="total_img2">
                                        <img src="https://teltik.pw/pdf/img/shape.png" alt="shape">
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="7">&nbsp;</td>
                            </tr>
                        </table>
                        <div class="footer">
                            <div class="container">
                                <div class="center">
                                    <a href="#">Contact us: {{ isset($invoice['reseller_phone_number']) ? $invoice['reseller_phone_number'] : '' }}</a>
                                    <a href="{{ isset($invoice['reseller_phone_number']) ? $invoice['reseller_phone_number'] : '' }}">teltik.pw</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>

    