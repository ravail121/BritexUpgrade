<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Teltik</title>
    <link href="https://teltik.pw/pdf/css/82style.css" type="text/css" rel="stylesheet">
</head>

<body>
    <div style='position:relative;margin-top:400px;' class="wrapper page1">
        <div class="container">
            <div class="header">
                <div class="logo">
                    <a href="#"><img src="{{ isset($invoice['company_logo']) ? $invoice['company_logo'] : '' }}" alt="logo"></a>
                </div>
                <div class="statement">
                    <p>Statement For:</p>
                    <h2>{{ $invoice['customer_name'] }}</h2>
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
                                <tr>
                                    <td>
                                        @if (!empty($invoice['date_payment']))
                                            Payment on 
                                                {{ str_replace('-', '/', $invoice['date_payment']) }} 
                                            with 
                                                {{ $invoice['payment_method'] }}
                                        @else
                                            Payment
                                        @endif
                                    </td>
                                    <td colspan="2" class="last"><a>$
                                        @isset ($invoice['total_payment'])
                                            {{ $invoice['total_payment'] }}
                                        @endisset
                                    </a></td>
                                </tr>
                                @if($invoice['total_old_credits'] > 0)
                                    <tr>
                                        <td>
                                            @if (!empty($invoice['date_credit']))
                                                Credit on 
                                                    {{ str_replace('-', '/', $invoice['date_credit']) }}
                                            @else
                                                Credit
                                            @endif
                                        </td>
                                        <td colspan="2" class="last"><a>$
                                            @isset ($invoice['total_old_credits'])
                                                {{ $invoice['total_old_credits'] }}
                                            @endisset
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
                                    @isset ($invoice['total_used_credits'])
                                        {{ $invoice['total_used_credits'] }}
                                    @endisset
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
                            <div class="sepratorline"></div>
                        </div>

                        <table class="test table-padding">
                            <tbody>
                                @isset($invoice['standalone_items']['devices'])
                                    @foreach($invoice['standalone_items']['devices'] as $item)
                                        <tr>
                                            <td>{{$item['name']}}</td>
                                            <td colspan='2' class='last'>
                                                {{number_format($item['amount'], 2)}}
                                            </td>
                                        </tr>
                                    @endforeach
                                    @endisset
                                    @isset($invoice['standalone_items']['sims'])
                                    @foreach($invoice['standalone_items']['sims'] as $item)
                                        <tr>
                                            <td>{{$item['name']}}</td>
                                            <td colspan='2' class='last'>
                                                {{number_format($item['amount'], 2)}}
                                            </td>
                                        </tr>
                                    @endforeach
                                @endisset

                                <tr>
                                    <td colspan="3">
                                        @if (isset($invoice['one_time_standalone']) && $invoice['one_time_standalone'] > 0)
                                            <div class="sepratorline dark"></div>
                                        @endisset
                                    </td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td colspan="2" class="last total_value"><a><strong>
                                        Total One-Time Charges: $
                                        @isset ($invoice['one_time_standalone'])
                                            {{ $invoice['one_time_standalone'] }}
                                        @endisset
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
                            <div class="sepratorline"></div>
                        </div>
                        <table class="test table-padding">
                            <tbody>
                                {{-- 
                                <tr>
                                    <td>Regulatory</td>
                                    <td colspan="2" class="last"><a>$ 
                                        @isset ($invoice['standalone_regulatory'])
                                            {{ $invoice['standalone_regulatory'] }}
                                        @endisset
                                    </a></td>
                                </tr>
                                --}}
                                <tr>
                                    <td>State</td>
                                    <td colspan="2" class="last"><a>$ 
                                        @isset ($invoice['standalone_tax'])
                                            {{ $invoice['standalone_tax'] }}
                                        @endisset
                                    </a></td>
                                </tr>

                                <tr>
                                    <td colspan="3">
                                        <div class="sepratorline dark"></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="right total_value"><a><strong>Total Taxes/Fees: $
                                        @isset ($invoice['standalone_total_taxes_fees'])
                                            {{ $invoice['standalone_total_taxes_fees'] }}
                                        @endisset
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
                            <div class="sepratorline dark"></div>
                        </div>
                        <table class="test table-padding">
                            <tbody>
                                <tr>
                                    <td></td>
                                    <td colspan="2" class="last total_value"><a><strong>Total Coupons: - $
                                        @isset ($invoice['standalone_coupons'])
                                            {{ $invoice['standalone_coupons'] }}
                                        @endisset
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
                                    <td colspan="3" class="right total_value">
                                        @isset ($invoice['standalone_total'])
                                            ${{ $invoice['standalone_total'] }}
                                        @endisset
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="container">
                    <h3>Page <strong> 2</strong>/3 </h3>
                </div>

                <div style='page-break-after:always;'>&nbsp;</div>
            </div>
        </div>
    </div>
</body>

</html>