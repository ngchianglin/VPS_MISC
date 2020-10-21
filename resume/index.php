<?php


class User
{

   public $userid;
   public $password_hash;
   public $file;

  function __construct($userid, $password_hash, $file) 
  {
      $this->userid = $userid;
      $this->password_hash = $password_hash;
      $this->file = $file; 
  }

}

$valid_users = array (

"hr01" => new User("hr01", '$2y$XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX', '/opt/secure/file1.html'), 
"hr02" => new User("hr02", '$2y$XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX', '/opt/secure/file2.html'),
"hr03" => new User("hr03", '$2y$XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX', '/opt/secure/file3.html'),
"myuser01" => new User("myuser01", '$2y$XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX', '/opt/secure/file4.html')

);




$db_enable = true; //set whether mariadb database is enabled
$em_enable = true; // set whether email alert is enabled
$captcha_enable = true; // set whether captcha is enabled

$email_account = "my-special-alert001@nighthour.sg";
$rurl = "https://nighthour.sg/resume/";
$captcha_timeout = 900;
$max_session_time = 1800; // maximum view time 30 minutes
$session_timeout = 300; //time out after 5 mins of no activity



function getDatabasePDO()
{

  $host = '127.0.0.1';
  $db   = 'db1';
  $user = 'dbuser0001';
  $pass = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
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
function updateDBView($ip, $username, $filename)
{
  global $db_enable;

  if(!$db_enable)
  {
    return;
  }


  $pdo = getDatabasePDO();
  $stmt = $pdo->prepare('INSERT INTO resumeview (ip,username,file) values(?,?,?)');
  $ret = $stmt->execute([$ip, $username, $filename]);
  $stmt = null;
  $pdo = null;
  return $ret;
}


/* Update fail login attempt into the database */
function updateDBFailLogin($ip, $username, $password)
{
  global $db_enable;

  if(!$db_enable)
  {
    return;
  }

  $pdo = getDatabasePDO();
  $stmt = $pdo->prepare('INSERT INTO failedlogin (ip,username,password) values(?,?,?)');
  $ret = $stmt->execute([$ip, $username,$password]);
  $stmt = null;
  $pdo = null;
  return $ret;

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




/* Validate user */
function validateUser($user, $pass, $valid_users)
{
   $valid = false;
   foreach($valid_users as $id => $user_obj)
   {
      if($id === $user && password_verify($pass , $user_obj->password_hash))
      {
        $valid=true;
        break;
      }
   }

   return $valid;
}


/* Get the file location for a given user */
function getFile($user)
{
  global $valid_users;
  $user_obj = $valid_users[$user];
  return $user_obj->file;
}



/* Output resume content */
function displayResume($user, $ip)
{

  /* Display the resume file */
  $filename = getFile($user);
  $input_file = fopen($filename, "r");

  if(!$input_file)
  {
    error_log("Unable to open resume file! " . $filename,0);
    exit(1);
  }

  echo fread($input_file,filesize($filename));
  fclose($input_file);

  $log_message = "Resume shown : ". $ip . " : " . $user . " : " . $filename;

  error_log($log_message, 0);
  emailalert($log_message);
  updateDBView($ip, $user, $filename);


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
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-control: no-store');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Content-Security-Policy: default-src \'self\'; frame-ancestors \'self\'; base-uri \'self\'; form-action \'self\'; report-uri /csprp/; ');
    header('Referrer-Policy: same-origin');
}


/* Check the submitted captcha */
function checkCaptcha()
{

  global $captcha_timeout, $captcha_enable;

  if(!$captcha_enable)
  {
    return true;
  }

  if( !isset($_SESSION['CAPCREATE']) || !isset($_SESSION['captcha']) || !isset($_POST['ccode']) )
  {//captcha not set
    return false;
  }

  $currenttime = time();

  if($currenttime - $_SESSION['CAPCREATE'] > $captcha_timeout)
  {
    return false;
  }


  $captcha_value = '**********';
  $captcha_value = $_SESSION['captcha'];
  $submitted_captcha = $_POST['ccode'];

 
  if(strcasecmp($captcha_value, $submitted_captcha) === 0)
  {
    return true;
  }

  return false;

}




$remoteip="";

if(isset($_SERVER['HTTP_X_REAL_IP']))
{
    $remoteip = $_SERVER['HTTP_X_REAL_IP'];
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


/* Allow only GET and POST */
if(strcasecmp("post", $_SERVER['REQUEST_METHOD']) != 0  && strcasecmp("get", $_SERVER['REQUEST_METHOD']) != 0)
{
  error_log("Invalid HTTP method : " . $_SERVER['REQUEST_METHOD'] . " : " .  $remoteip ,0);
  exit(1);
}


secureHeaders();
session_start();



$form_submitted = false;
$error_message = "";
$username = "";


/* Handle HTTP POST */

if(strcasecmp("post", $_SERVER['REQUEST_METHOD']) == 0 )
{

 
    if( !isset( $_SESSION['LoginStatus'] ) )
    { /* Handle login */

        $form_submitted = true;

        if(!isset($_POST['userid']) || !isset($_POST['password']))
        {//userid and password not set 
            error_log("HTTP POST submission : " . $remoteip . " : userid or password is not set",0);
            destroySession();
            header("Location: " . $rurl);
            exit(1);
        }


        $username = $_POST['userid'];
        $password = $_POST['password'];

        if(checkCaptcha())
        { 

            if(validateUser($username, $password,$valid_users))
            {

                session_regenerate_id();
                $_SESSION['LoginTime'] = time();
                $_SESSION['LoginStatus'] = time();
                $_SESSION['LoginUser'] = $username; 
                displayResume($username, $remoteip);
                exit(0);

            }
            else
            {
                $error_message = "Invalid Userid and Password !";
                $log_message = "Failed login attempt : " . $remoteip . " : " . $username . " : " . $password;
                error_log($log_message, 0);
                updateDBFailLogin($remoteip,  $username, $password);
                emailalert($log_message);

            }

        }
        else
        {
            $error_message = "Invalid Captcha !";
            error_log("Invalid Captcha : " . $remoteip . " : " . $username , 0);
        }

       

    }
    else
    { /* Already login and login session exists */

        if(isset($_SESSION['LoginUser']))
        {
            $username = $_SESSION['LoginUser'];
        }  

        if(!checkSession($username, $remoteip))
        {//Invalid session
            destroySession();
            error_log("Login Status session expire redirecting : " . $remoteip . " : " . $username , 0);
            header("Location: " . $rurl);
            exit(0);
        }


        if(!isset($_SERVER['REQUEST_URI']))
        {
          error_log("Request uri not set !", 0);
          destroySession();
          header("Location: " . $rurl);
          exit(1);
        }


        $url = $_SERVER['REQUEST_URI'];
        $query = parse_url($url,PHP_URL_QUERY);


        if(!$query)
        {//Invalid query
            error_log("Invalid url query string ! : " . $remoteip . " : " . $username , 0);
            destroySession();
            header("Location: " . $rurl);
            exit(1);
        }

        $query = urldecode($query);

        if($query === "q=refresh")
        {
           error_log("q=refresh received : " . $remoteip . " : " . $username, 0);
           http_response_code(200); //Send HTTP 200 ok
           echo "ok";
           exit(0);
        }
        else if($query === "q=update")
        {
          //update session activity time
          error_log("q=update received : ". $remoteip . " : " . $username, 0);
          $_SESSION['LoginStatus'] = time();
          http_response_code(202);  //Send HTTP 202 accepted
          echo "ok";
          exit(0);

        }
        else
        {
          error_log("Invalid url query string ! : " . $remoteip . " : " . $username, 0);
          destroySession();
          header("Location: " . $rurl);
          exit(1);
        }

    }

  
}



?>





<!DOCTYPE html>

<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" href="../css2/main.css">
<link rel="stylesheet" type="text/css" href="rsm.css">
<script type="text/javascript" src="rs.js" async></script>
<title>View Resume - 夜空思间登录</title>
</head>
<body>


<header class="mhead">
<h1 class="mhead">Night Hour</h1>
<span class="mhead">Reading under a cool night sky ... 宁静沉思的夜晚 ...</span>

<nav class="tnav">
  <ul class="tul" id="tul"> 
    <li class="icon" id="navicon"><a href="#">☰ Menu</a></li> 
    <li><a href="https://www.nighthour.sg/">Home</a></li>
     <li><a href="https://www.nighthour.sg/articles/">Articles</a></li> 
     <li><a href="/projects.html">Projects</a></li> 
     <li><a href="/humour.html">Humour</a></li> 
     <li><a href="/games/">Games</a></li> 
     <li><a href="/links.html">Links</a></li> 
     <li><a href="/apps/login.php">Login</a></li>
     <li><a href="/zh/index.html">中文</a></li>
     <li><a href="/resume/">Resume</a></li>
     <li><a href="/about.html">About</a></li> 
   </ul>
 </nav> 


</header>


<div class="content">

<section>

<article class="art">



<h2>Login to View Resume - 履历表登录</h2>

<div class="rs_center">
<p>
<form class="rms_form" action="<?php echo $rurl; ?>" accept-charset="utf-8" method="post" enctype="application/x-www-form-urlencoded" id="rs_form">
<div class="rms_form_row">
<label>Username</label><br><input type="text" name="userid"  id="rs_userid" placeholder="Username" maxlength="50" size="30" required>
</div>
<div class="rms_form_row">
<label>Password</label><br><input type="password" name="password" id="rs_password" autocomplete="off" placeholder="Password" maxlength="100" size="30" required>
</div>
<br>


<?php if($captcha_enable): ?>

<div class="rms_form_row">

<?php

$ranvalue = mt_rand(1000, 99999);
$catch_img_src = "/captcha.php" . "?" . $ranvalue;

?>

<div class="rms_img_d">
<img id="captchaimg" class="rs_cap_img" src="<?php echo $catch_img_src;?>">
<button id="creload" type="button">Reload Captcha</button> <br>
</div>

</div>
<br>

<div class="rms_form_row">
<strong>I am not a robot, enter the text above :</strong><br>
<input name="ccode" id="captcha_code" size="30" type="text"> <br>
</div>

<?php endif; ?>

<div class="rms_form_row">
<input type="submit" value="Log in" id="login">
</div>
</form>
</p>

<p class="error" id="msg">
<?php

if($form_submitted)
{
   echo $error_message . " <br>";
}

?>
</p>
</div>

<br>
<p>
If you are a recruiter and is interested to view my online resume, feel free to drop me an email at 
<a href="/contact.html" target="_blank">Contact Me</a>. A modern browser like firefox, chrome or edge is needed to view the page properly. 
</p>
<p>
如果您是一名网上招聘人员，想看看我的履历。您可以通过 <a href="/contact.html" target="_blank">Contact Me</a>，来请求登录帐号。请用新版本的网络浏览器像，firefox, chrome, 或者 edge 来查看这网页。
</p>


</article>


</section>

</div>

<footer class="pgbtm">
<p class="dclm">
Disclaimer: The content of this website is my personal opinion and is provided as is, without any warranties or fitness of any kind.
Use it at your own risk. The author will not be responsible for any omissions, mistakes, errors, any form of direct or indirect losses
arising from the use of this website, including any third party websites, third party content or applications, referred, embedded or used here.
</p>
<p class="dclm">
Privacy: The author respects user privacy. Information collected by this website will only be used to provide services to the users of this site. This website may utilize cookies, third party services such as advertisements, site analytics etc... Such third party services may track and collect user information which are subjected to the terms and policies of the third party providers. This website contains links to other external websites. These external websites have their own terms and policies. By continuing to browse this site, you agree to and accept the policies and terms specified by this site.
</p>
&copy; 2020 Ng Chiang Lin, 强林<p>To send feedback or to contact the author, <a href="https://www.nighthour.sg/contact.html">Contact/Feedback</a> </p>
<p>
<a href="https://creativecommons.org/licenses/by-sa/4.0/" rel="license noopener noreferrer" target="_blank"><img alt="Creative Commons License" src="/images1/88x31.png"></a><br>This work is licensed under a <a href="https://creativecommons.org/licenses/by-sa/4.0/" rel="license noopener noreferrer" target="_blank">Creative Commons Attribution-ShareAlike 4.0 International License</a>.
</p>
</footer>


</body>
</html>



