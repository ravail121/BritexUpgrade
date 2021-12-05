<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $data['order']->company->name }}</title>
    <link href="https://teltik.pw/pdf/css/82style.css" type="text/css" rel="stylesheet">
</head>

<body>
    <div style='position:relative;margin-top:250px;' class="wrapper page3">
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
                            @foreach ($planChange['order']->invoice->invoiceItem->where('type', 4) as $usage)
                                <tr>
                                    <td>{{ $usage['description'] }}</td>
                                    <td colspan="3" class="right"> $&nbsp;{{ number_format($usage['amount'], 2) }} </td>
                                </tr>
                            @endforeach
                            <tr>
                                <td colspan="3">
                                    <div class="sepratorline dark"></div>
                                </td>
                            </tr>
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

    