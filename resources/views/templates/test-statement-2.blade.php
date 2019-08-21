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
                                    @isset ($subscription->phone_number)
                                        {{ $data['order']->phoneNumberFormatted($subscription->phone_number) }}
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
                                        {{ $data['order']->formatDate($data['order']->invoice->start_date) }} - {{ $data['order']->formatDate($data['order']->invoice->end_date) }}
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
                                        @endisset
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                @if (!isset($ifUpgradeOrDowngradeInvoice))
                                    @if(isset($subscription->subscriptionAddon) && count($subscription->subscriptionAddon))
                                        <td>Features:</td>
                                        <td>
                                            @foreach ($subscription->subscriptionAddon as $item)
                                                <a>
                                                    @if ($subscription->getAddonData($item, $data['invoice']->id))
                                                        <div style='margin-left: 10px;'>{{$subscription->getAddonData($item, $data['invoice']->id)['name']}}</div>
                                                    @endif
                                                </a>
                                            @endforeach
                                        </td>                                    
                                        <td class="right">
                                            @foreach ($subscription->subscriptionAddon as $item) 
                                                <a>
                                                    @if ($subscription->getAddonData($item, $data['invoice']->id))
                                                        <div> $ {{ number_format ($subscription->getAddonData($item, $data['invoice']->id)['amount'], 2) }} </div>
                                                    @endif
                                                </a>
                                            @endforeach
                                        </td>
                                    @endif
                                @else
                                    @if (count($ifUpgradeOrDowngradeInvoice['addon_data']))
                                        <td>Features:</td>
                                        <td>
                                            @foreach ($ifUpgradeOrDowngradeInvoice['addon_data'] as $addon)
                                                <a>
                                                    <div style='margin-left: 10px;'>{{ $addon['name'].' ('.$addon['description'].')' }}</div>
                                                </a>
                                            @endforeach
                                        </td>
                                        <td class="right">
                                            @foreach ($ifUpgradeOrDowngradeInvoice['addon_data'] as $addon)
                                                <a>
                                                    <div> $ {{ number_format($addon['amount'], 2) }}</div>
                                                </a>
                                            @endforeach
                                        </td>
                                    @endif
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
                                                @if ($subscription->calculateChargesForAllproducts([1, 2], $data['invoice']->id, $subscription->id))
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
    
                @if ($data['order']->invoice->type == 2 && !isset($ifUpgradeOrDowngradeInvoice))
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
                            @if ($subscription->device_id)
                                <tr>
                                    <td>
                                        {{ $subscription->device->getDeviceName($subscription->device_id) }}
                                    </td>
                                    <td colspan='2' class='last'>
                                        $ {{ number_format ($subscription->device->deviceWithSubscriptionCharges($subscription->device_id), 2) }}
                                    </td>
                                </tr>
                            @endif
                            @if ($subscription->sim_id)
                                <tr>
                                    <td>
                                        {{ $subscription->simDetail->getSimName($subscription->sim_id) }}
                                    </td>
                                    
                                    <td colspan='2' class='last'>
                                        $ {{ number_format ($subscription->simDetail->getSimCharges($subscription->sim_id), 2) }}
                                    </td>
                                </tr>
                            @endif
                            @if ($subscription->invoiceItemDetail->where('description', 'Activation Fee')->sum('amount'))
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
                            @if ($subscription->invoiceItemDetail->where('description', 'Shipping Fee')->sum('amount'))
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
                                    <strong>Total One-Time Charges: 
                                        @if ($subscription->invoiceItemDetail->where('type', 3)->sum('amount'))
                                            $ {{
                                                number_format (
                                                    $subscription->invoiceItemDetail->where('type', 3)->sum('amount'), 2
                                                )
                                            }}
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
	                            <tr>
	                                <td colspan="3">
	                                    <div class="sepratorline"></div>
	                                </td>
	                            </tr>
	                        </table>
                        </div>
                        <table class="test table-padding">
                            @if (!isset($ifUpgradeOrDowngradeInvoice))
                                <tr>
                                    <td>Regulatory</td>
                                    <td colspan="2" class="last"><a>$
                                        
                                        @if ($subscription->calculateChargesForAllproducts([5], $data['invoice']->id, $subscription->id))
                                            {{ number_format( $subscription->calculateChargesForAllproducts([5], $data['invoice']->id, $subscription->id), 2) }}
                                        @else
                                            0.00
                                        @endif
                                    
                                    </a></td>
                                </tr>
                            @endif
                            <tr>
                                <td>State</td>
                                <td colspan="2" class="last"><a>$
                                    @if (!isset($ifUpgradeOrDowngradeInvoice))
                                        @if ($subscription->calculateChargesForAllproducts([7], $data['invoice']->id, $subscription->id))
                                            {{ number_format( $subscription->calculateChargesForAllproducts([7], $data['invoice']->id, $subscription->id), 2) }}
                                        @else
                                            0.00
                                        @endif
                                    @else 
                                        {{ number_format($data['invoice']->cal_taxes, 2) }}
                                    @endif
                                </a></td>
                            </tr>

                            <tr>
                                <td colspan="3">
                                    <div class="sepratorline dark"></div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" class="right total_value"><a><strong>Total Taxes/Fees: $
                                    @if (!isset($ifUpgradeOrDowngradeInvoice))
                                        @if ($subscription->calculateChargesForAllproducts([5], $data['invoice']->id, $subscription->id))
                                            {{ number_format( $subscription->calculateChargesForAllproducts([7,5], $data['invoice']->id, $subscription->id), 2) }}
                                        @else
                                            0.00
                                        @endif
                                    @else
                                        {{ number_format($data['invoice']->cal_taxes, 2) }}
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
                                    @if ($subscription->calculateChargesForAllproducts([4], $data['invoice']->id, $subscription->id))
                                        {{ number_format( $subscription->calculateChargesForAllproducts([4], $data['invoice']->id, $subscription->id), 2) }}
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
                                    @if ($subscription->calculateChargesForAllproducts([6, 8, 10], $data['invoice']->id, $subscription->id))
                                        {{ number_format( $subscription->calculateChargesForAllproducts([6, 8, 10], $data['invoice']->id, $subscription->id), 2) }}
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
                                    @if ($subscription['phone'] && $subscription['phone'] != 'Pending')
                                        {{$subscription['phone']}}
                                    @else 
                                        Pending
                                    @endisset
                                </td>
                                <td colspan="3" class="right">
                                    @isset ($subscription->invoiceItemDetail)
                                        $ {{
                                            number_format(
                                                $subscription->totalSubscriptionCharges($data['invoice']->id, $subscription) - 
                                                $subscription->totalSubscriptionDiscounts($data['invoice']->id, $subscription), 2
                                            ) 
                                        }}
                                    @else
                                        0.00
                                    @endisset
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="container">
                    <h3>Page 
                        <strong> 
                            {{$index + 3}}    
                        </strong>/ 
                        @if (count($data['order']->subscriptions))
                            {{ count($data['order']->subscriptions) + 2 }}
                        @else 
                            {{ count($subscriptions) + 2 }}
                        @endisset
                    </h3>
                </div>
                <div style='page-break-after:always;'>&nbsp;</div>                
            </div>
        </div>
    </div>
</body>

</html>

    