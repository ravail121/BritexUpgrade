<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Teltik</title>
    <link href="{{ asset('pdf/css/81style.css') }}" type="text/css" rel="stylesheet">
</head>

<body>
    <div class="wrapper">
        <div class="container">
            <div class="boxmain">
                <div class="head">
                    <div class="containerin">
                        <div class="logo">
                            <img src="{{ asset('pdf/img/logo.png') }}" alt="logo">
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
                                        <td class="detail">{{ str_replace('-', '/', $invoice['start_date']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Period Ending</td>
                                        <td width="20px"></td>
                                        <td class="detail">{{ str_replace('-', '/', $invoice['end_date']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Due Date</td>
                                        <td width="20px"></td>
                                        <td class="detail">{{ str_replace('-', '/', $invoice['due_date']) }}</td>
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
                                        <div>
                                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                                <tbody>
                                                    <tr>
                                                        <td>Previous Balance</td>
                                                        <td class="detail">$106.46</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Payments Received </td>
                                                        <td class="detail">-{{ $invoice['subtotal'] }}</td>
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
                                        </div>
                                    </td>
                                    <td class="titlebx">
                                        <div>
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
                                                        <td class="detail">{{ $invoice['subtotal'] }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                    <td class="titlebx">
                                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                            <div>
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
                                            </div>
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
                            <tr><td colspan="7" class="lh0">
                              <div class="total_img">
                                <img src="{{ asset('pdf/img/shape.png') }}" alt="shape">
                            </div></td>
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
                              <td colspan="7" class="lh0"><div class="total_img2">
                              <img src="{{ asset('pdf/img/shape.png') }}" alt="shape">
                            </div></td>
                            </tr>
                            <tr>
                              <td colspan="7">&nbsp;</td>
                            </tr>
                        </table>
                        <!-- <div class="total_img">
                            <img src="img/shape.png" alt="shape">
                        </div>
                        <div class="total_img2">
                          <img src="img/shape.png" alt="shape">
                        </div> -->

                         <!-- <div class="third_section">
                          <div class="links">
                                 <div class="linksfooter">
                                    <p><a href="#">Reseller Name</a></p>
                                    <p><a href="#">Reseller Address</a></p>
                                    <p><a href="#">Reseller City</a></p>
                                    <p><a href="#">State Zip</a></p>
                                </div>
                                <img src="img/boxshape.png" alt="fist"> 
                                    </div>
                                <div class="footer_info">
                                    <div class="linksfooter">
                                        <p><a href="#">First Lastname</a></p>
                                        <p><a href="#">PO Box 555</a></p>
                                        <p><a href="#">Roadville, NY 87879</a></p>
                                    </div>
                                    <img src="img/boxshape.png" alt="fist">
                                </div>-->
                                    <!-- <div class="footer_logo">
                                        <a href="#"><img src="img/footerlogo.png" alt="footer"></a>
                                    </div> 
                                </div>
                            </div>
                        </div> -->
                        <div class="footer">
                            <div class="container">
                                <div class="center">
                                    <a href="#">Contact us: 1-800-555-1212</a>
                                    <a href="#">ResellerDomain.com</a>
                                </div>
                            </div>
                        </div>
                        <!-- <div class="footer">
                            <div class="container">
                                <center><a href="#">Contact us: 1-800-555-1212</a></center>
                                <center><a href="#">ResellerDomain.com</a></center>
                            </div>
                        </div> -->
                    </div>
                </div>
            </div>
</body>

</html>