<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $invoice->customer->company->name }}</title>
    <link href="https://teltik.pw/pdf/css/82style.css" type="text/css" rel="stylesheet">

    @include('templates.dynamic-invoice-branding')
</head>

<body>
    <div style='margin-bottom:500px;' class="wrapper">
        <div class="container" style="width: 100%; float: none; margin:0 auto;">
            <div style='position:relative;top:100px;' class="boxmain">
                <div class="head" style="padding:0 0 0;">
                    <div class="containerin">
                        <div class="logo" style="width: 100%; text-align: center;">
                            <img src="{{ isset($invoice->customer->company->logo) ? $invoice->customer->company->logo : '' }}" style="padding: -10px 0 15px 0; width: 200px;" alt="logo">
                        </div>
                        <div style='margin-top:20px' class="invoice">
                            <h2>INVOICE</h2>
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tbody>
                                    <tr>
                                        <td>Invoice No.</td>
                                        <td width="20px"></td>
                                        <td class="detail">{{ $invoice['id'] }}</td>
                                    </tr>
                                    <tr>
                                        <td>Charge Date</td>
                                        <td width="20px"></td>
                                        <td class="detail">@date($invoice['created_at'])</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <br><br><br><br>
                        <!-- Customer Info -->
                        <div style='position:absolute; left:0; right:0; margin: auto; top: 75px; border-color: transparent;' class="linksfooter">
                            <h3>Customer Info</h3>
                            <div class="customer_info" style='margin-top: 5px;'>
                                @if ($invoice->customer->company_name)
                                    <p><span>
                                        {{ $invoice->customer->company_name }},
                                    </span></p>
                                @endif
                                <p><span>{{ $invoice->customer['full_name'] }},</span></p>
                                <p><span>{{ $invoice->customer['shipping_address1'] }}</span></p>
                                <p><span>{{ $invoice->customer['zip_address'] }}</span></p>
                            </div>
                        </div>
                        
                        <div style='position:absolute; right:15px; margin: auto; top: 65px; border-color: transparent; box-shadow:none;' class="bill_info">
                            <h2>Date</h2>
                            <h3 style='margin-top: 10px;'>{{ $invoice->createdAtFormatted }}</h3>
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
                                <p>2. Pay online <a href="{{ $invoice->customer->company->url }}">{{ $invoice->customer->company->url_formatted }}</a> </p>
                            </div>
                    </div>
                </div>
                <br><br><br><br>
                <div class="account_info">
                    <div class="containerin">
                        <center>Invoice Summary</center>
                        <table>
                            <thead>
                                <tr>
                                    <th>Payment Mode</th>
                                    <th>Amount</th>
                                    <th>Total Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>

                            <tr class="tfootQ">
                                <td>Card ending in XXXXX{{ $invoice->paymentLog->last4 }}</td>
                                <td>{{ number_format($invoice->subtotal, 2) }}</td>
                                <td>{{ number_format($invoice->subtotal, 2) }}</td>
                                <td>{{ $invoice->createdAtFormatted }}</td>
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
                                    <a href="#">{{ $invoice->customer->company->support_phone_formatted ? 'Contact us: &nbsp;'. $invoice->customer->company->support_phone_formatted : '' }}</a>
                                    <a href="{{ $invoice->customer->company->url }}">{{ $invoice->customer->company->url_formatted }}</a>    
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

    