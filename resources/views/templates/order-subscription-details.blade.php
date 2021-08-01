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

    