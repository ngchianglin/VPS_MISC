<?php


/* Allow only GET and POST */
if(strcasecmp("post", $_SERVER['REQUEST_METHOD']) != 0  && strcasecmp("get", $_SERVER['REQUEST_METHOD']) != 0)
{
  error_log("Invalid HTTP method : " . $_SERVER['REQUEST_METHOD'] . " : " .  $remoteip ,0);
  die();
}


header('Content-Type: text/html; charset=UTF-8');
header('Cache-control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\'; connect-src \'self\'; report-uri /csprp/;');
header('Referrer-Policy: same-origin');


/* Handle POST */
if(strcasecmp("post", $_SERVER['REQUEST_METHOD']) == 0 )
{
	 if(!isset($_POST['plainpassword']))
     {
     	echo "Plain password not set !";
		error_log("Plain password not set !",0);
		die();
     }

     $plain_password = $_POST['plainpassword'];
     $hash_pass = password_hash($plain_password, PASSWORD_BCRYPT);

     if(!$hash_pass)
     {
     	echo "Error generating bcrypt hash!";
		error_log("Error generating bcrypt hash!",0);
		die();
     }

     echo $hash_pass;
     exit(0);
}


?>


<!DOCTYPE html>
<html> 
<head> 
	<meta charset="utf-8"> 
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Simple Php Password Hash Utility</title> 
</head>
 <body> 
 	<h2>Generates Php BCRYPT hashes</h2>
 	<p>Note there is no check on password length and complexity. Choose and use a complex and sufficiently long password yourself.
    Use only ASCII characters, numeric digits, various punctuation marks, numeric operators. The php password_hash method is not binary safe. 
 	</p>
 	<div>
 		<form action="bcrypthash.php" accept-charset="utf-8" method="post" enctype="application/x-www-form-urlencoded">
 			<label>Enter Password</label><br>
 			<input type="text" name="plainpassword"  placeholder="Plain text password" maxlength="50" size="30" required>
 			<br><br>
 			<input type="submit" value="Generate Bcrypt Hash" >
 		</form>
 	</div>
 </body> 
 </html> 