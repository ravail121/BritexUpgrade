<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Teltik</title>
    <link href="https://teltik.pw/pdf/css/82style.css" type="text/css" rel="stylesheet">
</head>

<body>
    <div style='position:relative;margin-top:250px;' class="wrapper page3">
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
        <div class="tables">
            <div class="container">
                <div class="subscriber">
                    <div class="container">
                        <table class="table-padding">
                            <tr>
                                <td width="75%">Subscriber Detail</td>
                                <td width="25%" colspan="3" class="right">
                                    @isset ($subscription['phone'])
                                        {{$subscription['phone']}}
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
                                <td>Plans:</td>
                                <td>
                                    <a>
                                        @isset ($subscription['plan_name'])
                                            {{ $subscription['plan_name'] }}
                                        @endisset
                                    </a>
                                </td>
                                <td class="right">
                                    <a>
                                        @isset ($subscription['plan_charges'])
                                            $ {{ $subscription['plan_charges'] }}
                                        @endisset
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                @if(count($subscription['addons']))
                                    <td>Features:</td>
                                    <td>
                                        <a>  
                                            @foreach ($subscription['addons'] as $addon)
                                                <div style='margin-left: 10px;'>{{$addon['name']}}</div>
                                            @endforeach
                                        </a>
                                    </td>                                    
                                    <td class="right">
                                        <a>
                                            @foreach ($subscription['addons'] as $addon)
                                                <div style='margin-left: 10px;'>$ {{$addon['charges']}}</div>
                                            @endforeach
                                        </a>
                                    </td>
                                    
                                @endif
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
                                            Total Plan Charges: $
                                            @isset ($subscription['plan_and_addons_total'])
                                                {{ $subscription['plan_and_addons_total'] }}
                                            @endisset
                                        </strong>
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                @if ($invoice['invoice_type'] == 2)
                    <div class="one_time">
                        <div class="container">
                            <div class="table-padding">
                                <h2>One-Time Charges</h2>
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
                                    <td>
                                        @isset ($subscription['device_name'])
                                            {{$subscription['device_name']}}
                                        @endisset
                                    </td>
                                    <td colspan='2' class='last'>
                                        @isset ($subscription['device_charges'])
                                            $ {{$subscription['device_charges']}}
                                        @endisset
                                    </td>
                                </tr>
                            
                                
                                <tr>
                                    <td>
                                        @isset ($subscription['sim_name'])
                                            {{$subscription['sim_name']}}
                                        @endisset
                                    </td>
                                    <td colspan='2' class='last'>
                                        @isset ($subscription['sim_charges'])
                                            $ {{$subscription['sim_charges']}}
                                        @endisset
                                    </td>
                                </tr>
                                @if (isset($subscription['activation_fee']) && $subscription['activation_fee'] > 0)
                                    <tr>
                                        <td>Activation Fee</td>
                                        <td colspan='2' class='last'>
                                            
                                            $ {{$subscription['activation_fee']}}

                                        </td>
                                    </tr>
                                @endif
                                @if (isset($subscription['shipping_fee']) && $subscription['shipping_fee'] > 0)
                                    <tr>
                                        <td>Shipping Fee</td>
                                        <td colspan='2' class='last'>
                                            
                                            $ {{$subscription['shipping_fee']}}

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
                                        <strong>Total One-Time Charges: 
                                            @isset ($subscription['total_one_time'])
                                                $ {{$subscription['total_one_time']}}
                                            @endisset
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
                                <td colspan="2" class="last"><a>$
                                    @isset ($subscription['regulatory_fee'])
                                        {{$subscription['regulatory_fee']}}
                                    @else
                                        0
                                    @endisset
                                </a></td>
                            </tr>
                            <tr>
                                <td>State</td>
                                <td colspan="2" class="last"><a>$
                                    @isset ($subscription['subscription_tax'])
                                        {{$subscription['subscription_tax']}}
                                    @else
                                        0
                                    @endisset
                                </a></td>
                            </tr>

                            <tr>
                                <td colspan="3">
                                    <div class="sepratorline dark"></div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" class="right total_value"><a><strong>Total Taxes/Fees: 
                                    @isset ($subscription['total_tax_and_fee'])
                                        {{$subscription['total_tax_and_fee']}}
                                    @else
                                        0
                                    @endisset
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
                                    @isset ($subscription['usage_charges'])
                                        {{$subscription['usage_charges']}}
                                    @else
                                        0
                                    @endisset
                                </strong></a></td>
                            </tr>
                           
                        </table>
                    </div>
                </div>
                <div class="credit">
                    <div class="container">
                        <div class="table-padding">

                            <h2>Coupons</h2>
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
                                    @isset ($subscription['coupons'])
                                        {{$subscription['coupons']}}
                                    @else
                                        0
                                    @endisset
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
                                    @if ($subscription['phone'] && $subscription['phone'] != 'Pending')
                                        {{$subscription['phone']}}
                                    @endisset
                                </td>
                                <td colspan="3" class="right">
                                    @isset ($subscription['total_subscription_charges'])
                                        $ {{$subscription['total_subscription_charges']}}
                                    @else
                                        0
                                    @endisset
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="container">
                    <h3>Page 
                    <strong> 
                        @isset ($subscription['page_count'])
                            {{$subscription['page_count']}}
                        @endisset
                    </strong>/
                        @isset ($invoice['max_pages'])
                            {{$invoice['max_pages']}}
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