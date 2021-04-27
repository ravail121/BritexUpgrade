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
                                    @isset($subscription)
                                        @isset ($subscription->phone_number)
                                            {{ $data['order']->phoneNumberFormatted($subscription->phone_number) }}
                                        @else 
                                            Pending
                                        @endisset
                                    @endisset
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="plan_charge">
                    <div class="container">
                        <div class="table-padding">
                            <h2>Plan Charges</h2>
                            @isset($subscription)
                                <table>
                                    <tr>
                                        <td colspan="3">
                                            <div class="sepratorline"></div>
                                        </td>
                                    </tr>
                                </table>
                            @endisset
                        </div>
                        <table class="test table-padding">
                            @isset($subscription)
                                <tr>
                                    <td width="23%">Billing Period</td>
                                    <td width="60%">
                                        <a>
                                            @if (isset($subscription))
                                                {{ $data['order']->formatDate($data['order']->invoice->start_date) }} - {{ $data['order']->formatDate($data['order']->invoice->end_date) }}
                                            @endif
                                        </a>
                                    </td>
                                    <td width="17%"></td>
                                </tr>
                                <tr>
                                    <td>Plans:</td>
                                    <td>
                                        <a>
                                            @isset ($subscription->plan->name)
                                                {{ $subscription->plan->name }}
                                            @endisset
                                        </a>
                                    </td>
                                    <td class="right">
                                        <a>
                                            @isset ($subscription->plan)
                                                $ {{ 
                                                    number_format ($subscription->calculateChargesForAllproducts([1], $data['invoice']->id, $subscription->id), 2)
                                                }}
                                            @else 
                                                $ 0.00
                                            @endisset
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    @if(isset($subscription->subscriptionAddon) && count($subscription->subscriptionAddon->whereNotIn('status', 'removed')))
                                        <td>Features:</td>
                                        <td>
                                            @foreach ($subscription->subscriptionAddon->whereNotIn('status', 'removed') as $item)
                                                <a>
                                                    @if ($subscription->getAddonData($item, $data['invoice']->id))
                                                        {{-- <div style='margin-left: 10px;'> --}}
                                                            {{$subscription->getAddonData($item, $data['invoice']->id)['name']}}
                                                        {{-- </div> --}}
                                                    @endif
                                                </a> <br>
                                            @endforeach
                                        </td>
                                        <td class="right">
                                            @foreach ($subscription->subscriptionAddon->whereNotIn('status', 'removed') as $item) 
                                                <a>
                                                    @if ($subscription->getAddonData($item, $data['invoice']->id))
                                                        {{-- <div>  --}}
                                                            $ {{ number_format ($subscription->getAddonData($item, $data['invoice']->id)['amount'], 2) }} 
                                                        {{-- </div> --}}
                                                    @endif
                                                </a> <br>
                                            @endforeach
                                        </td>
                                    @endif
                                </tr>
                            @endisset
                            <tr>
                                <td colspan="3">
                                    <div class="sepratorline dark"></div>
                                </td>
                            </tr>
                            <tr>
                                <td></td>
                                <td colspan="2" class="last total_value">
                                    <a>
                                        @if (isset($subscription) && $subscription->calculateChargesForAllproducts([1, 2], $data['invoice']->id, $subscription->id) > 0 && $subscription->customerRelation->advancePaidInvoiceOfNextMonth->count())
                                            <small>(Next month charges included)</small>
                                        @endif
                                        <strong>
                                            Total Plan Charges: $
                                            @if (isset($subscription) && $subscription->calculateChargesForAllproducts([1, 2], $data['invoice']->id, $subscription->id))
                                                {{ number_format ( $subscription->calculateChargesForAllproducts([1, 2], $data['invoice']->id, $subscription->id), 2 ) }}
                                            @else 
                                                0.00  
                                            @endif
                                        </strong>
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
    
                @if ($data['order']->invoice->type == 2)
                <div class="one_time">
                    <div class="container">
                        <div class="table-padding">
                            <h2>One-Time Charges</h2>
                            @if (isset($subscription) && $subscription->invoiceItemDetail->where('type', 3)->sum('amount'))
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
                            @if (isset($subscription) && $subscription->device_id)
                                <tr>
                                    <td>
                                        @if ($subscription->device->getDeviceName($subscription->device_id))
                                            {{ $subscription->device->getDeviceName($subscription->device_id) }}
                                        @endif
                                    </td>
                                    <td colspan='2' class='last'>
                                        $ {{ number_format ($subscription->device->deviceWithSubscriptionCharges($subscription->device_id), 2) }}
                                    </td>
                                </tr>
                            @endif
                            @if (isset($subscription) && $subscription->sim_id)
                                <tr>
                                    <td>
                                        {{ $subscription->simDetail->getSimName($subscription->sim_id) }}
                                    </td>
                                    
                                    <td colspan='2' class='last'>
                                        $ {{ number_format ($subscription->simDetail->getSimCharges($subscription->sim_id), 2) }}
                                    </td>
                                </tr>
                            @endif
                            @if (isset($subscription) && $subscription->invoiceItemDetail->where('description', 'Activation Fee')->sum('amount'))
                                <tr>
                                    <td>Activation Fee</td>
                                    <td colspan='2' class='last'>
                                        $
                                        {{
                                            number_format (
                                                $subscription->invoiceItemDetail->where('description', 'Activation Fee')->sum('amount'), 2
                                            )
                                        }}
                                    </td>
                                </tr>
                            @endif
                            @if (isset($subscription) && $subscription->invoiceItemDetail->where('description', 'Shipping Fee')->sum('amount'))
                                <tr>
                                    <td>Shipping Fee</td>
                                    <td colspan='2' class='last'>
                                        $ {{ number_format ( $subscription->invoiceItemDetail->where('description', 'Shipping Fee')->sum('amount'), 2 ) }}
                                    </td>
                                </tr>
                            @endif  
                            <tr>
                                <td colspan="3">
                                    <div class="sepratorline dark"></div>
                                </td>
                            </tr>
                            <td colspan="2" class="last total_value">
                                <a>
                                    <strong>Total One-Time Charges: $
                                        @if (isset($subscription) && $subscription->cal_onetime_charges)
                                            {{ number_format ( $subscription->cal_onetime_charges, 2 ) }}
                                        @else 
                                            0.00
                                        @endif
                                    </strong>
                                </a>
                            </td>
                        </table>
                    </div>
                </div>
                @endif
                <div class="taxes">
                    <div class="container">
                        <div class="table-padding">
                            <h2>Taxes/Fees</h2>
                            <table>
                                @isset($subscription)
                                    <tr>
                                        <td colspan="3">
                                            <div class="sepratorline"></div>
                                        </td>
                                    </tr>
                                @endisset
	                        </table>
                        </div>
                        <table class="test table-padding">
                            <tr>
                                @isset($subscription)
                                    <td>Regulatory</td>
                                    <td colspan="2" class="last"><a>$
                                        
                                        @if (isset($subscription) && $subscription->cal_regulatory_fee)
                                            {{ number_format ($subscription->calculateChargesForAllproducts([5], $data['invoice']->id, $subscription->id), 2) }}
                                        @else
                                            0.00
                                        @endif
                                    
                                    </a></td>
                                @endisset
                            </tr>
                            <tr>
                                @isset($subscription)
                                    <td>State</td>
                                    <td colspan="2" class="last"><a>$
                                        @if (isset($subscription) && $subscription->cal_tax_rate)
                                            {{ number_format ($subscription->calculateChargesForAllproducts([7], $data['invoice']->id, $subscription->id), 2) }}
                                        @else
                                            0.00
                                        @endif
                                    </a></td>
                                @endisset
                            </tr>

                            <tr>
                                <td colspan="3">
                                    <div class="sepratorline dark"></div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" class="right total_value"><a><strong>Total Taxes/Fees: $
                                    @if (isset($subscription) && $subscription->cal_taxes)
                                        {{ number_format ($subscription->calculateChargesForAllproducts([5, 7], $data['invoice']->id, $subscription->id), 2) }}
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
                                    @if (isset($subscription) && $subscription->cal_usage_charges)
                                        {{ number_format ($subscription->calculateChargesForAllproducts([4], $data['invoice']->id, $subscription->id), 2) }}
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
                            @if (isset($subscription) && $subscription->cal_credits > 0)
                                <table>
                                    <tr>
                                        <td colspan="3">
                                            <div class="sepratorline"></div>
                                        </td>
                                    </tr>
                                </table>
                            @endif
                            <table class="test">
                                @if (isset($subscription))
                                    @foreach ($data['order']->invoice->invoiceItem->where('type', 6)->where('subscription_id', $subscription->id) as $coupon)
                                        <tr>
                                            <td>{{ $coupon['description'] }} 
                                                <span> 
                                                    @if ($coupon->coupon)
                                                        @if ($coupon->coupon->num_cycles == 0)
                                                            (Infinite Cycles)
                                                        @elseif ($coupon->coupon->num_cycles == 1)
                                                            (One time coupon)
                                                        @elseif ($coupon->coupon->num_cycles > 1)
                                                            ({{ $coupon->coupon->num_cycles - 1 }}{{ $coupon->coupon->num_cycles - 1 == 1 ? ' cycle' : ' cycles'}} remaining)
                                                        @endif
                                                    @endif
                                                </span> 
                                            </td>
                                            <td colspan="3" class="right"> $&nbsp;{{ number_format($coupon['amount'], 2) }} </td>
                                        </tr>
                                    @endforeach
                                @endif
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
                                    @if (isset($subscription) && $subscription->cal_credits)
                                        {{ number_format ($subscription->calculateChargesForAllproducts([6], $data['invoice']->id, $subscription->id), 2) }}
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
                                    @isset($subscription)
                                        @if ($subscription->phone_number_formatted && $subscription->phone_number_formatted != 'NA')
                                            ({{$subscription->phone_number_formatted}})
                                        @else 
                                            (Pending)
                                        @endif
                                    @endisset
                                </td>
                                <td colspan="3" class="right"> $
                                    @if (isset($subscription->cal_total_charges))
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
                        </table>
                    </div>
                </div>
                <div class="container">
                    <h3>Page 
                        <strong> 
                            {{isset($index) ? $index + 3 : 3}}    
                        </strong>/ 
                        @if (isset($subscriptions) && count($subscriptions))
                            {{ count($subscriptions) + 2 }}
                        @elseif (isset($data['order']->subscriptions) && count($data['order']->subscriptions))
                            {{ count($data['order']->subscriptions) + 2 }}
                        @else 
                            3
                        @endisset
                    </h3>
                </div>
                <div style='page-break-after:always;'>&nbsp;</div>                
            </div>
        </div>
    </div>
</body>

</html>

    