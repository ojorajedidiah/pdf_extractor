<?php
#   Author of the script
#   Name: Adeleke Ojora
#   Email : ojorajedidiah@gmail.com
#   Modified Date: 07-01-2022
#	  Modified by: Adeleke Ojora

session_start();
date_default_timezone_set("Africa/Lagos");
include("assets/includes/error_handler.php");

include('classes/databaseConnUserAccount.class.php');
include("classes/user.class.php");
include("classes/databaseConnection.class.php");
include("classes/ldapConnect.class.php");

include("assets/includes/auditTrail.php");
$ip = $_SERVER['REMOTE_ADDR'];

// var_dump($_REQUEST);
// var_dump($_SESSION);

$msg = '';

if (isset($_REQUEST['login']) && (isset($_REQUEST['vw']) && $_REQUEST['vw'] == 'psswd')) {
  //die('na here e dey');      
  if (!(empty($_SESSION['un'])) && !(empty($_REQUEST['password']))) {
    $username = $_SESSION['un'];
    $password=$_REQUEST['password'];

    try {
      $ldap = new ldapConnect($username, $password);
      $ldapStatus = $ldap->getResponses();
      // get response
      if ($ldapStatus['status_message'] == 'success') {
        // get userdetails from AD
        $ldapDetails = $ldap->userDetails();

        // your username in variable
        $dn = $username;
        $_SESSION['uspx'] = $password;

        $user = new FIRSUser('acc', $dn);

        if ($user && !empty($user->irNum)) {
          // echo json_encode($user).'<br><br>';
          // foreach ($rows as $row) {
          if ($user->userStatusID == 2) {
            $msg = 'Account deactivated.<br>Please contact ICT System Administrator <a href="mailto:ict-application@firs.gov.ng">Here!</a>';
            trigger_error($msg, E_USER_NOTICE);
          } else {
            $user->getUserOrg($user->getUserUnitID());
            // die(json_encode($user));  

            // Data to store in the cookie
            $expiration = time() + (10 * 31536000); // Expires in 10 minutes
            setcookie('appname', $user->appname, $expiration, "/");
            setcookie('fullname', $user->userFullname, $expiration, "/");
            setcookie('userName', $user->userName, $expiration, "/");
            setcookie('email', $user->userEmail, $expiration, "/");
            setcookie('active', true, $expiration, "/");
            setcookie('userid', $user->getUserID(), $expiration, "/");
            setcookie('userUnit', $user->userUnit, $expiration, "/");
            setcookie('userUnitID', $user->userUnitID, $expiration, "/");
            setcookie('userDept', $user->userDept, $expiration, "/");
            setcookie('userDeptID', $user->userDeptID, $expiration, "/");
            setcookie('userDivision', $user->userDivision, $expiration, "/");
            setcookie('userDivisionID', $user->userDivisionID, $expiration, "/");
            setcookie('userGroup', $user->userGroup, $expiration, "/");
            setcookie('userGroupID', $user->userGroupID, $expiration, "/");
            setcookie('userRole', $user->userRole, $expiration, "/");
            setcookie('userRoleID', $user->userRoleID, $expiration, "/");
            $_SESSION['loggedIn']=1;

            $userPages = array(
              'pages' => $user->getUserPrivileges($user->userRoleID) // get & 
            );
            // Serialize the array into JSON
            $jsonUserPages = json_encode($userPages);
            // Set a cookie with the serialized JSON data
            setcookie("rt_user_data", $jsonUserPages, $expiration, "/");

            // die(json_encode($_COOKIE));

            if ($user->userEmail == '') {
              $val = updateUserDetails($user->userName, $user->userEmail);
            }

            $action = $user->userFullname . ' Logged in Dashboard!';
            $data = [
              'logIP' => getIPAddress(),
              'logDate' => new DateTime(),
              'logDescription' => $action,
              'userName' => $user->userName
            ];
            // die(var_dump($data));
            $log = saveLoginTrail($data);
            if ($log == 'success') {
              // unset($_SESSION['userName']);
              die('<head><script language="javascript">window.location="view.php";</script></head>');
            } else {
              trigger_error($log, E_USER_NOTICE);
            }
          }
          // }
        } else {
          $msg = 'You do not have an account on this application.<br>Please contact ICT System Administrator <a href="mailto:ict-application@firs.gov.ng">Here!</a>';
          trigger_error($msg, E_USER_NOTICE);
        }
        // } else {
        //   $msg = $db->connectionError();
        //   trigger_error($msg, E_USER_NOTICE);
        // }
        // $db->closeConnection();
      } else {
        $msg = $ldapStatus['data'];
        trigger_error($msg, E_USER_NOTICE);
      }
      $ldap->closeLDAP();
    } catch (\Throwable $th) {
      $msg = $th->getMessage();
      trigger_error($msg, E_USER_NOTICE);
    }
  } else {
    $msg = "username and password is required";
  }
} else {
  $_SESSION['un'] = (isset($_REQUEST['username'])) ? $_REQUEST['username'] : '';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CBN Statement Uploads | Log in</title>

  <link rel="stylesheet" href="assets/css/all.min.css">
  <link rel="stylesheet" href="assets/css/adminlte.min.css">
</head>

<body class="hold-transition login-page"  style="background-color: azure;">
  <div class="login-box">
    <div class="login-logo">
      <a class="navbar-brand" href=""><img src="assets/img/logo.png" alt="Logo" width="165" height="75" /></a>
      <a href="">
        <h5><b>Staff | Log in</b></h5>
      </a>
    </div>
  <div class="card card-success card-outline">
    <div class="card-body login-card-body">
      <p class="login-box-msg">Sign in to start your session</p>
      <span class="badge badge-danger"><?php echo $msg; ?></span>

      <form action="" method="post">
        <?php if (!isset($_REQUEST['vw']) || strlen($msg) > 1) { ?>
          <div class="input-group mb-3">
            <input type="hidden" name="vw" id="vw" value="uname">
            <input type="username" name="username" class="form-control" required placeholder="Enter username">
            <!-- <div class="input-group-append">
              <div class="input-group-text">
                <span class="fa fa-user-check"></span>
              </div>
            </div> -->
          </div>
        <?php } else if (isset($_REQUEST['vw']) && $_REQUEST['vw'] == 'uname') { ?>
          <div class="input-group mb-3">
            <input type="hidden" name="vw" id="vw" value="psswd">
            <input type="password" name="password" class="form-control" required placeholder="Password">
            <!-- <div class="input-group-append">
              <div class="input-group-text">
                <span class="fa fa-lock"></span>
              </div>
            </div> -->
          </div>
        <?php } ?>
        <div class="row">
          <div class="col-8">
          </div>
          <?php if (!isset($_REQUEST['vw']) || strlen($msg) > 1) { ?>
            <div class="col-4">
              <button type="submit" name="nxt" class="btn btn-danger btn-block">Next</button>
            </div>
          <?php } elseif (isset($_REQUEST['vw']) && isset($_REQUEST['vw']) == 'uname') { ?>
            <div class="col-4">
              <button type="submit" name="login" class="btn btn-danger btn-block">Sign In</button>
            </div>
          <?php } elseif (strlen($msg) > 1) { ?>
            <div class="col-4">
              <button type="submit" name="tryAgain" class="btn btn-danger btn-block">Try Again</button>
            </div>
          <?php } ?>
        </div>
      </form>
      <!-- <div class="lockscreen-footer text-center"> -->
        <!-- <span style="font-size: 8pt;">Powered by <b><a href="https://github.com/ojorajedidiah" target="new">ojorajedidiah</a></b></span> -->
      <!-- </div> -->
    </div>
  </div>
</div>


<script src="assets/js/jquery-3.6.0.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/adminlte.min.js"></script>
</body>

</html>

<?php
function updateUserDetails($us, $em)
{
  $rtn = array();

  try {
    $db = new connectDatabaseUserAccount();
    if ($db->isLastQuerySuccessful()) {
      $con = $db->connect();

      $sql = "UPDATE users SET UserEmail =:email  WHERE UserName =:username ";
      //die($sql);
      $stmt = $con->prepare($sql);
      $stmt->setFetchMode(PDO::FETCH_ASSOC);
      $stmt->bindparam(":email", $em, PDO::PARAM_STR);
      $stmt->bindparam(":username", $us, PDO::PARAM_STR);
      $stmt->execute();
    } else {
      $rtn = $db->connectionError();
    }
    $db->closeConnection();
  } catch (PDOException $e) {
    $rtn = $e->getMessage();
  }
  return $rtn;
}


?>