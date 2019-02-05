<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// USA ePay PHP Library.
//      v1.6
//
//      Copyright (c) 2002-2008 USA ePay
//      For support please contact devsupport@usaepay.com
//
//  The following is an example of running a transaction using the php library.
//  Please see the README file for more information on usage.
//

// Change this path to the location you have save usaepay.php to
include "./usaepay.php";

// Instantiate USAePay client object
$tran=new umTransaction;

// Merchants Source key must be generated within the console
$tran->key="6VZf9kf1DfMD1pE9At75u5DKGwsQsNsZ";
$tran->pin="2135";

// Send request to sandbox server not production.  Make sure to comment or remove this line before
//  putting your code into production
$tran->usesandbox=false;    



$tran->card="8pqq-61g2-y4n4-xaxe";		
$tran->exp="0522";			
//$tran->cvv2="5793";

$tran->amount="1.00";			
$tran->invoice="GORIN-TEST1";   		
//$tran->cardholder="EVAN GORIN"; 	
//$tran->street="597 Empire Bld Apt 4";	
//$tran->zip="11213";			
//$tran->isrecurring=true;			
//$tran->savecard=true;		
$tran->description="Test WITH TOKEN, isrecurring=true, savecard=true";	

// Billing info
/**

$tran->billzip="11213";
$tran->billfname="Evan";
$tran->billlname="Gorin";
$tran->billcompany="Gorin Systems";
$tran->billstreet="597 Empire Blvd 4";
$tran->billcity="Brooklyn";
$tran->billstate="NY";

$tran->billcountry="USA";
$tran->billphone="6318068612";
$tran->email="moshe@gorinsystems.com";
$tran->website="gorinsystems.com";
**/


echo "<h1>Please Wait One Moment While We process your card.<br>\n";
flush();

if($tran->Process())
{
	echo "<b>Card approved</b><br>";
	echo "<b>Authcode:</b> " . $tran->authcode . "<br>";
	echo "<b>AVS Result:</b> " . $tran->avs_result . "<br>";
	echo "<b>Cvv2 Result:</b> " . $tran->cvv2_result . "<br>";
	echo "<b>Card Token:</b> " . $tran->cardref . "<br>";

} else {
	echo "<b>Card Declined</b> (" . $tran->result . ")<br>";
	echo "<b>Reason:</b> " . $tran->error . "<br>";	
	if($tran->curlerror) echo "<b>Curl Error:</b> " . $tran->curlerror . "<br>";	
}		

?>