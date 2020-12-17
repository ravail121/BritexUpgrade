<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $data['order']->company->name }}</title>
    <link href="{{ $data['order']->company->url }}/pdf/css/82style.css" type="text/css" rel="stylesheet">
</head>

<body>
    <div style='position:relative;margin-top:400px;' class="wrapper page1">
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
                                    @if ($data['credits']->count())
                                        @foreach ($data['credits'] as $c)
                                            <td>
                                                Payment on
                                                {{ str_replace('-', '/', $data['order']->formatDate($c->credit->date)) }} 
                                                with
                                                {{ ucwords(str_replace('-', ' ', $c->credit->description)) }}
                                            </td>
                                            <td colspan="2" class="last"><a>$
                                                @if ($c->amount)
                                                    {{ number_format($c->amount, 2) }}
                                                @endif
                                            </a></td>
                                        @endforeach
                                    @else
                                        <td>Payment</td>
                                        <td colspan="2" class="last"><a>$ 0.00</a></td>
                                    @endif
                                </tr>
                                @if(count($data['order']->credits))
                                    <tr>
                                        <td>
                                            @if ($data['order']->oldCredits($data['order']))
                                                Credit on 
                                                {{ str_replace('-', '/', $data['order']->credits->first()->date) }}
                                            @endif
                                        </td>
                                        <td colspan="2" class="last"><a>
                                            @if ($data['order']->oldCredits($data['order']))
                                                $ {{ number_format($data['order']->oldCredits($data['order']), 2) }}
                                            @endif
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
                                    @if ($data['invoice']->creditsToInvoice->sum('amount'))
                                        {{ number_format($data['invoice']->creditsToInvoice->sum('amount'), 2) }}
                                    @else 
                                        0.00
                                    @endif
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
                            @if (count($data['standalone_items']->where('type', 3)))
                                <div class="sepratorline"></div>
                            @else

                            @endif
                        </div>

                        <table class="test table-padding">
                            <tbody>
                                @if (count($data['standalone_items']->where('type', 3)))
                                    @foreach($data['standalone_items']->where('type', 3)->whereNotIn('description', 'Shipping Fee') as $item)
                                        <tr>
                                            <td>
                                                @if ($item['product_type'] == 'device')
                                                    {{ $item->standaloneDevice()->first()->name }}
                                                @elseif ($item['product_type'] == 'sim')
                                                    {{ $item->standaloneSim()->first()->name }}
                                                @elseif ($item['description'] == 'Activation Fee')
                                                    Activation Fee
                                                @endif
                                            </td>
                                            <td colspan='2' class='last'>
                                                <span>$</span> {{number_format($item['amount'], 2)}}
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                                @if (count($data['standalone_items']->where('type', 3)->where('description', 'Shipping Fee')))
                                    <tr>
                                        <td>
                                            Shipping fee
                                        </td>
                                        <td colspan='2' class='last'>
                                            <span>$</span> {{number_format($data['standalone_items']->where('type', 3)->where('description', 'Shipping Fee')->sum('amount'), 2)}}
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <td colspan="3">
                                        <div class="sepratorline dark"></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td colspan="2" class="last total_value"><a><strong>
                                        Total One-Time Charges: $
                                        @if ($data['standalone_items']->where('type', 3)->sum('amount'))
                                            {{ number_format($data['standalone_items']->where('type', 3)->sum('amount'), 2) }}
                                        @else 
                                            0.00
                                        @endif
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
                            @if (count($data['standalone_items']->where('type', 7)))
                                <div class="sepratorline"></div>
                            @endif
                        </div>
                        <table class="test table-padding">
                            <tbody>
                                <tr>
                                    @if (count($data['standalone_items']->where('type', 7)))
                                        <td>State</td>
                                        <td colspan="2" class="last"><a>$ {{ number_format($data['standalone_items']->where('type', 7)->sum('amount'), 2) }} </a></td>
                                    @endif
                                </tr>

                                <tr>
                                    <td colspan="3">
                                        <div class="sepratorline dark"></div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="right total_value"><a><strong>Total Taxes/Fees: $
                                        @if (count($data['standalone_items']->where('type', 7)))
                                            {{ number_format($data['standalone_items']->where('type', 7)->sum('amount'), 2) }}
                                        @else 
                                            0.00
                                        @endif
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

                            <?php
                                $coupons = $data['order']->invoice->invoiceItem->where('type', 6)->where('subscription_id', null);
                            ?>
                            @if ($coupons->count())
                                <table>
                                    <tr>
                                        <td colspan="3">
                                            <div class="sepratorline"></div>
                                        </td>
                                    </tr>
                                </table>
                            @endif
                            <table class="test">
                                @foreach ($coupons as $coupon)
                                    <tr>
                                        <td>{{ $coupon['description'] }}</td>
                                        <td colspan="3" class="right"> $&nbsp;{{ number_format($coupon['amount'], 2) }} </td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td colspan="3"></td>
                                </tr>
                            </table>
                            <div class="sepratorline dark"></div>
                        </div>
                        <table class="test table-padding">
                            <tbody>
                                <tr>
                                    <td></td>
                                    <td colspan="2" class="last total_value"><a><strong>Total Coupons: - $
                                        @if (count($data['standalone_items']->whereIn('type', [6, 8])))
                                            {{ number_format($data['standalone_items']->whereIn('type', [6, 8])->sum('amount'), 2) }}
                                        @else
                                            0.00
                                        @endif
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
                                    <td colspan="3" class="right">
                                        @if ($data['invoice']->standAloneTotal($data['invoice']->id))
                                            ${{ number_format($data['invoice']->standAloneTotal($data['invoice']->id), 2) }}
                                        @else 
                                            $ 0.00
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="container">
                    <h3>Page <strong> 2</strong>/
                        @if (!isset($planChange))
                            @if (isset($subscriptions) && count($subscriptions))
                                {{ count($subscriptions) + 2 }}
                            @elseif (isset($data['order']->subscriptions) && count($data['order']->subscriptions))
                                {{ count($data['order']->subscriptions) + 2 }}
                            @else 
                            3
                            @endif
                        @else 
                            3
                        @endif
                    </h3>
                </div>
                <div style='page-break-after:always;'>&nbsp;</div>
            </div>
        </div>
    </div>
</body>

</html>