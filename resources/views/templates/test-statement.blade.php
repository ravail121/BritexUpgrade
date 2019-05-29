<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Teltik</title>
    <link href="https://teltik.pw/pdf/css/82style.css" type="text/css" rel="stylesheet">
</head>

<body>
    <div class="wrapper page1">
        <div class="container">
            <div class="header">
                <div class="logo">
                    <a href="#"><img src="https://teltik.pw/pdf/img/logo.png" alt="logo"></a>
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
                                    <td>Payment on {{ str_replace('-', '/', $invoice['start_date']) }} with {{ $invoice['payment_method'] }}</td>
                                    <td colspan="2" class="last"><a>${{ $invoice['payment'] }}</a></td>
                                </tr>
                                <tr>
                                    <td>Credit on {{ $invoice['payment_date'] }}</td>
                                    <td colspan="2" class="last"><a>$ 0</a></td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        <div class="sepratorline dark"></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td></td>
                                <td colspan="2" class="last total_value"><a><strong>Total Payments & Credits: {{ $invoice['payment'] }}</strong></a></td>
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
                                    <td colspan="2" class="last"><a>${{ $invoice['total_one_time_charges'] }}</a></td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        <div class="sepratorline dark"></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td colspan="2" class="last total_value"><a><strong>Total One-Time Charges: ${{ $invoice['total_one_time_charges'] }}</strong></a></td>
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
                                    <td colspan="2" class="last"><a>$ {{ $invoice['regulatory_fee'] }}</a></td>
                                </tr>
                                <tr>
                                    <td>State</td>
                                    <td colspan="2" class="last"><a>$ {{ $invoice['state_tax'] }}</a></td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        <div class="sepratorline dark"></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="right total_value"><a><strong>Total Taxes/Fees: ${{ $invoice['taxes'] }}</strong></a></td>
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
                                    <td colspan="2" class="last total_value"><a><strong>Total Coupons: ${{ $invoice['credits'] }}</strong></a></td>
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
                                        ${{ 
                                            $invoice['taxes'] + $invoice['total_one_time_charges']
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="container">
                    <h3>Page <strong> 1</strong>/2 </h3>
                </div>

                <div style='page-break-after:always;'>&nbsp;</div>
            </div>
        </div>
    </div>
</body>

</html>