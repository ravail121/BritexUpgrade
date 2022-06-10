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
            margin:0;
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
        }
        .containerin {
            padding: 0 20px;
        }

        .invoice {
            width: 30%;
            box-sizing: border-box;
            position: relative;
        }
        .invoice h2 {
            font-size: 30px;
            font-weight: 700;
            padding: 0 0 5px;
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
            padding: 30px 0;
            background: #fff;
            box-shadow: 32.192px 60.916px 131px 0 rgba(4, 7, 11, 0.16);
        }
        .head .bill_info h2 {
            font-size: 14px;
            font-weight: 700;
            padding: 0 0 5px;
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
            padding: 30px 0 20px;
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
            padding: 15px 0;
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
        .bill_detail { padding: 30px 0;}
        .bill_detail td a {
            font-weight: 400;
            float: right;
            margin: 0 80px;
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
            padding: 0 0 15px 0;
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
            padding: 10px 0 8px 0;
            text-align: center;
        }
        .account_info p a {
            color: #FFFFFF;
            font-size: 12px;
            font-weight: 700;
            padding: 10px 0;
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
            margin: 20px 0;
        }
        .footer {
            background: #420590;
            padding: 12px 0;
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
            padding:0 20px;
        }

        .header {
            background: #d7dcec;
            padding: 30px 0;
            margin: 0 0 70px;
            width: 100%;
        }
        .header .logo {
            width: 0%;
            box-sizing: border-box;
            float: left;
        }
        .header img {
            width: 200px;
            padding:0 20px;
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
            border-bottom: 5px solid {{ $data['order']->company->invoice_account_summary_primary_color ?? '#8b00da' }} !important;
        }
        .account h2 {
            font-family: 'Avenir LT Std';
            font-style: normal;
            font-weight: normal;
            font-size: 22px;
            padding: 0 0 20px 0;
        }

        .account img {
            margin:0 40px 0 0;
            width: 100%;
        }
        .base_charges h2 {
            font-family: 'Avenir LT Std';
            font-style: normal;
            font-weight: normal;

            font-size: 22px;
            padding: 60px 0 20px;
        }
        .tables table td {
            font-family: 'Avenir LT Std 65 Medium';
            font-style: normal;
            font-weight: normal;
            font-family: 'Avenir LT Std';
            font-size: 14px;
            padding: 10px 0;
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
            padding:0 0 5px;
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
            padding:0 0 5px;
        }
        .taxes h2 {
            font-family: 'Avenir LT Std';
            font-style: normal;
            font-weight: normal;

            font-size: 22px;
            padding:0 0 5px;
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
            padding:0 0 5px;
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
            padding: 5px 0;
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
            padding:0 0 5px;
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
            padding:0 0 5px;
        }
        .tables h3 {
            font-family: 'Open Sans', sans-serif;
            font-size: 16px;
            font-weight: 400;
            text-align: center;
            padding: 30px 0 20px;
        }
        .fees h2 {
            font-family: 'Avenir LT Std';
            font-style: normal;
            font-weight: normal;

            font-size: 22px;
            position: relative;
            padding:0 0 5px;
        }
        .usage_charges h2 {
            font-family: 'Avenir LT Std';
            font-style: normal;
            font-weight: normal;

            font-size: 22px;
            position: relative;
            padding:0 0 5px;
        }
        .credit h2 {
            font-family: 'Avenir LT Std';
            font-style: normal;
            font-weight: normal;

            font-size: 22px;
            position: relative;
            padding:0 0 5px;
        }
        .plan_charge h2 {
            font-family: 'Avenir LT Std';
            font-style: normal;
            font-weight: normal;

            font-size: 22px;
            position: relative;
            padding:0 0 5px;
        }
        .credit2 {
            margin: 30px 0;
        }
        .credit2 h2 {
            font-family: 'Avenir LT Std';
            font-style: normal;
            font-weight: normal;

            font-size: 22px;
            position: relative;
            padding: 0 0 5px;
        }
        table.test td {
            padding: 8px 0;
        }
        .page1 table.test td, .page2 table.test td,  .page3 table.test td {
            padding: 5px 0;
        }
        .page1 .header, .page2 .header, .page3 .header {
            margin: 0 0 20px;
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
            top:0 !important;
            position: relative !important;
            float: left !important;
        }

        .linksfooter {
            margin-left: 40px !important;
        }
    </style>
    @include('templates.dynamic-invoice-branding')
</head>

<body>
<div style='position:relative;margin-top:400px;' class="wrapper page1">
    <div class="container">
        <div class="header">
            <div class="logo">
                <a href="{{ $data['order']->company->url }}" target="_blank"><img src="{{ isset($data['order']->company->logo) ? $data['order']->company->logo : '' }}" style="padding: -10px 0 15px 0; width: 200px;" alt="logo"></a>
            </div>
            <div class="statement">
                <p>Statement For:</p>
                <h2>{{ $data['order']->customer->full_name }}</h2>
            </div>
            <div class="clear"></div>
        </div>
    </div>

    <div class="container">
        <div class="account">
            <div class="table-padding">
                <h2>Payments / Credits</h2>
            </div>
        </div>
    </div>

    <div class="tables">
        <div class="container">
            <div class="one_time">
                <div class="container" style="margin-top: 25px;">
                    <table class="test table-padding">
                        <tbody>
                            @if ($data['credits']->count())
                                @foreach ($data['credits'] as $c)
                                    <tr>
                                        <td>
                                            Payment on
                                            {{ str_replace('-', '/', $data['order']->formatDate($c->credit->date)) }}
                                            with
                                            {{ ucwords(str_replace('-', ' ', $c->credit->description)) }}
                                        </td>
                                        <td colspan="2" class="last"><a>$
                                                @if ($c->amount)
                                                    {{ number_format($c->amount, 2) }}
                                                @endif
                                        </a></td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td>Payment</td>
                                    <td colspan="2" class="last"><a>$ 0.00</a></td>
                                </tr>
                            @endif
                            @if(count($data['order']->credits))
                                <tr>
                                    <td>
                                        @if ($data['order']->oldCredits($data['order']))
                                            Credit on
                                            {{ str_replace('-', '/', $data['order']->credits->first()->date) }}
                                        @endif
                                    </td>
                                    <td colspan="2" class="last"><a>
                                            @if ($data['order']->oldCredits($data['order']))
                                                $ {{ number_format($data['order']->oldCredits($data['order']), 2) }}
                                            @endif
                                        </a></td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan="3">
                                    <div class="sepratorline dark"></div>
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td colspan="2" class="last total_value"><a><strong>Total Payments & Credits: $
                                            @if ($data['invoice']->creditsToInvoice->sum('amount'))
                                                {{ number_format($data['invoice']->creditsToInvoice->sum('amount'), 2) }}
                                            @else
                                                0.00
                                            @endif
                                        </strong></a></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


    <div class="container">
        <div class="account">
            <div class="table-padding">
                <h2>Account Charges</h2>
            </div>
        </div>
    </div>
    <div class="tables">
        <div class="container">
            <div class="one_time">
                <div class="container" style="margin-top: 25px;">
                    <div class="table-padding">
                        <h2>One-Time Charges</h2>
                        @if (count($data['standalone_items']->where('type', 3)))
                            <div class="sepratorline"></div>
                        @endif
                    </div>

                    <table class="test table-padding">
                        <tbody>
                        @if (count($data['standalone_items']->where('type', 3)))
                            @foreach($data['standalone_items']->where('type', 3)->whereNotIn('description', 'Shipping Fee') as $item)
                                <tr>
                                    <td>
                                        @if ($item['product_type'] == 'device')
                                            {{ $item->standaloneDevice()->first()->name }}
                                        @elseif ($item['product_type'] == 'sim')
                                            {{ $item->standaloneSim()->first()->name }}
                                        @elseif ($item['description'] == 'Activation Fee')
                                            Activation Fee
                                        @else
                                            {{ $item['description'] }}
                                        @endif
                                    </td>
                                    <td colspan='2' class='last'>
                                        <span>$</span> {{ number_format($item['amount'], 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                        @if (count($data['standalone_items']->where('type', 3)->where('description', 'Shipping Fee')))
                            <tr>
                                <td>
                                    Shipping fee
                                </td>
                                <td colspan='2' class='last'>
                                    <span>$</span> {{ number_format($data['standalone_items']->where('type', 3)->where('description', 'Shipping Fee')->sum('amount'), 2) }}
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td colspan="3">
                                <div class="sepratorline dark"></div>
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td colspan="2" class="last total_value"><a><strong>
                                        Total One-Time Charges: $
                                        @if ($data['standalone_items']->where('type', 3)->sum('amount'))
                                            {{ number_format($data['standalone_items']->where('type', 3)->sum('amount'), 2) }}
                                        @else
                                            0.00
                                        @endif
                                    </strong></a></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="taxes">
                <div class="container">
                    <div class="table-padding">
                        <h2>Taxes</h2>
                        @if (count($data['standalone_items']->where('type', 7)))
                            <div class="sepratorline"></div>
                        @endif
                    </div>
                    <table class="test table-padding">
                        <tbody>
                        <tr>
                            @if (count($data['standalone_items']->where('type', 7)))
                                <td>State</td>
                                <td colspan="2" class="last"><a>$ {{ number_format($data['standalone_items']->where('type', 7)->sum('amount'), 2) }} </a></td>
                            @endif
                        </tr>

                        <tr>
                            <td colspan="3">
                                <div class="sepratorline dark"></div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" class="right total_value"><a><strong>Total Taxes/Fees: $
                                        @if (count($data['standalone_items']->where('type', 7)))
                                            {{ number_format($data['standalone_items']->where('type', 7)->sum('amount'), 2) }}
                                        @else
                                            0.00
                                        @endif
                                    </strong></a></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="credits">
                <div class="container">
                    <div class="table-padding">
                        <h2>Coupons</h2>

						<?php
						$coupons = $data['order']->invoice->invoiceItem->where('type', 6)->where('subscription_id', null);
						?>
                        @if ($coupons->count())
                            <table>
                                <tr>
                                    <td colspan="3">
                                        <div class="sepratorline"></div>
                                    </td>
                                </tr>
                            </table>
                        @endif
                        <table class="test">
                            @foreach ($coupons as $coupon)
                                <tr>
                                    <td>{{ $coupon['description'] }}</td>
                                    <td colspan="3" class="right"> $&nbsp;{{ number_format($coupon['amount'], 2) }} </td>
                                </tr>
                            @endforeach
                            <tr>
                                <td colspan="3"></td>
                            </tr>
                        </table>
                        <div class="sepratorline dark"></div>
                    </div>
                    <table class="test table-padding">
                        <tbody>
                        <tr>
                            <td></td>
                            <td colspan="2" class="last total_value"><a><strong>Total Coupons: - $
                                        @if (count($data['standalone_items']->whereIn('type', [6, 8])))
                                            {{ number_format($data['standalone_items']->whereIn('type', [6, 8])->sum('amount'), 2) }}
                                        @else
                                            0.00
                                        @endif
                                    </strong></a></td>
                        </tr>
                        <tr>
                            <td colspan="3"></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="total">
                <div class="container">
                    <table>
                        <tbody>
                        <tr>
                            <td>Total Account Charges</td>
                            <td colspan="3" class="right">
                                @if ($data['invoice']->standAloneTotal($data['invoice']->id))
                                    ${{ number_format($data['invoice']->standAloneTotal($data['invoice']->id), 2) }}
                                @else
                                    $ 0.00
                                @endif
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="container">
                <h3>Page <strong> 2</strong>/
                    @if (!isset($planChange))
                        @if (isset($subscriptions) && count($subscriptions))
                            {{ count($subscriptions) + 2 }}
                        @elseif (isset($data['order']->subscriptions) && count($data['order']->subscriptions))
                            {{ count($data['order']->subscriptions) + 2 }}
                        @else
                            2
                        @endif
                    @else
                        3
                    @endif
                </h3>
            </div>
            <div style='page-break-after:always;'>&nbsp;</div>
        </div>
    </div>
</div>
</body>

</html>