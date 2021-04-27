<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $invoice->customer->company->name }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css?family=Montserrat:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i');
        @import url('https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i,800,800i');

        @font-face {
            font-family: 'Avenir LT Std';
            src: url('https://teltik.pw/pdf/fonts/AvenirLTStd-Black.eot');
            src: url('https://teltik.pw/pdf/fonts/AvenirLTStd-Black.eot?#iefix') format('embedded-opentype'),
            url('https://teltik.pw/pdf/fonts/AvenirLTStd-Black.woff2') format('woff2'),
            url('https://teltik.pw/pdf/fonts/AvenirLTStd-Black.woff') format('woff'),
            url('https://teltik.pw/pdf/fonts/AvenirLTStd-Black.ttf') format('truetype'),
            url('https://teltik.pw/pdf/fonts/AvenirLTStd-Black.svg#AvenirLTStd-Black') format('svg');
            font-weight: 900;
            font-style: normal;
        }

        @font-face {
            font-family: 'Avenir LT Std';
            src: url('https://teltik.pw/pdf/fonts/AvenirLTStd-Medium.eot');
            src: url('https://teltik.pw/pdf/fonts/AvenirLTStd-Medium.eot?#iefix') format('embedded-opentype'),
            url('https://teltik.pw/pdf/fonts/AvenirLTStd-Medium.woff2') format('woff2'),
            url('https://teltik.pw/pdf/fonts/AvenirLTStd-Medium.woff') format('woff'),
            url('https://teltik.pw/pdf/fonts/AvenirLTStd-Medium.ttf') format('truetype'),
            url('https://teltik.pw/pdf/fonts/AvenirLTStd-Medium.svg#AvenirLTStd-Medium') format('svg');
            font-weight: 500;
            font-style: normal;
        }


        h1, h2, h3, h4, h5, p {
            margin: 0px;
        }
        body {
            font-family: 'Montserrat', sans-serif;
            margin: 0 auto;
        }
        .wrapper {
            width: 100%;
        }
        .boxmain{
            background: white/*#e7e5e8 url(http://teltik.pw/public/pdf/img/mainbg.jpg) top repeat-x*/;
            width: 100%;
            /*float: left;*/
        }
        /*.head {
            padding: 0px 0px 0px;
        }*/
        /*.container {
            width: 800px;
            float: none;
            margin: 0px auto;
            max-width: 100%;
        }*/
        .containerin {
            padding: 0 20px;
        }


        /*.logo {
            width: 100%;
            float: left;
            text-align: center;
        }*/
        /*.logo img {
            padding: 20px 0px;
            width: 200px;
        }*/
        .invoice {
            width: 30%;
            box-sizing: border-box;
            position: relative;
            /*width: auto;*/
            /*float: left;*/
        }
        .invoice h2 {
            font-size: 30px;
            font-weight: 700;
            padding: 0px 0px 5px;
        }
        .invoice td {
            font-size: 12px;
            font-weight: 700;
            line-height: 24px;
            color: #373737;
        }
        .invoice table .detail {
            font-size: 12px;
            font-weight: 400;
            color: #373737;
        }
        .head .bill_info {
            width: 32%;
            box-sizing: border-box;
            float: right;
            margin-top: -140px;
            text-align: center;
            border: 2px dashed #4c00ac;
            border-radius: 20px;
            padding: 30px 0px;
            background: #fff;
            box-shadow: 32.192px 60.916px 131px 0px rgba(4, 7, 11, 0.16);
        }
        .head .bill_info h2 {
            font-size: 14px;
            font-weight: 700;
            padding: 0px 0px 5px;
            color: #373737;
        }
        .head .bill_info h3 {
            font-size: 32px;
            font-weight: 700;
            color: #373737;
        }
        .info {
            width: 100%;
            clear: both;
        }
        .info h2 {
            font-size: 14px;
            font-weight: 700;
            padding: 30px 0px 20px;
        }
        .info p {
            font-size: 12px;
            font-weight: 500;
            line-height: 24px;
        }
        .info p a {
            font-size: 12px;
            font-weight: 700;
            line-height: 24px;
            text-decoration: none;
            color: #373737;
        }
        .billing_detail {
            padding: 15px 0px;
            margin: 40px 0 0 0;
            background: #fff;
            clear: both;
            display: inline-block;
            width: 100%;
        }
        .billing_detail th, td {
            color: #373737;
            font-weight: 700;
        }
        .billing_detail .titlebx h3 {
            font-size: 16px;
            font-weight: 700;
            text-align: left;
        }
        .bill_detail { padding: 30px 0px;}
        .bill_detail td a {
            font-weight: 400;
            float: right;
            margin: 0px 80px;
        }
        .bill_detail td.titlebx:last-child {
            padding: 0;
        }
        .bill_detail td.titlebx {
            font-size: 12px;
            line-height: 36px;
            padding-right: 9px;
        }
        .bill_detail img {
            position: relative;
            left: 612px;
            top: 82px;
        }
        .bill_detail table .detail {
            font-size: 12px;
            font-weight: 400;
            color: #373737;
        }
        .bill_detail table .thankyou {
            font-style: italic;
        }
        .bill_detail table .seprator {
            background: url(https://teltik.pw/pdf/img/bdr.png) -200px 0 no-repeat;
            width: 100%;
            height: 1px;
        }
        .bill_detail table span {
            font-weight: 500;
        }
        .account_info {
            background: #4c00ac;
            padding: 0px 0px 15px 0px;
        }
        .account_info center {
            font-size: 28px;
            color: #FFFFFF;
            font-weight: 700;
            padding: 30px 0;
        }
        .account_info table {
            width: 100%;
            border-collapse: collapse;
        }
        .account_info .tfoot {
            border-top: 1px solid #FFFF !important;
            border-bottom: 1px solid #FFFF !important;
        }
        .account_info .text-left {
            text-align: left;
            padding: 10px 5px;
        }
        .account_info th {
            color: #FFFFFF;
            font-size: 12px;
            padding: 12px 12px 12px 2px;
            font-weight: 700;
            text-align: center;
        }
        .account_info .center {
            padding: 3px 25px;
            text-align: center;
        }
        .account_info td {
            color: #FFFFFF;
            font-size: 12px;
            font-weight: 500;
            padding: 10px 0px 8px 0px;
            text-align: center;
        }
        .account_info p a {
            color: #FFFFFF;
            font-size: 12px;
            font-weight: 700;
            padding: 10px 0px;
            text-decoration: none;
            line-height: 25px;
        }
        .account_info .total_img img {
            height: 0.3px;
            width: 700px;
        }
        .account_info .total_img2 img {
            height: 0.3px;
            width: 700px
        }
        .lh0 {
            line-height: 0;
            padding: 0 !important;
        }

        .linksfooter {
            width: 30%;
            /*float: left;*/
            margin-left: 215px;
            margin-top: -135px;
            border: 2px dashed #4c00ac;
            border-radius: 18px;
            text-align: center;
            padding: 15px 5px;
        }

        .links {
            width: 34%;
            position: relative;
            float: left;
        }

        .links img {
            height: 100px;
            width: 1px;
        }
        .footer_info {
            width: 34%;
            position: relative;
            float: left;
        }

        .footer_info img {
            height: 100px;
            width: 1px;
        }
        .footer_logo {
            width: 26%;
            float: left;
        }
        .footer_logo img {
            width: 180px;
            margin: 20px 0px;
        }
        .footer {
            background: #420590;
            /* float: left; */
            padding: 12px 0px;
        }
        .footer .center a {
            color: #FFFFFF;
            font-size: 12px;
            font-weight: 700;
            line-height: 30px;
            text-decoration: none;
        }
        .footer .center a:first-child {
            margin-right: 10px;
        }
        .titlebox {
            width: 33.3%;
            float: left;
        }
        .titlebx {
            width: 33.3%;
        }
        /*Account*/
        .clear {
            clear:both;
        }
        .table-padding{
            padding: 0px 20px;
        }

        .header {
            background: #d7dcec;
            padding: 30px 0px;
            margin: 0px 0px 70px;
            width: 100%;
        }
        .header .logo {
            width: 0%;
            box-sizing: border-box;
            float: left;
        }
        .header img {
            width: 200px;
            padding: 0px 20px;
        }
        .header .statement {
            width: 26%;
            box-sizing: border-box;
            float: right;
        }
        .header .statement p {
            font-size: 14px;
            font-weight: 700;
        }
        .header .statement h2 {
            font-size: 24px;
            font-weight: 400;
        }
        .account {
            border-bottom: 5px solid #8b00da;
        }
        .account h2 {
            font-family: 'Avenir LT Std';
            font-style: normal;
            font-weight: normal;
            font-size: 22px;
            padding: 0 0 20px 0;
        }

        .account img {
            margin: 0px 40px 0px 0px;
            width: 100%;
        }
        .base_charges h2 {
            font-family: 'Avenir LT Std';
            font-style: normal;
            font-weight: normal;

            font-size: 22px;
            padding: 60px 0px 20px;
        }
        .tables table td {
            font-family: 'Avenir LT Std 65 Medium';
            font-style: normal;
            font-weight: normal;
            font-family: 'Avenir LT Std';
            font-size: 14px;
            padding: 10px 0px;
            color: #000000;
        }
        .tables td a {
            font-family: 'Avenir LT Std';
            font-style: normal;
            font-weight: normal;
            font-size: 15px;
            margin-left: 10px;
            color: #000000;
        }
        .tables table .right {
            text-align: right;
            color: #000000;
        }
        .tables img {
            width: 100%;
        }
        .feature h2 {
            font-family: 'Avenir LT Std';
            font-style: normal;
            font-weight: normal;

            font-size: 22px;
            padding: 0px 0px 5px;
        }
        .feature table .right {
            text-align: right;
            color: #000000;
        }
        .tables table .last {
            text-align: right;
        }
        .tables table {
            width: 100%;
        }
        .one_time h2 {
            font-family: 'Avenir LT Std';
            font-style: normal;
            font-weight: normal;
            font-size: 22px;
            padding: 0px 0px 5px;
        }
        .taxes h2 {
            font-family: 'Avenir LT Std';
            font-style: normal;
            font-weight: normal;

            font-size: 22px;
            padding: 0px 0px 5px;
        }
        .taxes table .right {
            text-align: right;
            color: #000000;
        }
        .credits h2 {
            font-family: 'Avenir LT Std';
            font-style: normal;
            font-weight: normal;

            font-size: 22px;
            padding: 0px 0px 5px;
        }
        .total {
            background: #000000;
        }
        .total table td {
            font-family: 'Avenir LT Std';
            font-style: normal;
            font-weight: normal;

            font-size: 18px;
            color: #FFFFFF;
            padding: 10px 20px;
        }
        .total table .right {
            text-align: right;
            font-size: 22px;
            color: #FFFFFF;
        }
        .subscriber {
            border-bottom: 5px solid #8b00da;
            padding: 5px 0px;
            margin-bottom: 20px;
        }
        .sepratorline{
            height: 1px;
            background: #000;
            margin-bottom: 10px;
        }
        .sepratorline.dark {
            height: 2px;
        }
        .subscriber table td {
            font-family: 'Avenir LT Std';
            font-style: normal;
            font-weight: normal;
            font-size: 22px;
            color: #000000;
        }
        .subscriber table .nmbr {
            text-align: right;
            font-size: 20px;
            color: #000000;
        }
        .plan_base h2 {
            font-family: 'Avenir LT Std';
            font-style: normal;
            font-weight: normal;

            font-size: 22px;
            position: relative;
            padding: 0px 0px 5px;
        }
        .tables .nmbr a {
            float: right;
        }
        .plan_feature h2 {
            font-family: 'Avenir LT Std';
            font-style: normal;
            font-weight: normal;

            font-size: 22px;
            position: relative;
            padding: 0px 0px 5px;
        }
        .tables h3 {
            font-family: 'Open Sans', sans-serif;
            font-size: 16px;
            font-weight: 400;
            text-align: center;
            padding: 30px 0px 20px;
        }
        .fees h2 {
            font-family: 'Avenir LT Std';
            font-style: normal;
            font-weight: normal;

            font-size: 22px;
            position: relative;
            padding: 0px 0px 5px;
        }
        .usage_charges h2 {
            font-family: 'Avenir LT Std';
            font-style: normal;
            font-weight: normal;

            font-size: 22px;
            position: relative;
            padding: 0px 0px 5px;
        }
        .credit h2 {
            font-family: 'Avenir LT Std';
            font-style: normal;
            font-weight: normal;

            font-size: 22px;
            position: relative;
            padding: 0px 0px 5px;
        }
        .plan_charge h2 {
            font-family: 'Avenir LT Std';
            font-style: normal;
            font-weight: normal;

            font-size: 22px;
            position: relative;
            padding: 0px 0px 5px;
        }
        .credit2 {
            margin: 30px 0px;
        }
        .credit2 h2 {
            font-family: 'Avenir LT Std';
            font-style: normal;
            font-weight: normal;

            font-size: 22px;
            position: relative;
            padding: 0px 0px 5px;
        }
        table.test td {
            padding: 8px 0px;
        }
        .page1 table.test td, .page2 table.test td,  .page3 table.test td {
            padding: 5px 0px;
        }
        .page1 .header, .page2 .header, .page3 .header {
            margin: 0px 0px 20px;
        }
        .total_value a, .total_value {
            font-size: 16px !important;
        }
        .customer_info {
            padding-top: 10px;
        }
        .invoice{
            float: left !important;
        }

        .linksfooter, .bill_info{
            top: 0px !important;
            position: relative !important;
            float: left !important;
        }

        .linksfooter {
            margin-left: 40px !important;
        }
    </style></head>

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

    