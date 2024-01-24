<?php

function saveTrail($var)
{
  $rtn='';
  $ip = getIPAddress();
  $userN = ucwords(strtolower($_COOKIE['fullname']));
  $logDate=new DateTime();
  $dd=$logDate->format("Y-m-d");

  if ($var !== '') {
    try {
      $db = new connectDatabase(); // connect to DB and insert generate log
      if ($db->isLastQuerySuccessful()) {

        $con = $db->connect();

        $sql = "INSERT INTO fd_audit_logs (logUserName,logIP,logDescription,logDate) VALUES (:username,:ip,:operation,:Opdate)";
        // die($sql);
        $stmt = $con->prepare($sql);

        $stmt->bindparam(":username", $userN, PDO::PARAM_STR);
        $stmt->bindparam(":operation", $var, PDO::PARAM_STR);
        $stmt->bindparam(":ip", $ip, PDO::PARAM_STR);
        $stmt->bindparam(":Opdate", $dd, PDO::PARAM_STR);
        // $stmt->bindparam(":allocStatus", $a, PDO::PARAM_STR);
        $row = $stmt->execute();

        if ($row) {
          $rtn = 'success';
        }
      } else {
        $rtn = $db->connectionError();
      }
      $db->closeConnection();
    } catch (PDOException $e) {
      $rtn = $e->getMessage();
    }
  }
  return $rtn;
}

function saveLoginTrail($var)
{
  $rtn='';
  $usrn = $var['userName'];  // get username 
  // compose the log details
  $yyy = $var['logDescription'] . ' on ' . $var['logDate']->format("Y-m-d h:i:s") . ' from ' . $var['logIP'];
  $dd=$var['logDate']->format("Y-m-d");

  if ($yyy !== '') {
    try {
      $db = new connectDatabase(); // connect to DB and insert generate log
      if ($db->isLastQuerySuccessful()) {

        $con = $db->connect();

        $sql = "INSERT INTO fd_audit_logs (logUserName,logIP,logDescription,logDate) VALUES (:username,:ip,:operation,:Opdate)";
        // die($sql);
        $stmt = $con->prepare($sql);

        $stmt->bindparam(":username", $usrn, PDO::PARAM_STR);
        $stmt->bindparam(":operation", $yyy, PDO::PARAM_STR);
        $stmt->bindparam(":ip", $var['logIP'], PDO::PARAM_STR);
        $stmt->bindparam(":Opdate", $dd, PDO::PARAM_STR);
        // $stmt->bindparam(":allocStatus", $a, PDO::PARAM_STR);
        $row = $stmt->execute();

        if ($row) {
          // die($sql);
          $rtn = 'success';
        }
      } else {
        $rtn = $db->connectionError();
      }
      $db->closeConnection();
    } catch (PDOException $e) {
      $rtn = $e->getMessage();
    }
  }
  // die($rtn);
  return $rtn;
}


?>