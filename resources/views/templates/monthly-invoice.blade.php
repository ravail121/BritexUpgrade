<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Teltik</title>
    <link href="https://teltik.pw/pdf/css/82style.css" type="text/css" rel="stylesheet">

</head>

<body>
    <div tyle='margin-bottom:500px;' class="wrapper">
        <div class="container" style="width: 100%; float: none; margin: 0px auto;">
            <div style='position:relative;top:100px;' class="boxmain">
                <div class="head" style="padding: 0px 0px 0px;">
                    <div class="containerin">
                        <div class="logo" style=" width: 100%; text-align: center;">
                                <img src="{{ isset($data['order']->company->logo) ? $data['order']->company->logo : '' }}" style="padding: -10px 0px 15px 0px; width: 200px;" alt="logo">
                        </div>
                        <div style='margin-top:20px' class="invoice">
                            <h2>INVOICE</h2>
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tbody>
                                    <tr>
                                        <td>Invoice No.</td>
                                        <td width="20px"></td>
                                        <td class="detail">{{ $data['invoice']->id }}</td>
                                    </tr>
                                    <tr>
                                        <td>Period Beginning</td>
                                        <td width="20px"></td>
                                        <td class="detail">@date($data['order']->formatDate($data['order']->invoice->start_date))</td>
                                    </tr>
                                    <tr>
                                        <td>Period Ending</td>
                                        <td width="20px"></td>
                                        <td class="detail">@date($data['order']->formatDate($data['order']->invoice->end_date))</td>
                                    </tr>
                                    <tr>
                                        <td>Due Date</td>
                                        <td width="20px"></td>
                                        <td class="detail">@date($data['order']->formatDate($data['order']->invoice->due_date))</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Customer Info -->

                        <div style='position:absolute; left:0; right:0; margin: auto; top: 100px; border-color: transparent;' class="linksfooter">
                            <h3>Customer Info</h3>
                            <div class="customer_info">
                                <p><span>{{ $data['order']->customer->company_name }},</span></p>
                                <p><span>{{ $data['order']->customer->full_name }},</span></p>
                                <p><span>{{ $data['order']->customer->shipping_address1 }}</span></p>
                                <p><span>{{ $data['order']->customer->zip_address }}</span></p>
                            </div>
                        </div>
                        
                        <div style='position:absolute; right:15px; margin: auto; top: 100px; box-shadow:none; border-color: transparent;' class="bill_info">
                            <h2>Your Monthly Bill As Of</h2>
                            <h3>{{ $data['invoice']->dateFormatForInvoice($data['invoice']->created_at) }}</h3>
                        </div>
                        
                        <div class="info">
                                <h2>Important Information</h2>
                                <p>1. You are 
                                    <strong>
                                        @if (isset($data['order']->customer->auto_pay) && $data['order']->customer->auto_pay)
                                        
                                        @else 
                                            not
                                        @endif
                                    </strong> 
                                    enrolled in Autopay. Amount will 
                                    <strong>
                                        @if (isset($data['order']->customer->auto_pay) && $data['order']->customer->auto_pay)
                                    
                                        @else 
                                            not
                                        @endif    
                                    </strong> be forwarded for automatic processing.</p>
                                    <p>2. Pay online <a href="{{ isset($data['order']->company->url) ? $data['order']->company->url : '' }}">teltik.com</a></p>
                            </div>
                    </div>
                    <div class="billing_detail">
                        <div class="containerin">
                            <div class="titlebox">
                                <h3>Last Bill</h3>
                            </div>
                            <div class="titlebox">
                                <h3>Current Bill</h3>
                            </div>
                            <div class="titlebox">
                                <h3>Total Amount Due</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bill_detail">
                    <div class="containerin">
                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tbody>
                                <tr>
                                    <td class="titlebx">
                                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                            <tbody>
                                                <tr>
                                                    <td>Previous Balance</td>
                                                    <td class="detail">$ 
                                                        @isset ($data['previous_bill']['previous_amount'])
                                                            {{$data['previous_bill']['previous_amount']}}
                                                        @else
                                                            0.00
                                                        @endisset
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Payments Received </td>
                                                    <td class="detail">-$
                                                        @isset ($data['previous_bill']['previous_payment'])
                                                                {{$data['previous_bill']['previous_payment']}}
                                                        @else
                                                            0.00
                                                        @endisset
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="thankyou" colspan="2">
                                                        <div class="seprator"></div>
                                                        Thank you!
                                                        <div class="seprator"></div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Balance Forward</td>
                                                    <td class="detail">$
                                                        @isset ($data['previous_bill']['previous_pending'])
                                                            {{$data['previous_bill']['previous_pending']}}
                                                        @else
                                                            0.00
                                                        @endisset
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                    <td class="titlebx">
                                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                            <tbody>
                                                <tr>
                                                    <td>Services, Usage &amp; Charges</td>
                                                    <td class="detail">$ 
                                                        @if ($data['invoice']->cal_service_charges)
                                                            {{ number_format($data['invoice']->cal_service_charges, 2) }}
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Fees/Taxes</td>
                                                    <td class="detail">$ 
                                                        @if ($data['invoice']->cal_taxes)
                                                            {{ number_format($data['invoice']->cal_taxes, 2) }}
                                                        @endif
                                                    </td>
                                                </tr>
                                                
                                                <tr>
                                                    <td>Coupons 
                                                        <div class="seprator"></div>
                                                    </td>
                                                    <td class="detail">-$ 
                                                        @isset ($data['invoice']->cal_credits)
                                                            {{ number_format($data['invoice']->cal_credits, 2) }}
                                                        @endisset
                                                    </td>
                                                </tr>
                                                
                                                <tr>
                                                    <td>Total Charges This Bill</td>
                                                    
                                                    <td class="detail">$ 
                                                        @isset ($data['invoice']->subtotal)
                                                            {{ number_format($data['invoice']->subtotal, 2) }}
                                                        @endisset
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                    <td class="titlebx">
                                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                                <tbody>
                                                    <tr>
                                                        <td>Payments/Credits</td>
                                                        <td class="detail">$ 
                                                            @isset ($data['invoice']->creditsToInvoice)
                                                                {{ number_format($data['invoice']->creditsToInvoice->sum('amount'), 2) }}
                                                            @endisset
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Due {{ date('M', strtotime($data['invoice']->due_date)).' '.date('j', strtotime($data['invoice']->due_date)) }}</td>
                                                        <td class="detail">$ 
                                                            {{ $data['invoice']->total_due ? number_format($data['invoice']->total_due, 2) : '0.00' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <div class="seprator"></div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2">Let’s talk!<span> Call us anytime</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2">{{ isset($data['order']->company->support_phone_number) ? $data['order']->phoneNumberFormatted($data['order']->company->support_phone_number) : '' }}</td>
                                                    </tr>
                                                </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="account_info">
                    <div class="containerin">
                    <center>Account Summary</center>
                        <table>
                            <thead>
                                <tr>
                                    <th>Phone No.</th>
                                    <th>Plan Charges</th>
                                    <th>One Time Charges</th>
                                    <th>Usage Charges</th>
                                    <th>Taxes/Fees</th>
                                    <th>Coupons</th>
                                    <th>Total Current Charges</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($subscriptions))
                                    @foreach ($subscriptions as $index => $subscription)
                                        <tr>
                                            <td>@isset ($subscription->phone_number) 
                                                    {{ $data['order']->phoneNumberFormatted($subscription->phone_number) }}
                                                @else
                                                    Pending
                                                @endisset
                                            </td>
                                            
                                            <td>$ @if ($subscription->cal_plan_charges) 
                                                    {{ 
                                                        number_format (
                                                            $subscription->calculateChargesForAllproducts([1, 2], $data['invoice']->id, $subscription->id), 2
                                                        )
                                                    }} 
                                                @endif
                                            </td>
                                            <td>$ @if ($subscription->cal_onetime_charges)
                                                    {{ 
                                                        number_format (
                                                            $subscription->calculateChargesForAllproducts([3], $data['invoice']->id, $subscription->id), 2
                                                        )
                                                    }}
                                                @endif
                                            </td>
                                            <td>$ @if ($subscription->cal_usage_charges)
                                                    {{ number_format ( $subscription->calculateChargesForAllproducts([4], $data['invoice']->id, $subscription->id), 2) }}
                                                @endif
                                            </td>
                                            <td>$ @if($subscription->cal_taxes)
                                                    {{ number_format ($subscription->calculateChargesForAllproducts([7, 5], $data['invoice']->id, $subscription->id), 2) }}
                                                @endif
                                            </td>
                                            <td>-$ @if($subscription->cal_credits)
                                                    {{ 
                                                        number_format ( $subscription->calculateChargesForAllproducts([6, 8, 10], $data['invoice']->id, $subscription->id), 2)
                                                    }}
                                                @endif</td>
                                            <td>$ @if ($subscription->cal_total_charges)
                                                    {{ 
                                                        number_format(
                                                            $subscription->totalSubscriptionCharges($data['invoice']->id, $subscription) - 
                                                            $subscription->totalSubscriptionDiscounts($data['invoice']->id, $subscription), 2
                                                        ) 
                                                    }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                            <tr>
                                <td colspan="7" class="lh0">
                                    <div class="total_img">
                                        <img src="https://teltik.pw/pdf/img/shape.png" alt="shape">
                                    </div>
                                </td>
                            </tr>
                            
                            <tr class="tfootQ">
                                <td>Total</td>
                                <td>$ 
                                    @if($data['invoice']->cal_plan_charges)
                                        {{ number_format($data['invoice']->cal_plan_charges, 2) }}
                                    @endif
                                </td>
                                <td>$ 
                                    @if($data['invoice']->cal_onetime)
                                        {{ number_format($data['invoice']->cal_onetime, 2) }}
                                    @endif
                                </td>
                                <td>$ 
                                    @if($data['invoice']->cal_usage_charges)
                                        {{ number_format($data['invoice']->cal_usage_charges, 2) }}
                                    @endif
                                </td>
                                <td>$ 
                                    @if($data['invoice']->cal_taxes)
                                        {{ number_format($data['invoice']->cal_taxes, 2) }}
                                    @endif
                                </td>
                                <td>-$ 
                                    @if(isset($data['invoice']->cal_credits) && $data['invoice']->cal_credits)
                                        {{ number_format($data['invoice']->cal_credits, 2) }}
                                    @endif
                                    </td>
                                <td>$ 
                                    @if($data['invoice']->cal_total_charges)
                                    {{ 
                                        number_format($data['invoice']->cal_total_charges, 2)
                                    }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td colspan="7" class="lh0">
                                    <div class="total_img2">
                                        <img src="https://teltik.pw/pdf/img/shape.png" alt="shape">
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="7">&nbsp;</td>
                            </tr>
                        </table>
                        <div class="footer">
                            <div class="container">
                                <div class="center">
                                    <a href="#">Contact us: <td colspan="2">{{ isset($data['order']->company->support_phone_number) ? $data['order']->phoneNumberFormatted($data['order']->company->support_phone_number) : '' }}</td></a>
                                    <a href="{{ isset($data['order']->company->url) ? $data['order']->company->url : '' }}">teltik.com</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div style='text-align:center; margin-top: 35px; margin-bottom: 35px;' class="container">
                    <p>Page <strong> 1</strong>/
                        @if (count($data['order']->subscriptions))
                            {{ count($data['order']->subscriptions) + 2 }}
                        @else 
                            {{ count($subscriptions) + 2 }}
                        @endisset
                    </p>
                </div>
            </div>
        </div>
    </div>

</body>

</html>


@include('templates.test-statement')
@if (count($subscriptions))
    @foreach ($subscriptions as $index => $subscription)
        
        @include('templates.test-statement-2')

    @endforeach
@endif
