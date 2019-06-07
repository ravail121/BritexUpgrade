<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Teltik</title>
    <link href="https://teltik.pw/pdf/css/82style.css" type="text/css" rel="stylesheet">

</head>

<body>
    <div class="wrapper">
        <div class="container" style="width: 100%; float: none; margin: 0px auto;">
            <div class="boxmain">
                <div class="head" style="padding: 0px 0px 0px;">
                    <div class="containerin">
                        <div class="logo" style=" width: 100%; text-align: center;">
                            <img src="https://teltik.pw/pdf/img/logo.png" style="padding: -10px 0px 15px 0px; width: 200px;" alt="logo">
                        </div>
                        <div class="invoice">
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
                        <div style='position:absolute; left:0; right:0; margin: auto; top: 65px;' class="linksfooter">
                            <h3>Customer Info</h3>
                            <div class="customer_info">
                                <p><span>{{ $invoice['customer_name'] }}</span></p>
                                <p><span>{{ $invoice['customer_address'] }}</span></p>
                                <p><span>{{ $invoice['customer_zip_address'] }}</span></p>
                            </div>
                        </div>
                        
                        <div class="bill_info">
                            <h2>Bill for</h2>
                            <h3>{{ $invoice['today_date'] }}</h3>
                        </div>
                    
                        <div class="info">
                            <h2>Important Information</h2>
                            <p>1. You are <strong>not</strong> enrolled in Autopay. Amount will <strong>not</strong> be forwarded for automatic processing.</p>
                            <p>2. Pay online <a href="http://www.ResellerDomain.com">ResellerDomain.com</a></p>
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
                                                    <td class="detail">--</td>
                                                </tr>
                                                <tr>
                                                    <td>Payments Received </td>
                                                    <td class="detail">--</td>
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
                                                    <td class="detail">--</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                    <td class="titlebx">
                                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                            <tbody>
                                                <tr>
                                                    <td>Services, Usage &amp; Charges</td>
                                                    <td class="detail">$ {{ $invoice['service_charges'] }}</td>
                                                </tr>
                                                <tr>
                                                    <td>Fees/Taxes</td>
                                                    <td class="detail">$ {{ $invoice['taxes'] }}</td>
                                                </tr>

                                                <tr>
                                                    <td>Shipping fee</td>
                                                    <td class="detail">$ {{ isset($invoice['shipping_fee']) ? $invoice['shipping_fee'] : 0 }}</td>
                                                </tr>
                                                
                                                <tr>
                                                    <td>Coupons 
                                                        <div class="seprator"></div>
                                                    </td>
                                                    <td class="detail">-$ {{ $invoice['credits'] }}</td>
                                                </tr>
                                                
                                                <tr>
                                                    <td>Total Charges This Bill</td>
                                                    
                                                    <td class="detail">$ 
                                                        {{ 
                                                           $invoice['subtotal']
                                                        }}
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
                                                    <td class="detail">$ {{ $invoice['total_due'] }}</td>
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
                                                    <td colspan="2">Reseller Phone Number</td>
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
                                @if (count($invoice['subscriptions']))
                                    @foreach ($invoice['subscriptions'] as $subscription)
                                        <tr>
                                            <td>{{ $subscription['phone'] }}</td>
                                            <td>$ {{ $subscription['plan_charges'] }}</td>
                                            <td>$ {{ $subscription['onetime_charges'] }}</td>
                                            <td>$ {{ $subscription['usage_charges'] }}</td>
                                            <td>$ {{ $subscription['tax'] }}</td>
                                            <td>-$ 0.00</td>
                                            <td>$ {{ 
                                                    $subscription['total']
                                                }}
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
                                    
                                <td>Standalone</td>
                                <td>$ 0.00</td>
                                <td>$ {{ isset($invoice['standalone_data']) ?
                                            $invoice['standalone_data']['standalone_onetime_charges'] : 0 }}</td>
                                <td>$ 0.00</td>
                                <td>$ {{  isset($invoice['standalone_data']) ?
                                $invoice['standalone_data']['taxes'] : 0}}</td>
                                <td>-$ 0.00</td>
                                <td>$ 
                                    {{  isset($invoice['standalone_data']) ?
                                        $invoice['standalone_data']['total'] : 0 }}

                                </td>
                            </tr>

                            <tr class="tfootQ">
                                <td>Total</td>
                                <td>$ {{ $invoice['plan_charges'] }}</td>
                                <td>$ {{ $invoice['total_one_time_charges'] }}</td>
                                <td>$ {{ $invoice['total_usage_charges'] }}</td>
                                <td>$ {{ $invoice['tax_and_shipping'] }}</td>
                                <td>-$ {{ $invoice['credits'] }}</td>
                                <td>$ {{ 
                                        $invoice['subtotal']
                                    }}
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
                                    <a href="#">Contact us: 1-800-555-1212</a>
                                    <a href="#">ResellerDomain.com</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('templates.test-statement')
    @include('templates.test-statement-2')
</body>
        
</html>