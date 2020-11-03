<?php


$users_download = array (
	'myuser01' => '/opt/secure/myfile.pdf'
);

$max_session_time = 1800; // maximum view time 30 minutes
$session_timeout = 300; //time out after 5 mins of no activity

$db_enable = true;
$em_enable = true;

$email_account = "my-special-alert001@nighthour.sg";
$rurl = "https://nighthour.sg/resume/";



function getDatabasePDO()
{

  $host = '127.0.0.1';
  $db   = 'db1';
  $user = 'dbuser0001';
  $pass = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
  $charset = 'utf8';

  $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
  $opt = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
      PDO::MYSQL_ATTR_SSL_KEY    =>'/opt/certs/client-key.pem',
      PDO::MYSQL_ATTR_SSL_CERT=>'/opt/certs/client-cert.pem',
      PDO::MYSQL_ATTR_SSL_CA    =>'/opt/certs/ca-cert.pem',
      PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
  ];
  $pdo = new PDO($dsn, $user, $pass, $opt);
  return $pdo;

}


/* Update views into the database */
function updateDownloadDB($ip, $username, $filename)
{
  global $db_enable;

  if(!$db_enable)
  {
    return;
  }


  $pdo = getDatabasePDO();
  $stmt = $pdo->prepare('INSERT INTO resumedownload (ip,username,file) values(?,?,?)');
  $ret = $stmt->execute([$ip, $username, $filename]);
  $stmt = null;
  $pdo = null;
  return $ret;
}



/* Get the file location for a given user */
function getFile($user)
{
  global $users_download;
  $file = $users_download[$user];
  return $file;
}

/* Output resume content */
function downloadResume($user, $ip)
{
   global $rurl;	

  /* Get the resume filename */
  $filename = getFile($user);

  if($filename === null)
  {
  	   error_log("User does not have a file to download : " . $ip . " : ". $user, 0);
  	   emailalert("User does not have a file to download : " . $ip . " : ". $user);
  	   destroySession();
       header("Location: " . $rurl);
  	   exit(0);
  }

  $input_file = fopen($filename, "r");

  if(!$input_file)
  {
    error_log("Unable to open resume file! " . $filename,0);
    exit(1);
  }


  header('Content-Type: application/pdf');
  header('Content-Disposition: attachment; filename="myfile.pdf"');

  echo fread($input_file,filesize($filename));
  fclose($input_file);

  $log_message = "Resume downloaded : ". $ip . " : " . $user . " : " . $filename;

  error_log($log_message, 0);
  emailalert($log_message);
  updateDownloadDB($ip, $user, $filename);

}


/* Send an email alert */
function emailalert($message)
{
  global $em_enable, $email_account;

  if(!$em_enable)
  {
    return;
  }

  error_log($message, 1, $email_account);

}


/* Check session validity */
function checkSession($username, $remoteip)
{
  global $max_session_time, $session_timeout;

  if(!isset($_SESSION['LoginTime']) || !isset($_SESSION['LoginStatus']))
  {
    return false;
  }

  $currenttime = time();

  if($currenttime - $_SESSION['LoginTime'] > $max_session_time)
  {
    error_log("Maximum session time exceeded! : " . $remoteip . " : " . $username,0);
    return false;
  }

  if($currenttime - $_SESSION['LoginStatus'] < $session_timeout)
  { //Session still valid
      return true;           
  }

  error_log("Session time out : " . $remoteip . " : " . $username ,0);

  /* Session timeout */
  return false;
}


/* Destroy the existing session */
function destroySession()
{     

    error_log("destroySession(): Clearing active session ", 0);

    $sess_name = session_name(); 
    $sessioncookie = "Set-Cookie: " . $sess_name . "=deleted;" . " expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; secure; HttpOnly; SameSite=Strict" ;

    //Clear all session variables
    $_SESSION = array();

    //Destroy the session
    session_destroy();

    //clear any session cookie
    header_remove("Set-Cookie");
    header($sessioncookie);

}


/* Set security headers */
function secureHeaders()
{
    header('Cache-control: no-store');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Content-Security-Policy: default-src \'self\'; frame-ancestors \'self\'; base-uri \'self\'; form-action \'self\'; report-uri /csprp/; ');
    header('Referrer-Policy: same-origin');
}


$remoteip="";

if(isset($_SERVER['HTTP_X_REAL_IP']))
{
     $remoteip = " remote ip/real ip : " . $_SERVER['REMOTE_ADDR'] . " : " . $_SERVER['HTTP_X_REAL_IP'];
}
else
{
    $remoteip = $_SERVER['REMOTE_ADDR'];
}

/* Check that request method is set */
if(!isset($_SERVER['REQUEST_METHOD'])) 
{
    error_log("Request Method not set : " .  $remoteip ,0);
    exit(1);
}


/* Allow only GET */
if(strcasecmp("get", $_SERVER['REQUEST_METHOD']) != 0)
{
  error_log("Invalid HTTP method : " . $_SERVER['REQUEST_METHOD'] . " : " .  $remoteip ,0);
  exit(1);
}


secureHeaders();
session_start();

if( ! (isset($_SESSION['LoginStatus']) && isset($_SESSION['LoginTime']) && isset($_SESSION['LoginUser'])) )
{

	error_log("Login not set redirecting : " . $remoteip , 0);
	destroySession();
	header("Location: " . $rurl);
	exit(0);

}


$username = $_SESSION['LoginUser']; 


if(checkSession($username,$remoteip))
{

	downloadResume($username, $remoteip);

}
else
{

	error_log("Invalid Session : " . $remoteip . " : " . $username , 0);
    destroySession();
    header("Location: " . $rurl);
    exit(0);

}



?>
