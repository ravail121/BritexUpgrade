<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $data['order']->company->name }}</title>
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
    <div style='margin-bottom:500px;' class="wrapper" >
        <div class="container" style="width: 100%; float: none; margin: 0px auto;">
            <div style='position:relative;top:100px;' class="boxmain">
                <div class="head" style="padding: 0px 0px 0px;">
                    <div class="containerin">
                        <div class="logo" style="width: 100%; text-align: center;">
                            <img src="{{ isset($data['order']->company->logo) ? $data['order']->company->logo : '' }}" style="padding: -10px 0px 15px 0px; width: 200px;" alt="logo">
                        </div>
                        <div style='margin-top:20px' class="invoice">
                            <h2>INVOICE</h2>
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tbody>
                                    <tr>
                                        <td>Invoice No.</td>
                                        <td width="20px"></td>
                                        <td class="detail">{{ $data['invoice']->id }}</td>
                                    </tr>
                                    <?php
                                        $downgradeInvoice = false;
                                        $samePlan = false;
                                        if (isset($planChange['subscription']) && $planChange['subscription']->downgrade_status) {
                                            $downgradeInvoice = true;
                                        }
                                        if (isset($planChange['subscription']) && $planChange['same_plan']) {
                                            $samePlan = true;
                                        }
                                    ?>
                                    @if (!$downgradeInvoice && !$samePlan)
                                        <tr>
                                            <td>Period Beginning</td>
                                            <td width="20px"></td>
                                            <td class="detail">@date($data['order']->formatDate($data['invoice']->start_date))</td>
                                        </tr>
                                        <tr>
                                            <td>Period Ending</td>
                                            <td width="20px"></td>
                                            <td class="detail">@date($data['order']->formatDate($data['invoice']->end_date))</td>
                                        </tr>
                                        <tr>
                                            <td>Due Date</td>
                                            <td width="20px"></td>
                                            <td class="detail">@date($data['order']->formatDate($data['invoice']->due_date))</td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td>
                                                @if ($downgradeInvoice) 
                                                    Downgrade Date
                                                @elseif ($samePlan)
                                                    Subscription Change Date
                                                @endif
                                            </td>
                                            <td width="20px"></td>
                                            <td class="detail">@date($data['order']->formatDate($data['invoice']->created_at))</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        <!-- Customer Info -->
                        <div style='position:absolute; left:0; right:0; margin: auto; top: 75px; border-color: transparent;' class="linksfooter">
                            <h3>Customer Info</h3>
                            <div class="customer_info" style='margin-top: 5px;'>
                                @if ($data['order']->customer->company_name)
                                    <p><span>
                                        {{ $data['order']->customer->company_name }},
                                    </span></p>
                                @endif
                                <p><span>{{ $data['order']->customer->full_name }},</span></p>
                                <p><span>{{ $data['order']->customer->shipping_address1 }}</span></p>
                                <p><span>{{ $data['order']->customer->zip_address }}</span></p>
                            </div>
                        </div>
                        <div style='position:absolute; right:15px; margin: auto; top: 65px; border-color: transparent; box-shadow:none;' class="bill_info">
                            <h2>
                                @if (isset($planChange['subscription']))
                                    @if ($planChange['subscription']->downgrade_status)
                                        Downgrade on
                                    @elseif ($planChange['subscription']->upgrade_status)
                                        Upgrade on
                                    @elseif ($planChange['same_plan'])
                                        Subscription change on
                                    @endif
                                @else 
                                    Bill for
                                @endif
                            </h2>
                            <h3 style='margin-top: 10px;'>{{ $data['invoice']->dateFormatForInvoice($data['invoice']->created_at) }}</h3>
                        </div>
                        <div class="info">
                                <h2>Important Information</h2>
                                <p>1. You are 
                                    <strong>
                                        @if (isset($data['order']->customer->auto_pay) && $data['order']->customer->auto_pay)
                                        
                                        @else 
                                            not
                                        @endif
                                    </strong> 
                                    enrolled in Autopay. Amount will 
                                    <strong>
                                        @if (isset($data['order']->customer->auto_pay) && $data['order']->customer->auto_pay)
                                    
                                        @else 
                                            not
                                        @endif    
                                    </strong> be forwarded for automatic processing.</p>
                                <p>2. Pay online <a href="{{ isset($data['order']->company->url) ? $data['order']->company->url : '' }}">{{ $data['order']->company->url_formatted }}</a></p>
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
                                                    <td class="detail">$ 0.00</td>
                                                </tr>
                                                <tr>
                                                    <td>Payments Received </td>
                                                    <td class="detail">$ 0.00</td>
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
                                                    <td class="detail">$ 0.00</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                    <td class="titlebx">
                                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                            <tbody>
                                                <tr>
                                                    <td>Services, Usage &amp; Charges</td>
                                                    <td class="detail">$ 
                                                        @if ($data['invoice']->cal_service_charges)
                                                            {{ number_format($data['invoice']->cal_service_charges, 2) }}
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Fees/Taxes</td>
                                                    <td class="detail">$ 
                                                        @if ($data['invoice']->cal_taxes)
                                                            {{ number_format($data['invoice']->cal_taxes, 2) }}
                                                        @endif
                                                    </td>
                                                </tr>
                                                
                                                <tr>
                                                    <td>Coupons 
                                                        <div class="seprator"></div>
                                                    </td>
                                                    <td class="detail">-$ 
                                                        @isset ($data['invoice']->cal_credits)
                                                            {{ number_format($data['invoice']->cal_credits, 2) }}
                                                        @endisset
                                                    </td>
                                                </tr>
                                                
                                                <tr>
                                                    <td>Total Charges This Bill</td>
                                                    
                                                    <td class="detail">$ 
                                                        @isset ($data['invoice']->subtotal)
                                                            {{ number_format($data['invoice']->subtotal, 2) }}
                                                        @endisset
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                    <td class="titlebx">
                                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                            <tbody>
                                                <tr>
                                                    <td>Payments/Credits</td>
                                                    <td class="detail">$ 
                                                            {{ number_format($data['invoice']->creditsToInvoice->sum('amount'), 2) }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Due {{ date('M', strtotime($data['invoice']->due_date)).' '.date('j', strtotime($data['invoice']->due_date)) }}</td>
                                                    <td class="detail">$ 
                                                        {{ $data['invoice']->total_due ? number_format($data['invoice']->total_due, 2) : '0.00' }}
                                                    </td>
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
                                                    <td colspan="2">{{ isset($data['order']->company->support_phone_number) ? $data['order']->phoneNumberFormatted($data['order']->company->support_phone_number) : '' }}</td>
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
                                    <th>Coupons</th>
                                    <th>Total Current Charges</th>
                                </tr>
                            </thead>
                            <tbody>
                            @if (!isset($planChange))
                                <tr class="tfootQ">
                                    <td>Account Charges</td>
                                    <td>$ 0.00</td>
                                    <td>$ 
                                        @if(count($data['standalone_items']->where('type', 3)))
                                            {{ number_format($data['standalone_items']->where('type', 3)->sum('amount'), 2) }}
                                        @else 
                                            0.00
                                        @endif
                                    </td>
                                    <td>$ 0.00</td>
                                    <td>$ 
                                        @if(count($data['standalone_items']->where('type', 7)))
                                            {{ number_format($data['standalone_items']->where('type', 7)->sum('amount'), 2) }}
                                        @else 
                                            0.00
                                        @endif
                                    </td>
                                    <td>-$ 

                                        @if(count($data['standalone_items']->whereIn('type', [6, 8])))
                                            {{number_format($data['standalone_items']->whereIn('type', [6, 8])->sum('amount'), 2)}}
                                        @else 
                                            0.00
                                        @endif
                                    </td>
                                    <td>$ 
                                        @if($data['standalone_items']->sum('amount'))
                                            {{ number_format($data['invoice']->standAloneTotal($data['invoice']->id), 2)}}
                                        @else
                                            0.00
                                        @endif
                                    </td>
                                </tr>
                            
                        
                                @if (count($data['order']->subscriptions))
                                    @foreach ($data['order']->subscriptions as $index => $subscription)
                                        <tr>
                                            <td>@isset ($subscription->phone_number) 
                                                    {{ $subscription->phone_number_formatted != 'NA' ? $subscription->phone_number_formatted : "Pending" }}
                                                @else
                                                    Pending
                                                @endisset
                                            </td>
                                            <td>$ @if ($subscription->cal_plan_charges) 
                                                    {{ 
                                                        number_format (
                                                            $subscription->calculateChargesForAllproducts([1, 2], $data['invoice']->id, $subscription->id), 2
                                                        )
                                                    }} 
                                                @endif
                                            </td>
                                            <td>$ @if ($subscription->cal_onetime_charges)
                                                    {{ 
                                                        number_format (
                                                            $subscription->calculateChargesForAllproducts([3], $data['invoice']->id, $subscription->id), 2
                                                        )
                                                    }}
                                                @endif
                                            </td>
                                            <td>$ @if ($subscription->cal_usage_charges)
                                                    {{ number_format ( $subscription->calculateChargesForAllproducts([4], $data['invoice']->id, $subscription->id), 2) }}
                                                @endif
                                            </td>
                                            <td>$ @if($subscription->cal_taxes)
                                                    {{ number_format ($subscription->calculateChargesForAllproducts([7, 5], $data['invoice']->id, $subscription->id), 2) }}
                                                @endif
                                            </td>
                                            <td>-$ @if($subscription->cal_credits)
                                                    {{ 
                                                        number_format ( $subscription->calculateChargesForAllproducts([6, 8, 10], $data['invoice']->id, $subscription->id), 2)
                                                    }}
                                                 @endif
                                            </td>
                                            <td>$ @if ($subscription->cal_total_charges)
                                                        {{ 
                                                            number_format(
                                                                $subscription->totalSubscriptionCharges($data['invoice']->id, $subscription) - 
                                                                $subscription->totalSubscriptionDiscounts($data['invoice']->id, $subscription), 2
                                                            ) 
                                                        }}
                                                    @else 
                                                    0.00
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            @else
                                <tr>            
                                    <td>@if ($planChange['subscription']->upgrade_downgrade_status)
                                            {{ $planChange['subscription']->phone_number_formatted != 'NA' ? $planChange['subscription']->phone_number_formatted : "Pending" }}
                                        @endif
                                    </td>
                                    <td>$ {{ number_format($data['invoice']->cal_plan_charges, 2) }}
                                    </td>
                                    <td>$ {{ number_format($data['invoice']->cal_onetime_charges, 2) }}
                                    </td>
                                    <td>$ {{ number_format($data['invoice']->cal_usage_charges, 2) }}
                                    </td>
                                    <td>$ {{ number_format($data['invoice']->cal_taxes, 2) }}
                                    </td>
                                    <td>-$ {{ number_format($data['invoice']->cal_credits, 2) }}</td>
                                    <td>$  {{ number_format($data['invoice']->cal_total_charges, 2)}}
                                    </td>
                                </tr>
                            @endif
                            </tbody>
                            <tr>
                                <td colspan="7" class="lh0">
                                    <div class="total_img">
                                        <img src="https://teltik.pw/pdf/img/shape.png" alt="">
                                    </div>
                                </td>
                            </tr>
                            @if (!isset($planChange))
                                <tr class="tfootQ">
                                    <td><b>Total</b></td>
                                    <td><b>$ 
                                        @if($data['invoice']->cal_plan_charges)
                                            {{ number_format($data['invoice']->cal_plan_charges, 2) }}
                                        @endif
                                    </b></td>
                                    <td><b>$ 
                                        @if($data['invoice']->cal_onetime)
                                            {{ number_format($data['invoice']->cal_onetime, 2) }}
                                        @endif
                                    </b></td>
                                    <td><b>$ 
                                        @if($data['invoice']->cal_usage_charges)
                                            {{ number_format($data['invoice']->cal_usage_charges, 2) }}
                                        @endif
                                    </b></td>
                                    <td><b>$ 
                                        @if($data['invoice']->cal_taxes)
                                            {{ number_format($data['invoice']->cal_taxes, 2) }}
                                        @endif
                                    </b></td>
                                    <td><b>-$ 
                                        @if($data['invoice']->cal_credits)
                                            {{ number_format($data['invoice']->cal_credits, 2) }}
                                        @endif
                                        </b></td>
                                    <td><b>$ 
                                        @if($data['invoice']->cal_total_charges)
                                        {{ 
                                            number_format($data['invoice']->cal_total_charges, 2)
                                        }}
                                        @endif
                                    </b></td>
                                </tr>
                            @endisset
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
                                    <a href="#">Contact us: <td colspan="2">{{ isset($data['order']->company->support_phone_number) ? $data['order']->phoneNumberFormatted($data['order']->company->support_phone_number) : '' }}</td></a>
                                    <a href="{{ isset($data['order']->company->url) ? $data['order']->company->url : '' }}">{{ $data['order']->company->url_formatted }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div style='text-align:center; margin-top: 35px; margin-bottom: 35px;' class="container">
                    <p>Page <strong> 1</strong>/
                        @if (!isset($planChange))
                            @if (count($data['order']->subscriptions))
                                {{ count($data['order']->subscriptions) + 2 }}
                            @else 
                                1
                            @endif
                        @else 
                            3
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>

@include('templates.account-charges')
@if (!isset($planChange['subscription']))
    @if ($data['order']->subscriptions->count())
        @foreach ($data['order']->subscriptions as $index => $subscription)
            @include('templates.order-subscription-details')
        @endforeach
    @else 
        @include('templates.order-subscription-details')
    @endif
@else 
    @if ($planChange['subscription'])
        @include('templates.plan-change')
    @endif
@endif
