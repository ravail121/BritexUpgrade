<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Teltik</title>
    <link href="https://teltik.pw/pdf/css/82style.css" type="text/css" rel="stylesheet">

    @include('templates.dynamic-invoice-branding')
</head>

<body>
    <div class="wrapper">
        <div class="container" style="width: 100%; float: none; margin:0 auto;">
            <div class="boxmain">
                <div class="head" style="padding:0 0 0;">
                    <div class="containerin">
                        <div class="logo" style=" width: 100%; text-align: center;">
                            <img src="https://teltik.pw/pdf/img/logo.png" alt="logo" style="padding: -10px 0 15px 0; width: 200px;">
                        </div>
                        <div class="invoice">
                            <h2>INVOICE</h2>
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tbody>
                                    <tr>
                                        <td>Invoice No.</td>
                                        <td width="20px"></td>
                                        <td class="detail">####</td>
                                    </tr>
                                    <tr>
                                        <td>Period Beginning</td>
                                        <td width="20px"></td>
                                        <td class="detail">2019/4/2</td>
                                    </tr>
                                    <tr>
                                        <td>Period Ending</td>
                                        <td width="20px"></td>
                                        <td class="detail">2019/4/2</td>
                                    </tr>
                                    <tr>
                                        <td>Due Date</td>
                                        <td width="20px"></td>
                                        <td class="detail">2019/4/2</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Customer Info -->
                        <div class="linksfooter">
                            <h3>Customer Info</h3>
                            <div class="customer_info">
                                <p><span>First Lastname</span></p>
                                <p><span>PO Box 555</span></p>
                                <p><span>Roadville, NY 87879</span></p>
                            </div>
                        </div>
                        
                        <div class="bill_info">
                            <h2>Your Monthly Bill As Of</h2>
                            <h3>4 Jan, 2019</h3>
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
                                                    <td class="detail">$106.46</td>
                                                </tr>
                                                <tr>
                                                    <td>Payments Received </td>
                                                    <td class="detail">-$499</td>
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
                                                    <td class="detail">$0.00</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                    <td class="titlebx">
                                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                            <tbody>
                                                <tr>
                                                    <td>Services, Usage &amp; Charges</td>
                                                    <td class="detail">$90.00</td>
                                                </tr>
                                                <tr>
                                                    <td>Fees/Taxes</td>
                                                    <td class="detail">$16.46</td>
                                                </tr>
                                                <tr>
                                                    <td>Credits</td>
                                                    <td class="detail">$0.00</td>
                                                </tr>
                                                <tr>
                                                    <td>Total Charges This Bill</td>
                                                    <td class="detail">$499</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                    <td class="titlebx">
                                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                            <tbody>
                                                <tr>
                                                    <td>Payments (Mar 1)</td>
                                                    <td class="detail">$90.00</td>
                                                </tr>
                                                <tr>
                                                    <td>Due Mar 1</td>
                                                    <td class="detail">$0.00</td>
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
                                    <th>Credits</th>
                                    <th>Total Current Charges</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Account Charges</td>
                                    <td>$50.00</td>
                                    <td>$0.00</td>
                                    <td>$0.00</td>
                                    <td>$0.00</td>
                                    <td>$0.00</td>
                                    <td>$50.00</td>
                                </tr>
                                <tr>
                                    <td>863-666-9879</td>
                                    <td>$30.00</td>
                                    <td>$0.00</td>
                                    <td>$0.00</td>
                                    <td>$5.49</td>
                                    <td>$0.00</td>
                                    <td>$35.49</td>
                                </tr>
                                <tr>
                                    <td>863-666-9878</td>
                                    <td>$40.00</td>
                                    <td>$0.00</td>
                                    <td>$0.00</td>
                                    <td>$6.15</td>
                                    <td>$0.00</td>
                                    <td>$46.15</td>
                                </tr>
                                <tr>
                                    <td>863-666-9877</td>
                                    <td>$20.00</td>
                                    <td>$0.00</td>
                                    <td>$0.00</td>
                                    <td>$4.83</td>
                                    <td>$0.00</td>
                                    <td>$24.83</td>
                                </tr>
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
                                <td>$90</td>
                                <td>$0.00</td>
                                <td>$0.00</td>
                                <td>$16.46</td>
                                <td>$0.00</td>
                                <td>$106.46</td>
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
</body>

</html>