<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Teltik</title>
    <link href="https://teltik.pw/pdf/css/82style.css" type="text/css" rel="stylesheet">

</head>

<body>
    <div style='margin-bottom:500px;' class="wrapper">
        <div class="container" style="width: 100%; float: none; margin: 0px auto;">
            <div style='position:relative;top:100px;' class="boxmain">
                <div class="head" style="padding: 0px 0px 0px;">
                    <div class="containerin">
                        <div class="logo" style="width: 100%; text-align: center;">
                            <img src="{{ isset($invoice['company_logo']) ? $invoice['company_logo'] : '' }}" style="padding: -10px 0px 15px 0px; width: 200px;" alt="logo">
                        </div>
                        <div style='margin-top:20px' class="invoice">
                            <h2>INVOICE</h2>
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tbody>
                                    <tr>
                                        <td>Invoice No.</td>
                                        <td width="20px"></td>
                                        <td class="detail">{{ $invoice['invoice_num'] }}</td>
                                    </tr>
                                    <tr>
                                        <td>Period Beginning</td>
                                        <td width="20px"></td>
                                        <td class="detail">@date($invoice['start_date'])</td>
                                    </tr>
                                    <tr>
                                        <td>Period Ending</td>
                                        <td width="20px"></td>
                                        <td class="detail">@date($invoice['end_date'])</td>
                                    </tr>
                                    <tr>
                                        <td>Due Date</td>
                                        <td width="20px"></td>
                                        <td class="detail">@date($invoice['due_date'])</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Customer Info -->
                        <div style='position:absolute; left:0; right:0; margin: auto; top: 100px; border-color: transparent;' class="linksfooter">
                            <h3>Customer Info</h3>
                            <div class="customer_info">
                                <p><span>{{ $invoice['company_name'] }},</span></p>
                                <p><span>{{ $invoice['customer_name'] }},</span></p>
                                <p><span>{{ $invoice['customer_address'] }}</span></p>
                                <p><span>{{ $invoice['customer_zip_address'] }}</span></p>
                            </div>
                        </div>
                        
                        <div style='position:absolute; right:15px; margin: auto; top: 100px; border-color: transparent; box-shadow:none;' class="bill_info">
                            <h2>Bill for</h2>
                            <h3>{{ $invoice['today_date'] }}</h3>
                        </div>
                    
                        <div class="info">
                                <h2>Important Information</h2>
                                <p>1. You are 
                                    <strong>
                                        @if (isset($invoice['customer_auto_pay']) && $invoice['customer_auto_pay'])
                                        
                                        @else 
                                            not
                                        @endif
                                    </strong> 
                                    enrolled in Autopay. Amount will 
                                    <strong>
                                        @if (isset($invoice['customer_auto_pay']) && $invoice['customer_auto_pay'])
                                    
                                        @else 
                                            not
                                        @endif    
                                    </strong> be forwarded for automatic processing.</p>
                                <p>2. Pay online <a href="{{ isset($invoice['reseller_domain']) ? $invoice['reseller_domain'] : '' }}">teltik.pw</a></p>
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
                                                    <td class="detail">0.00</td>
                                                </tr>
                                                <tr>
                                                    <td>Payments Received </td>
                                                    <td class="detail">0.00</td>
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
                                                    <td class="detail">0.00</td>
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
                                                        @isset ($invoice['service_charges'])
                                                            {{ $invoice['service_charges'] }}
                                                        @endisset
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Fees/Taxes</td>
                                                    <td class="detail">$ 
                                                        @isset ($invoice['taxes'])
                                                            {{ $invoice['taxes'] }}
                                                        @endisset
                                                    </td>
                                                </tr>
                                                
                                                <tr>
                                                    <td>Coupons 
                                                        <div class="seprator"></div>
                                                    </td>
                                                    <td class="detail">-$ 
                                                        @isset ($invoice['credits'])
                                                            {{ $invoice['credits'] }}
                                                        @endisset
                                                    </td>
                                                </tr>
                                                
                                                <tr>
                                                    <td>Total Charges This Bill</td>
                                                    
                                                    <td class="detail">$ 
                                                        @isset ($invoice['subtotal'])
                                                            {{ $invoice['subtotal'] }}
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
                                                            {{ !empty($invoice['total_credits_to_invoice']) ? $invoice['total_credits_to_invoice'] : '0.00' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Due {{ date('M', strtotime($invoice['due_date'])).' '.date('j', strtotime($invoice['due_date'])) }}</td>
                                                    <td class="detail">$ @isset ($invoice['total_due'])
                                                            {{ $invoice['total_due'] }}
                                                        @endisset
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
                                                    <td colspan="2">{{ isset($invoice['reseller_phone_number']) ? $invoice['reseller_phone_number'] : '' }}</td>
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
                                <!--
                                <tr>
                                    <td>Account Charges</td>
                                    <td>$</td>
                                    <td>$ </td>
                                    <td>$ </td>
                                    <td>$ </td>
                                    <td>-$ </td>
                                    <td>$ </td>
                                </tr>
                            -->

                            <tr class="tfootQ">
                                <td>Account Charges</td>
                                <td>$ 0.00</td>
                                <td>$ 
                                    @isset($invoice['standalone_data']['standalone_onetime_charges'])
                                        {{ $invoice['standalone_data']['standalone_onetime_charges'] }}
                                    @endisset
                                </td>
                                <td>$ 0.00</td>
                                <td>$ 
                                    @isset($invoice['standalone_data']['taxes'])
                                        {{ $invoice['standalone_data']['taxes'] }}
                                    @endisset
                                </td>
                                <td>-$ 

                                    @isset($invoice['standalone_data']) 
                                        {{$invoice['standalone_data']['coupons']}} 
                                    @endisset
                                </td>
                                <td>$ 
                                    @isset($invoice['standalone_data']['total'])
                                        {{ $invoice['standalone_data']['total'] }}
                                    @endisset
                                </td>
                            </tr>

                                @if (isset($invoice['subscriptions']) && count($invoice['subscriptions']))
                                    @foreach ($invoice['subscriptions'] as $subscription)
                                        <tr>
                                            <td>@isset ($subscription['phone']) 
                                                    {{ $subscription['phone'] }} 
                                                @endisset
                                            </td>
                                            <td>$ @isset ($subscription['plan_charges']) 
                                                    {{ $subscription['plan_charges'] }} 
                                                @endisset
                                            </td>
                                            <td>$ @isset ($subscription['onetime_charges'])
                                                    {{ $subscription['onetime_charges'] }}
                                                @endisset
                                            </td>
                                            <td>$ @isset ($subscription['usage_charges'])
                                                    {{ $subscription['usage_charges'] }}
                                                @endisset
                                            </td>
                                            <td>$ @isset($subscription['tax'])
                                                    {{ $subscription['tax'] }}
                                                @endisset
                                            </td>
                                            <td>-$ 0.00</td>
                                            <td>$ @isset ($subscription['total'])
                                                    {{ $subscription['total'] }}
                                                @endisset
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                            <tr>
                                <td colspan="7" class="lh0">
                                    <div class="total_img">
                                        <img src="https://teltik.pw/pdf/img/shape.png" alt="">
                                    </div>
                                </td>
                            </tr>
                            <tr class="tfootQ">
                                <td>Total</td>
                                <td>$ 
                                    @isset($invoice['plan_charges'])
                                        {{$invoice['plan_charges']}}
                                    @endisset
                                </td>
                                <td>$ 
                                    @isset($invoice['total_one_time_charges'])
                                        {{ $invoice['total_one_time_charges'] }}
                                    @endisset
                                </td>
                                <td>$ 
                                    @isset($invoice['total_usage_charges'])
                                        {{ $invoice['total_usage_charges'] }}
                                    @endisset
                                </td>
                                <td>$ 
                                    @isset($invoice['tax_and_shipping'])
                                        {{ $invoice['tax_and_shipping'] }}
                                    @endisset
                                </td>
                                <td>-$ 
                                    @isset($invoice['credits'])
                                        {{ $invoice['credits'] }}
                                    @endisset
                                    </td>
                                <td>$ 
                                    @isset($invoice['total_account_summary_charge'])
                                    {{ 
                                        $invoice['total_account_summary_charge']
                                    }}
                                    @endisset
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
                                    <a href="#">Contact us: {{ isset($invoice['reseller_phone_number']) ? $invoice['reseller_phone_number'] : '' }}</a>
                                    <a href="{{ isset($invoice['reseller_phone_number']) ? $invoice['reseller_phone_number'] : '' }}">teltik.pw</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div style='text-align:center; margin-top: 35px; margin-bottom: 35px;' class="container">
                    <p>Page <strong> 1</strong>/
                        @isset ($invoice['max_pages'])
                            {{$invoice['max_pages']}}
                        @else 
                            3
                        @endisset
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>

@include('templates.test-statement')

@if (isset($invoice['subscription_per_page']) && count($invoice['subscription_per_page']))

    @foreach ($invoice['subscription_per_page'] as $subscription)

        @include('templates.test-statement-2')

    @endforeach

@endif

    