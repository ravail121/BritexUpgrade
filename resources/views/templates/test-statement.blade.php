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
                                <tr>
                                    <td>
                                        @if (!empty($invoice['date_credit']))
                                            Credit on 
                                                {{ $invoice['date_credit'] }}
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
                                <tr>
                                    <td>Coverage Device Deposit</td>
                                    <td colspan="2" class="last"><a>$
                                        @isset ($invoice['total_one_time_charges'])
                                            {{ $invoice['total_one_time_charges'] }}
                                        @endisset
                                    </a></td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        <div class="sepratorline dark"></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td colspan="2" class="last total_value"><a><strong>
                                        Total One-Time Charges: $
                                        @isset ($invoice['total_one_time_charges'])
                                            {{ $invoice['total_one_time_charges'] }}
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
                            <h2>Taxes/Fees</h2>
                            <div class="sepratorline"></div>
                        </div>
                        <table class="test table-padding">
                            <tbody>
                                <tr>
                                    <td>Regulatory</td>
                                    <td colspan="2" class="last"><a>$ 
                                        @isset ($invoice['regulatory_fee'])
                                            {{ $invoice['regulatory_fee'] }}
                                        @endisset
                                    </a></td>
                                </tr>
                                <tr>
                                    <td>State</td>
                                    <td colspan="2" class="last"><a>$ 
                                        @isset ($invoice['state_tax'])
                                            {{ $invoice['state_tax'] }}
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
                                        @isset ($invoice['taxes'])
                                            {{ $invoice['taxes'] }}
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
                                        @isset ($invoice['total_coupons'])
                                            {{ $invoice['total_coupons'] }}
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
                                        @isset ($invoice['account_charges_discount'])
                                            ${{ $invoice['account_charges_discount'] }}
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