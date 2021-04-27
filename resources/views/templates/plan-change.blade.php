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
    <div style='position:relative;margin-top:250px;' class="wrapper page3">
        <div class="container">
            <div class="header">
                <div class="logo">
                    <a href="#"><img src="{{ isset($data['order']->company->logo) ? $data['order']->company->logo : '' }}" style="padding: -10px 0px 15px 0px; width: 200px;" alt="logo"></a>
                </div>
                <div class="statement">
                    <p>Statement For:</p>
                    <h2>{{ $data['order']->customer->full_name }}</h2>
                </div>
                <div class="clear"></div>
            </div>
        </div>
        <div class="tables">
            <div class="container">
                <div class="subscriber">
                    <div class="container">
                        <table class="table-padding">
                            <tr>
                                <td width="75%">Subscriber Detail</td>
                                <td width="25%" colspan="3" class="right">
                                        @if($planChange['subscription']->phone_number_formatted && $planChange['subscription']->phone_number_formatted != 'NA')
                                            {{ $planChange['subscription']->phone_number_formatted }}
                                        @else 
                                            (Pending)
                                        @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="plan_charge">
                    <div class="container">
                        <div class="table-padding">
                            <h2>Plan Charges</h2>
                            <table>
	                            <tr>
	                                <td colspan="3">
	                                    <div class="sepratorline"></div>
	                                </td>
	                            </tr>
	                        </table>
                        </div>
                        <table class="test table-padding">
                            <tr>
                                <td width="23%">Billing Period</td>
                                <td width="60%">
                                    <a>
                                        {{ $data['order']->formatDate($data['order']->invoice->start_date) }} - {{ $data['order']->formatDate($data['order']->invoice->end_date) }}
                                    </a>
                                </td>
                                <td width="17%"></td>
                            </tr>
                            <tr>
                                <td>Plans:</td>
                                <td>
                                    <a>
                                        @if ($planChange['subscription']->downgrade_status)
                                            Downgrade from <b>{{ $planChange['subscription']->plan->name }}</b> to <b>{{ $planChange['subscription']->newPlanDetail->name }}</b>
                                        @elseif ($planChange['subscription']->upgrade_status)
                                            Upgrade from <b>{{ $planChange['subscription']->oldPlan->name }}</b> to <b>{{ $planChange['subscription']->plan->name }}</b>
                                        @elseif ($planChange['same_plan'])
                                            {{ $planChange['subscription']->plan->name }}
                                        @endif
                                    </a>
                                </td>
                                <td class="right">
                                    <a>
                                        @isset ($planChange['subscription']->plan)
                                            $ {{ 
                                                number_format ($planChange['order']->invoice->cal_plan_only_charges, 2)
                                            }}
                                        @endisset
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>Features:</td>
                                <td style=''>
                                    @if ($planChange['addons'])
                                        @foreach ($planChange['addons'] as $addon)
                                            <a>
                                                {{ ($addon['name']) }} <b>{{ !$addon['amount'] ? '(Removed)' : '(Added)' }}</b>
                                            </a> <br>
                                        @endforeach
                                    @endif
                                </td>
                                <td class="right">
                                    @if ($planChange['addons'])
                                        @foreach ($planChange['addons'] as $addon)
                                            <a>    
                                                $ {{ number_format($addon['amount'], 2) }}
                                            </a> <br>
                                        @endforeach
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3">
                                    <div class="sepratorline dark"></div>
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td colspan="2" class="last total_value">
                                    <a>
                                        @if ($planChange['subscription']->calculateChargesForAllproducts([1, 2], $data['invoice']->id, $planChange['subscription']->id) && $planChange['next_month_charges'])
                                            <small>(Next month charges included)</small>
                                        @endif
                                        <strong>
                                            Total Plan Charges: $
                                            {{ number_format($planChange['order']->invoice->cal_plan_charges, 2) }}
                                        </strong>
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="one_time">
                    <div class="container">
                        <div class="table-padding">
                            <h2>One-Time Charges</h2>
                        </div>
                    </div>
                </div>
                <table class="test table-padding">
                    <tr>
                        <td colspan="3">
                            <div class="sepratorline dark"></div>
                        </td>
                    </tr>
                    <td colspan="2" class="last total_value">
                        <a>
                            <strong>Total One-Time Charges: $ 0.00
                            </strong>
                        </a>
                    </td>
                </table>
                <div class="taxes">
                    <div class="container">
                        <div class="table-padding">
                            <h2>Taxes/Fees</h2>
                            @if ($planChange['order']->invoice->cal_state_tax > 0)
                                <table>
                                    <tr>
                                        <td colspan="3">
                                            <div class="sepratorline"></div>
                                        </td>
                                    </tr>
                                </table>
                            @endif
                        </div>
                        <table class="test table-padding">
                            @if ($planChange['order']->invoice->cal_state_tax > 0)
                                <tr>
                                    <td>State</td>
                                    <td colspan="2" class="last"><a>$
                                        {{ number_format ($planChange['order']->invoice->cal_taxes, 2) }}
                                    </a></td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan="3">
                                    <div class="sepratorline dark"></div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" class="right total_value"><a><strong>Total Taxes/Fees: $
                                    @if ($planChange['order']->invoice->cal_taxes)
                                        {{ number_format ($planChange['order']->invoice->cal_taxes, 2) }}
                                    @else
                                        0.00
                                    @endif
                                </strong></a></td>
                            </tr>
                           
                        </table>
                    </div>
                </div>
                <div class="usage_charges">
                    <div class="container">
                        <div class="table-padding">
                            <h2>Usage Charges</h2>
                            <table>
	                            <tr>
	                                <td colspan="3">
	                                    <div class="sepratorline dark"></div>
	                                </td>
	                            </tr>
	                        </table>
                        </div>
                        <table class="test table-padding">
                            <tr>
                                <td></td>
                                <td colspan="2" class="last total_value"><a><strong>Total Usage Charges: $
                                    @if ($planChange['order']->invoice->cal_usage_charges)
                                        {{ number_format ($planChange['order']->invoice->cal_usage_charges, 2) }}
                                    @else
                                        0.00
                                    @endif
                                </strong></a></td>
                            </tr>
                           
                        </table>
                    </div>
                </div>
                <div class="credit">
                    <div class="container">
                        <div class="table-padding">
                            <h2>Coupons</h2>
                            @if ($planChange['order']->invoice->cal_credits > 0)
                                <table>
                                    <tr>
                                        <td colspan="3">
                                            <div class="sepratorline"></div>
                                        </td>
                                    </tr>
                                </table>
                            @endif
                            <table class="test">
                                <tr>
                                    @if ($planChange['order']->invoice->cal_credits)
                                        <td>
                                            @if ($planChange['order']->invoice->invoiceItem->where('type', 6)->first())
                                                {{$planChange['order']->invoice->invoiceItem->where('type', 6)->first()->description}}
                                            @endif
                                        </td>
                                        <td colspan="3" class="right">
                                            @if ($planChange['order']->invoice->invoiceItem->where('type', 6)->first())
                                                $&nbsp;{{ number_format($planChange['order']->invoice->invoiceItem->where('type', 6)->first()->amount, 2) }}
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                                <tr>
                                    <td colspan="3"></td>
                                </tr>
                            </table>
                            <table>
                                <tr>
                                    <td colspan="3">
                                        <div class="sepratorline dark"></div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <table class="test table-padding">
                            <tr>
                                <td><strong></strong></td>
                                <td colspan="2" class="last total_value"><a><strong>Total Coupons: - $
                                    @if ($planChange['order']->invoice->cal_credits)
                                        {{ number_format ($planChange['order']->invoice->cal_credits, 2) }}
                                    @else
                                        0.00
                                    @endif
                                </strong></a></td>
                            </tr>
                            <tr>
                                <td colspan="3"></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="total">
                    <div class="container">
                        <table>
                            <tr>
                                <td>Total Line Charges 
                                        @if ($planChange['subscription']->phone_number_formatted && $planChange['subscription']->phone_number_formatted != 'NA')
                                            {{ $planChange['subscription']->phone_number_formatted }}
                                        @else 
                                            (Pending)
                                        @endif
                                </td>
                                <td colspan="3" class="right"> $
                                    @if ($planChange['order']->invoice->cal_total_charges)
                                        {{ 
                                            number_format(
                                                $planChange['order']->invoice->cal_total_charges, 2
                                            ) 
                                        }}
                                    @else
                                        0.00
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="container">
                    <h3>Page <strong>3</strong>/3</h3>
                </div>
                <div style='page-break-after:always;'>&nbsp;</div>                
            </div>
        </div>
    </div>
</body>

</html>

    