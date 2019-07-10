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
                                    @if (isset($invoice['subscriptions'][0]['phone']))
                                        {{ $invoice['subscriptions'][0]['phone'] }}
                                    @else
                                        <p></p>
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
                                        {{ $invoice['start_date'] }} - {{ $invoice['end_date'] }}
                                    </a>
                                </td>
                                <td width="17%"></td>
                            </tr>
                            <tr>
                                <td>Plans:</td>
                                <td>
                                    <a>
                                        @isset ($invoice['plans'])
                                            @if (count($invoice['plans']) > 0)                                       
                                                @foreach ($invoice['plans'] as $key => $val)
                                                    <div style='margin-left: 10px;'>{{ $invoice['plans'][$key]['name'] }}</div>
                                                @endforeach
                                            @endif
                                        @endisset
                                    </a>
                                </td>
                                <td class="right">
                                    <a>
                                        @isset ($invoice['plans'])
                                            @if (count($invoice['plans']) > 0)                                       
                                                @foreach ($invoice['plans'] as $key => $val)
                                                    <div style='margin-left: 10px;'>${{ number_format($invoice['plans'][$key]['amount'], 2) }}</div>
                                                @endforeach
                                            @endif
                                        @endisset
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td>Features:</td>
                                <td>
                                    <a>  
                                        @isset ($invoice['addons'])                      
                                            @if (count($invoice['addons']) > 0)                                       
                                                @foreach ($invoice['addons'] as $key => $val)
                                                    <div style='margin-left: 10px;'>{{ $invoice['addons'][$key]['name'] }}</div>
                                                @endforeach
                                            @endif
                                        @endisset
                                    </a>
                                </td>
                                <td class="right">
                                    <a>
                                        @isset ($invoice['addons']) 
                                            @if (count($invoice['addons']) > 0)                                    
                                                @foreach ($invoice['addons'] as $key => $val)
                                                    <div style='margin-left: 10px;'>${{ number_format($invoice['addons'][$key]['amount'], 2) }}</div>
                                                @endforeach
                                            @endif
                                        @endisset
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
                                            Total Plan Charges: $
                                            @isset ($invoice['plan_charges'])
                                                {{ $invoice['plan_charges'] }}
                                            @endisset
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
	                                    <div class="sepratorline"></div>
	                                </td>
	                            </tr>
	                        </table>
                        </div>
                        <table class="test table-padding">
                            @isset($invoice['subscription_items']['devices'])
                            @foreach($invoice['subscription_items']['devices'] as $item)
                                <tr>
                                    <td>{{$item['name']}}</td>
                                    <td colspan='2' class='last'>
                                        {{number_format($item['amount'], 2)}}
                                    </td>
                                </tr>
                            @endforeach
                            @endisset
                            @isset($invoice['subscription_items']['sims'])
                            @foreach($invoice['subscription_items']['sims'] as $item)
                                <tr>
                                    <td>{{$item['name']}}</td>
                                    <td colspan='2' class='last'>
                                        {{number_format($item['amount'], 2)}}
                                    </td>
                                </tr>
                            @endforeach
                            @endisset
                            @isset($invoice['subscription_act_fee'])
                                @if ($invoice['subscription_act_fee'] != 0)
                                    <tr>
                                        <td>Activation Fee</td>
                                        <td colspan='2' class='last'>
                                            
                                            {{$invoice['subscription_act_fee']}}

                                        </td>
                                    </tr>
                                @endif
                            @endisset
                            <tr>
                                
                                <td colspan="3">
                                    <div class="sepratorline dark"></div>
                                </td>
                                
                            </tr>
                            <td colspan="2" class="last total_value">
                                <a>
                                    <strong>Total One-Time Charges: 
                                        @isset ($invoice['subscription_total_one_time'])
                                            {{ $invoice['subscription_total_one_time'] }}
                                        @endisset
                                    </strong>
                                </a>
                            </td>
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
                                <td colspan="2" class="last"><a>$
                                    @isset ($invoice['subscription_total_reg_fee'])
                                        {{ $invoice['subscription_total_reg_fee'] }}
                                    @endisset
                                </a></td>
                            </tr>
                            <tr>
                                <td>State</td>
                                <td colspan="2" class="last"><a>$
                                    @isset ($invoice['subscription_total_tax'])
                                        {{ $invoice['subscription_total_tax'] }}
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
                                    @isset ($invoice['subscription_total_tax_fee'])
                                        {{ $invoice['subscription_total_tax_fee'] }}
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
                                    @isset ($invoice['subscription_usage_charges'])
                                        {{ $invoice['subscription_usage_charges'] }}
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
                                    @isset ($invoice['subscription_coupons'])
                                        {{ $invoice['subscription_coupons'] }}
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
                                    @if (isset($invoice['subscriptions'][0]['phone']) && $invoice['subscriptions'][0]['phone'] != 'Pending')
                                        ({{ $invoice['subscriptions'][0]['phone'] }})
                                        
                                    @else
                                        <p></p>
                                    @endif 
                                </td>
                                <td colspan="3" class="right">
                                    @isset ($invoice['subscription_total'])
                                        ${{ $invoice['subscription_total'] }}
                                    @endisset
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="container">
                    <h3>Page <strong> 3</strong>/3 </h3>
                </div>
                <div style='page-break-after:always;'>&nbsp;</div>                
            </div>
        </div>
    </div>
</body>

</html>