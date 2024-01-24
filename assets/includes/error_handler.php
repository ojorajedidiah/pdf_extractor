<?php

#   Author of the script
#   Name: Adeleke Ojora
#   Email : adeleke.ojora@firs.gov.ng
#   Date created: 10th May 2022
#   Date modified: 28th June 2022 
#   Date updated: 30th May 2023
#		Modified and Updated by: Butu Ordooter A.


ini_set('display_errors',false);  // enforce error handling within application
register_shutdown_function( "fatal_handler" ); // trap fatal errors
set_error_handler("errorMessage"); // trap general errors
$_SESSION['activePage']=$_SERVER['REQUEST_URI'];

function fatal_handler() 
{
  // trap fatal error and send to error message handler
    $errfile = "unknown file";
    $errstr  = "shutdown";
    $errno   = E_CORE_ERROR;
    $errline = 0;

    $error = error_get_last();

    if($error !== NULL) {
        $errno   = $error["type"];
        $errfile = $error["file"];
        $errline = $error["line"];
        $errstr  = $error["message"];
        // $trace = print_r( debug_backtrace( false ), true );
        errorMessage( $errno, $errstr, $errfile, $errline);
    }
}

function errorMessage($errno, $errstr, $errfile, $errline)
{
// create a friendly error message, display error to user and save a record in the database
  $errMsg = '<div id="dialog" title="' . errorTitle($errfile) . '"><p>';
  $errMsg .= 'Ooops, there seem to be an error with your '.getPage($errfile)
    . '.<br><span style="text-align:center;color:red;font-weight:bold">'.getUserFriendlyMessage($errstr).'</span>';
  $errMsg .= '<br><div style="text-align:right;"><a class="ui-button danger" href="' . $_SESSION['activePage'] . '">  << Back   </a></div></p></div>';

  // log Error into DB
  if (saveLogData($errno,$errstr,$errline,$errfile)) {
    die($errMsg);
  }
}


function errorTitle($err)
{// create appropriate error title for the dialog window;
  $rtn='';  
  if (strpos(strtolower($err),'ldap') !== false || strpos(strtolower($err),'index') !== false){
    $rtn='Issue with the Login';
  } else {
    $rtn=(isset($_REQUEST['p']))? $_REQUEST['p']:'undefined page';
  }
  return $rtn;
}

function getUserFriendlyMessage($err)
{ // create friendly error message
  $rtn='';
  if (strpos(strtolower($err),'ldap') !== false) { // ldap related errors
    $rtn='Invalid credentials or Active Directory not reachable';
  } elseif (strpos($err,'open stream') !== false){ // missing file call
    $rtn='The requested page is not found';
  } else {
    $rtn=$err;
  }
  return $rtn;
}

function getPage($pg)
{ // define the page name to display in error message
  $rtn = '';
  $gpg = explode('\\', $pg);
  $gpglen = count($gpg) - 1;
  if (strpos(strtolower($pg),'ldap') !== false || strpos(strtolower($pg),'index') !== false) {
    $rtn = 'login attempt';
  } elseif ($gpg[$gpglen] == 'home') {
    $rtn = (isset($_REQUEST['p'])) ? $_REQUEST['p'] : 'undefined page';
  }
  return $rtn;
}

function getIPAddress()
{ // get IP address of the user of the application
  $ipadd = 'localhost';

  try {
    //die('testing the login 2');
    if (isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP'] != '' && $_SERVER['HTTP_CLIENT_IP'] != '127.0.0.1') {
      $ipadd = $_SERVER['HTTP_CLIENT_IP'];
    } else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] != '' && $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
      $ipadd = $_SERVER['REMOTE_ADDR'];
    } else if (isset($_SERVER['REMOTE_HOST']) && $_SERVER['REMOTE_HOST'] != '' && $_SERVER['REMOTE_HOST'] != '127.0.0.1') {
      $ipadd = $_SERVER['REMOTE_HOST'];
    }
  } catch (Exception $ex) {
    die('Error getIPAddress ' . $ex->getMessage());
  }

  /*return $_SERVER['REMOTE_ADDR'];  ///$_SERVER['REMOTE_ADDR']
    $ipaddress = '';
    if ($_SERVER['HTTP_CLIENT_IP'] != '127.0.0.1')
    $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if ($_SERVER['HTTP_X_FORWARDED_FOR'] != '127.0.0.1')
    $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if ($_SERVER['HTTP_X_FORWARDED'] != '127.0.0.1')
    $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if ($_SERVER['HTTP_FORWARDED_FOR'] != '127.0.0.1')
    $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if ($_SERVER['HTTP_FORWARDED'] != '127.0.0.1')
    $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1')
    $ipaddress = $_SERVER['REMOTE_ADDR'];
    else if ($_SERVER['REMOTE_HOST'] != '127.0.0.1')
    $ipaddress = $_SERVER['REMOTE_HOST'];*/

  return $ipadd;
}

function saveLogData($n,$s,$l,$f)
{ // create record of error in the DB
  $rtn=true;
  $ip = getIPAddress(); 
  $userN = getUserName();
  $qry = basename($_SERVER['REQUEST_URI']);
  $strW = (isset($_REQUEST))?json_encode($_REQUEST):'';
  $logD = 'URL: ' . $qry . ' [Error Number: ' . $n . '][Error String: '
    .$s.'][Error Line number:'.$l.']{Error FileName: '.$f.']';

  // exclude the querystring entry in the login errors
  if (!(strpos(strtolower($f),'ldap') !== false 
    || strpos(strtolower($f),'index') !== false)) { $logD .= '[QueryString: '.$strW.']';}

  $dat = new DateTime();
  $dt = $dat->format('Y-m-d');

  try {
    $db = new connectDatabase();
    if ($db->isLastQuerySuccessful()) {
      $con = $db->connect();

      $sql = "INSERT INTO fd_error_logs (errUserName,errIP,errDate,errDescription) VALUES (:erUN,:erIP,:erD,:erDS)";

      $stmt = $con->prepare($sql);
      $stmt->bindparam(":erUN", $userN, PDO::PARAM_STR);
      $stmt->bindparam(":erIP", $ip, PDO::PARAM_STR);
      $stmt->bindparam(":erD", $dt, PDO::PARAM_STR);
      $stmt->bindparam(":erDS", $logD, PDO::PARAM_STR);

      $row = $stmt->execute();
    } else {
      $rtn=false;
    }
    //$db->closeConnection();
  } catch (PDOException $e) {
    $rtn=false;
  }
  return $rtn;
}

function getUserName()
{ // get Username of application user
  $rtn='';
  //ucwords(strtolower($_SESSION['fullname']));
  if (isset($_COOKIE['fullName']) && strlen($_COOKIE['fullName']) > 1){
    $rtn = strtolower($_COOKIE['fullName']);
  } elseif (isset($_REQUEST['fullName']) && strlen($_REQUEST['fullName']) > 1){
    $rtn = strtolower($_REQUEST['fullName']);
  }  elseif (isset($_REQUEST['username']) && strlen($_REQUEST['username']) > 1){
    $rtn = strtolower($_REQUEST['username']);
  }
  return $rtn;
}
?>