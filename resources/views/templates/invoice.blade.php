<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Teltik</title>
    {{-- <link href="{{ asset('pdf/css/81style.css') }}" type="text/css" rel="stylesheet"> --}}
    <style>
        @import url('https://fonts.googleapis.com/css?family=Montserrat:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i');
        @import url('https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i,800,800i');

        @font-face {
            font-family: 'Avenir LT Std';
            src: url('http://teltik.pw/public/pdf/fonts/AvenirLTStd-Black.eot');
            src: url('http://teltik.pw/public/pdf/fonts/AvenirLTStd-Black.eot?#iefix') format('embedded-opentype'),
                url('http://teltik.pw/public/pdf./fonts/AvenirLTStd-Black.woff2') format('woff2'),
                url('http://teltik.pw/public/pdf/fonts/AvenirLTStd-Black.woff') format('woff'),
                url('http://teltik.pw/public/pdf/fonts/AvenirLTStd-Black.ttf') format('truetype'),
                url('http://teltik.pw/public/pdf/fonts/AvenirLTStd-Black.svg#AvenirLTStd-Black') format('svg');
            font-weight: 900;
            font-style: normal;
        }

        @font-face {
            font-family: 'Avenir LT Std';
            src: url('http://teltik.pw/public/pdf/fonts/AvenirLTStd-Medium.eot');
            src: url('http://teltik.pw/public/pdf/fonts/AvenirLTStd-Medium.eot?#iefix') format('embedded-opentype'),
                url('http://teltik.pw/public/pdf/fonts/AvenirLTStd-Medium.woff2') format('woff2'),
                url('http://teltik.pw/public/pdf/fonts/AvenirLTStd-Medium.woff') format('woff'),
                url('http://teltik.pw/public/pdf/fonts/AvenirLTStd-Medium.ttf') format('truetype'),
                url('http://teltik.pw/public/pdf/fonts/AvenirLTStd-Medium.svg#AvenirLTStd-Medium') format('svg');
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
            background: url(http://teltik.pw/public/pdf/img/bdr.png) -200px 0 no-repeat;
            width: 100%;
            height: 1px;
        }
        .bill_detail table span {
            font-weight: 500;
        }
        .account_info {
            background: #4c00ac;
            padding: 0px 0px 15px;
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
            padding: 12px 0px 12px 2px;
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
            padding: 10px 0px 8px;
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
            width: 765px;
        }
        .account_info .total_img2 img {
            height: 0.3px;
            width: 765px
        }
        .lh0 {
            line-height: 0;
            padding: 0 !important;
        }

        .linksfooter {
            width: 32%;
            /*float: left;*/
            margin-left: 225px;
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

    </style>
</head>

<body>
    <div class="wrapper">
        <div class="container" style="width: 800px; float: none; margin: 0px auto;">
            <div class="boxmain">
                <div class="head" style="padding: 0px 0px 0px;">
                    <div class="containerin">
                        <div class="logo" style=" width: 100%; text-align: center;">
                            <img src="http://teltik.pw/public/pdf/img/logo.png" style="padding: 20px 0px; width: 200px;" alt="logo">
                        </div>
                        <div class="invoice">
                            <h2>INVOICE</h2>
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tbody>
                                    <tr>
                                        <td>Invoice No.</td>
                                        <td width="20px"></td>
                                        <td class="detail">####</td>
                                    </tr>
                                    <tr>
                                        <td>Period Beginning</td>
                                        <td width="20px"></td>
                                        <td class="detail">{{ str_replace('-', '/', $invoice['start_date']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Period Ending</td>
                                        <td width="20px"></td>
                                        <td class="detail">{{ str_replace('-', '/', $invoice['end_date']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Due Date</td>
                                        <td width="20px"></td>
                                        <td class="detail">{{ str_replace('-', '/', $invoice['due_date']) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Customer Info -->
                        <div class="linksfooter">
                            <h3>Customer Info</h3>
                            <div class="customer_info">
                                <p><span>First Lastname</span></p>
                                <p><span>PO Box 555</span></p>
                                <p><span>Roadville, NY 87879</span></p>
                            </div>
                        </div>
                        
                        <div class="bill_info">
                            <h2>Your Monthly Bill As Of</h2>
                            <h3>{{ $invoice['today_date'] }}</h3>
                        </div>
                        <div class="info">
                            <h2>Important Information</h2>
                            <p>1. You are <strong>not</strong> enrolled in Autopay. Amount will <strong>not</strong> be forwarded for automatic processing.</p>
                            <p>2. Pay online <a href="http://www.ResellerDomain.com">ResellerDomain.com</a></p>
                        </div>
                    </div>
                    <div class="billing_detail">
                        <div class="containerin">
                            <div class="titlebox">
                                <h3>Last Bill</h3>
                            </div>
                            <div class="titlebox">
                                <h3>Current Bill</h3>
                            </div>
                            <div class="titlebox">
                                <h3>Total Amount Due</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bill_detail">
                    <div class="containerin">
                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tbody>
                                <tr>
                                    <td class="titlebx">
                                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                            <tbody>
                                                <tr>
                                                    <td>Previous Balance</td>
                                                    <td class="detail">$106.46</td>
                                                </tr>
                                                <tr>
                                                    <td>Payments Received </td>
                                                    <td class="detail">-{{ $invoice['subtotal'] }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="thankyou" colspan="2">
                                                        <div class="seprator"></div>
                                                        Thank you!
                                                        <div class="seprator"></div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Balance Forward</td>
                                                    <td class="detail">$0.00</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                    <td class="titlebx">
                                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                            <tbody>
                                                <tr>
                                                    <td>Services, Usage &amp; Charges</td>
                                                    <td class="detail">$90.00</td>
                                                </tr>
                                                <tr>
                                                    <td>Fees/Taxes</td>
                                                    <td class="detail">$16.46</td>
                                                </tr>
                                                <tr>
                                                    <td>Credits</td>
                                                    <td class="detail">$0.00</td>
                                                </tr>
                                                <tr>
                                                    <td>Total Charges This Bill</td>
                                                    <td class="detail">{{ $invoice['subtotal'] }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                    <td class="titlebx">
                                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                            <tbody>
                                                <tr>
                                                    <td>Payments (Mar 1)</td>
                                                    <td class="detail">$90.00</td>
                                                </tr>
                                                <tr>
                                                    <td>Due Mar 1</td>
                                                    <td class="detail">$0.00</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <div class="seprator"></div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2">Let’s talk!<span> Call us anytime</span></td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2">Reseller Phone Number</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="account_info">
                    <div class="containerin">
                        <center>Account Summary</center>
                        <table>
                            <thead>
                                <tr>
                                    <th>Phone No.</th>
                                    <th>Plan Charges</th>
                                    <th>One Time Charges</th>
                                    <th>Usage Charges</th>
                                    <th>Taxes/Fees</th>
                                    <th>Credits</th>
                                    <th>Total Current Charges</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Account Charges</td>
                                    <td>$50.00</td>
                                    <td>$0.00</td>
                                    <td>$0.00</td>
                                    <td>$0.00</td>
                                    <td>$0.00</td>
                                    <td>$50.00</td>
                                </tr>
                                <tr>
                                    <td>863-666-9879</td>
                                    <td>$30.00</td>
                                    <td>$0.00</td>
                                    <td>$0.00</td>
                                    <td>$5.49</td>
                                    <td>$0.00</td>
                                    <td>$35.49</td>
                                </tr>
                                <tr>
                                    <td>863-666-9878</td>
                                    <td>$40.00</td>
                                    <td>$0.00</td>
                                    <td>$0.00</td>
                                    <td>$6.15</td>
                                    <td>$0.00</td>
                                    <td>$46.15</td>
                                </tr>
                                <tr>
                                    <td>863-666-9877</td>
                                    <td>$20.00</td>
                                    <td>$0.00</td>
                                    <td>$0.00</td>
                                    <td>$4.83</td>
                                    <td>$0.00</td>
                                    <td>$24.83</td>
                                </tr>
                            </tbody>
                            <tr>
                                <td colspan="7" class="lh0">
                                    <div class="total_img">
                                        <img src="http://teltik.pw/public/pdf/img/shape.png" alt="shape">
                                    </div>
                                </td>
                            </tr>
                            
                            <tr class="tfootQ">
                                <td>Total</td>
                                <td>$90</td>
                                <td>$0.00</td>
                                <td>$0.00</td>
                                <td>$16.46</td>
                                <td>$0.00</td>
                                <td>$106.46</td>
                            </tr>
                            <tr>
                                <td colspan="7" class="lh0">
                                    <div class="total_img2">
                                        <img src="http://teltik.pw/public/pdf/img/shape.png" alt="shape">
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
                                    <a href="#">Contact us: 1-800-555-1212</a>
                                    <a href="#">ResellerDomain.com</a>
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