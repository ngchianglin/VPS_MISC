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

Content Security report end point
to receive reports on CSP violations

Uses the phpmailer to send email notifications on CSP violations. 
Refer to https://github.com/PHPMailer/PHPMailer for more detailed information on 
how to use phpmailer

There is a throttle mechanism in this script to control the rate of reports.
This is to prevent spamming. 
Refer to https://www.nighthour.sg/articles/2017/php-rate-limiter-finite-state.html
for the throttling implementation

Ng Chiang Lin
March 2017

*/

require_once '<php scripts library location>/throttle.php';
require_once '<php scripts library location>/class.phpmailer.php';
require_once '<php scripts library location>/class.smtp.php';


/* Send email notification of the violation */
function sendEmail($from, $to, $subject, $message)
{
    $mail = new PHPMailer;
    $mail->isSMTP();                                     
    $mail->Host = 'localhost'; 
    $mail->Port = 25;
    $mail->SMTPAuth = false;
    $mail->SMTPOptions = array(
        'ssl' => array(
              'verify_peer' => false,
              'verify_peer_name' => false,
              'allow_self_signed' => true
          )
     );
    $mail->CharSet = 'UTF-8'; // Set charset to utf-8
    $mail->setFrom($from, 'CSP Violation Mailer');
    $mail->addAddress($to, 'CSP Violation Mailer');
    $mail->isHTML(false); //To prevent any potential malicious content, set it to text email. 
    $mail->Subject = $subject;
    $mail->Body   =  $message;
      
    if(!$mail->send()) 
    {          
        error_log("Error sending CSP email !\n" . "Mailer Error: " . $mail->ErrorInfo  . "\n", 0);
        return false;        
    } 
    return true;
}

define("MAXSIZE", 250000);


function allow($ip, $result)
{
    $raw = file_get_contents("php://input");
    $content = null;
    $content = json_decode($raw, true, 5);

    $message ="";
    $formatted="";
    
    if(!is_null($content) && strlen($raw) < MAXSIZE )
    { //Valid Json 
        $report=key($content);
        if( ! is_null($report)  and  strcasecmp("csp-report", $report) ===0 )
        {//Another check to make sure the first json name is "csp-report"
     
     
            $formatted = json_encode($content, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
            $message = $_SERVER['REMOTE_ADDR'] . "\n" . $_SERVER['HTTP_USER_AGENT'] . "\n\n" 
                        . $formatted . "\n";              
             
            sendEmail('no-reply@nighthour.sg','myreceivingemailaddress@nighthour.sg','CSP Violation Report', $message);
            http_response_code(202); //Send HTTP 202 accepted back
     
        }
        else
        {
            error_log("Invalid CSP Json format: " . $_SERVER['REMOTE_ADDR'] . " : " . $_SERVER['HTTP_USER_AGENT'] . "\n" , 0);
            exit(1); 
        }

    }
    else
    {
        error_log("Invalid CSP Json format: " . $_SERVER['REMOTE_ADDR'] . " : " . $_SERVER['HTTP_USER_AGENT'] . "\n" , 0);
        exit(1); 
    }
    
}  


function disallow($ip, $result)
{
    error_log("CSP report violation exceeded throttle rate ! : " . " count : " . $result['count']
    . " : " . $_SERVER['REMOTE_ADDR'] . " : " . $_SERVER['HTTP_USER_AGENT'] . "\n", 0); 
    http_response_code(202); //Send HTTP 202 accepted 
    exit(1);
}



if( isset($_SERVER['REQUEST_METHOD'])  &&  strcasecmp("post", $_SERVER['REQUEST_METHOD'] ) == 0   )
{//http post


     //Starts the finite state throttling, calls allow() if rate is within limit
    startThrottleStateMachine($_SERVER['REMOTE_ADDR']); 
    header('Cache-control: no-store');
    header('Content-Type: text/html; charset=UTF-8');

    
}
else
{
    //Any other methods directed to error page
    header("Location: https://www.nighthour.sg/error.html");
    exit(1);
}


?>

