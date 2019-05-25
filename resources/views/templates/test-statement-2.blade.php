<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Teltik</title>
    <link href="https://teltik.pw/pdf/css/82style.css" type="text/css" rel="stylesheet">
</head>

<body>
    <div class="wrapper page3">
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
        <div class="tables">
            <div class="container">
                <div class="subscriber">
                    <div class="container">
                        <table class="table-padding">
                            <tr>
                                <td width="75%">Subscriber Detail</td>
                                <td width="25%" colspan="3" class="right">
                                    {{ $invoice['subscriptions'][0]['phone'] }}
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
                                        {{ $invoice['start_date'] }} - {{ $invoice['end_date'] }}
                                    </a>
                                </td>
                                <td width="17%"></td>
                            </tr>
                            <tr>
                                <td>Plan:</td>
                                <td>
                                    <a>
                                        
                                        @foreach ($invoice['plan_names'] as $name)
                                            {{ $name }} -
                                        @endforeach
                                    </a>
                                </td>
                                <td class="right">${{ $invoice['plan_charges'] }}</td>
                            </tr>
                            <tr>
                                <td>Features:</td>
                                <td>
                                    <a>
                                        @if (count($invoice['addons']) > 0)
                                            @foreach ($invoice['addons'] as $addon)
                                                {{ $addon['name'] }} -
                                            @endforeach
                                        @else
                                            No addon
                                        @endif
                                    </a>
                                </td>
                                <td class="right">
                                    <a>
                                        @if (count($invoice['addons']) > 0)
                                            @foreach ($invoice['addons'] as $addon)
                                                ${{ $addon['amount'] }}
                                            @endforeach
                                        @else
                                            0.00
                                        @endif
                                    </a>
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
                                        <strong>
                                            Total Plan Charges: ${{ $invoice['plan_charges'] }}
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
                                <td colspan="2" class="last total_value"><a><strong>Total One-Time Charges: {{ $invoice['total_one_time_charges'] }}</strong></a></td>
                            </tr>
                            
                        </table>
                    </div>
                </div>
                <div class="taxes">
                    <div class="container">
                        <div class="table-padding">
                            <h2>Taxes/Fees</h2>
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
                                <td>Regulatory</td>
                                <td colspan="2" class="last"><a>${{ $invoice['regulatory_fee'] }}</a></td>
                            </tr>
                            <tr>
                                <td>State</td>
                                <td colspan="2" class="last"><a>${{ $invoice['state_tax'] }}</a></td>
                            </tr>
                            <tr>
                                <td colspan="3">
                                    <div class="sepratorline dark"></div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" class="right total_value"><a><strong>Total Taxes/Fees: {{ $invoice['taxes'] }}</strong></a></td>
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
                                <td colspan="2" class="last total_value"><a><strong>Total Usage Charges: ${{ $invoice['total_usage_charges'] }}</strong></a></td>
                            </tr>
                           
                        </table>
                    </div>
                </div>
                <div class="credit">
                    <div class="container">
                        <div class="table-padding">

                            <h2>Credits</h2>
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
                                <td colspan="2" class="last total_value"><a><strong>Total Credits: ${{ $invoice['credits'] }}</strong></a></td>
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
                                <td>Total Line Charges {{ $invoice['subscriptions'][0]['phone'] }}</td>
                                <td colspan="3" class="right">                                         ${{ 
                                    !isset($invoice['serviceChargesProrated']) ? $invoice['total_charges'] : $invoice['serviceChargesProrated'] + $invoice['taxes'] - $invoice['credits']
                                }}
                            </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="container">
                    <h3>Page <strong> 2</strong>/2 </h3>
                </div>
                <div style='page-break-after:always;'>&nbsp;</div>                
            </div>
        </div>
    </div>
</body>

</html>