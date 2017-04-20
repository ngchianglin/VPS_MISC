<?php

/*
* MIT License
*
* Copyright (c) 2017 Ng Chiang Lin
*
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
*
* The above copyright notice and this permission notice shall be included in all
* copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
* SOFTWARE.
*/


/*
A token bucket throttle using a backend UDP token bucket server that 
serves token. 

Refer to https://www.nighthour.sg/articles/2017/token-bucket-rate-limiter.html

Ng Chiang Lin
April 2017
*/


/* 
Function to connect to the UDP token bucket server
and check if the remote client ip address is within 
the rate limit set. 
Takes the remote client ip as parameter. 
Returns true if within rate, false otherwise. 
*/
function checkRate($clientip)
{

	$ret = $reply = $cs = false;	

	$cs = stream_socket_client("udp://127.0.0.1:3211", $errno, $errstr, 1);
	if (!$cs)
	{
	    error_log("Cannot open udp socket\n", 0); 
	    return false;
	}	

	$ret = fwrite($cs, $clientip);
	if(!$ret)
	{
	    error_log("Cannot write udp socket\n", 0);
	    fclose($cs); 	
	    return false;
	}

	$reply = fread($cs,10);
	if (!$reply )
	{
	    error_log("Cannot write udp socket\n", 0);
	    fclose($cs); 	
	    return false;
	}

	$reply = rtrim($reply);
	if(strcasecmp("OK", $reply ) == 0 )
	{	
	    fclose($cs); 
	    return true;
	}
	
	fclose($cs); 	
	return false; 
}


if( isset($_SERVER['REQUEST_METHOD'])  &&  strcasecmp("get", $_SERVER['REQUEST_METHOD'] ) == 0   )
{
	
	$ip = $_SERVER['REMOTE_ADDR']; //Connecting remote client ip address
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-control: no-store');
	
    if(isset($_GET['ip']) && !empty($_GET['ip']))
    {//Warning !
      //The is only for testing, to simulate different ip
      //It will not throttle real ip addresses, can be bypassed
      //and lead to vulnerabilities with the throttling script
      //There are also no checks for malicious input
      //In Production to throttle real ip addresses, 
      //remove this and use $_SERVER['REMOTE_ADDR'] 
       $ip= $_GET['ip'];
    }
	
	$ret = checkRate($ip);
	
	if($ret)
		echo "Allowed : " . $ip . "<br>\n";
	else
		echo "Disallowed : " .$ip .  "<br>\n";
	
}

?>
