<?php
include_once('databaseConnUserAccount.class.php');
// include_once('recordManager.class.php');

#**** FIRS User Class v1.00
#***Author of the script
#***Name: Adeleke Ojora
#***Email : adeleke.ojora@firs.gov.ng
#***Date created: 01/06/2023
#***Version number: v1.0


#   The user class define a user of the application 
#   the user will have status (active or not active) will be 
#   have an expiration date for the access granted aside from the
#   the basic properties of a user object.

class FIRSUser extends connectDatabaseUserAccount
{
  //declaring FIRS user  variable
  protected $userID;
  protected $userPwd;

  public $appname = 'collections';
  public $irNum;
  public $userName;
  public $userEmail;
  public $userFullname;
  public $userUnit;
  public $userUnitID;
  public $userDept;
  public $userDeptID;
  public $userDivision;
  public $userDivisionID;
  public $userGroup;
  public $userGroupID;
  public $userRole;
  public $userRoleID;
  public $userExpDate;
  public $userStatus;
  public $userStatusID;
  public $searchUserMsg;
  public $rec = array();
  protected $recacc = array();

  private $userError;
  private $userClassState;
  private $conn;


  //initialise 
  function __construct($type, $userDetail)
  {
    // $this->umcon = $this->connect();
    parent::__construct();
    // $this->connect();
    if ($type == 'new') { // new user 
      $this->initialiseNewUser($userDetail);
    } elseif ($type == 'acc') { // new account
      $this->initialiseWithUserID($userDetail);
    } elseif ($type == 'rec') { // existing account
      $this->getSpecificUserByID($userDetail);
    } elseif ($type == 'ser') { // search account
      $this->searchUser($userDetail);
    }

    $this->recacc = $userDetail;

    $this->conn = new connectDatabase();
  } 

  /* NOTES -- TODO --
  - create multiple users using bulk upload
  */

  // method that initialises a user from the database using the userID
  private function initialiseWithUserID($userName)
  {
    return ($this->getSpecificUser($userName)) ? true : false;
  }


  // method that initialises a user from a form entry 
  // (this creates a new user into the database)
  private function initialiseNewUser($user)
  {
    $this->createNewUser($user);
    return ($this->userClassState) ? true : false;
  }

  //*****************************************************************/
  //*********************** CRUD methods ****************************/
  //*****************************************************************/

  // get all users in the database
  public function getAllUsers()
  {
    $this->userClassState = false;
    try {
      // build the sql statement
      $sql = "SELECT User_id,irNo,UserName,FullName,UserExpiryDate FROM users ";
      $stmt = $this->connect()->prepare($sql);
      $stmt->execute();

      // store found data in $rec
      while ($rw = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tmp = array_push($this->rec, $rw);
      }

      $this->userClassState = (count($tmp) > 0) ? true : false;
    } catch (PDOException $e) {
      $this->userClassState = false;
      $msg = $e->getMessage();
      trigger_error($msg, E_USER_NOTICE);
    }
    return $this->userClassState;
  }

  public function getAllUsersData($appname)
  {
    $this->userClassState = false;
    $tmp = '';
    try {
      if (empty($appname) || $appname == NULL) {
        $sql = "SELECT us.User_id, us.irNo, us.UserName, us.FullName, ap.application_name , ap.app_roles_id, ur.roleName, ap.AppUserStatus, uss.StatusName FROM user_mgt_db.application_privileges ap 
        LEFT JOIN user_mgt_db.users us ON us.user_id = ap.user_id
        LEFT JOIN user_mgt_db.rt_user_roles ur ON ur.role_id = ap.app_roles_id
        LEFT JOIN user_mgt_db.user_status uss ON uss.user_status_id = ap.AppUserStatus";
      } else {
        $sql = "SELECT us.User_id, us.irNo, us.UserName, us.FullName, ap.application_name , ap.app_roles_id, ur.roleName, ap.AppUserStatus, uss.StatusName FROM user_mgt_db.application_privileges ap 
        LEFT JOIN user_mgt_db.users us ON us.user_id = ap.user_id
        LEFT JOIN user_mgt_db.rt_user_roles ur ON ur.role_id = ap.app_roles_id
        LEFT JOIN user_mgt_db.user_status uss ON uss.user_status_id = ap.AppUserStatus
            where application_name = '$appname' ";
      }
      $stmt = $this->connect()->prepare($sql);
      $stmt->execute();

      // store found data in $rec
      while ($rw = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tmp = array_push($this->rec, $rw);
      }

      $this->userClassState = ($tmp > 0) ? true : false;
    } catch (PDOException $e) {
      $this->userClassState = false;
      $msg = $e->getMessage();
      trigger_error($msg, E_USER_NOTICE);
    }
    return $this->userClassState;
  }

  // method to get specific user details
  private function getSpecificUser($username)
  {
    $this->userClassState = true;
    // $_SESSION['appname'] = 'record-tracker';
    // $appnm = $_SESSION['appname'];
    try {
      // build the sql statement
      // $sql = "SELECT User_id,irNo,UserName,FullName,UserEmail,UserStatus,UserRole,UserExpiryDate FROM users WHERE UserName=:id";
      $sql = "SELECT us.User_id, us.irNo, us.UserName, us.FullName, us.UserEmail,us.UserExpiryDate, ap.application_name , ap.app_roles_id, ur.roleName, ap.AppUserStatus, uss.StatusName, uss.user_status_id, us.UserUnitID FROM user_mgt_db.application_privileges ap 
      LEFT JOIN user_mgt_db.users us ON us.user_id = ap.user_id
      LEFT JOIN user_mgt_db.rt_user_roles ur ON ur.role_id = ap.app_roles_id
      LEFT JOIN user_mgt_db.user_status uss ON uss.user_status_id = ap.AppUserStatus WHERE UserName=:id AND ap.application_name =:app";
      $stmt = $this->connect()->prepare($sql);
      $stmt->bindparam(":id", $username, PDO::PARAM_STR);
      $stmt->bindparam(":app", $this->appname, PDO::PARAM_STR);
      $stmt->execute();

      // store found data in $rows
      $rows = $stmt->fetchAll();
      // die(var_dump($rows));

      if (count($rows) > 0) {
        foreach ($rows as $row) {
          $this->userID = $row['User_id'];
          $this->irNum = $row['irNo'];
          $this->userName = $row['UserName'];
          $this->userFullname = $row['FullName'];
          $this->userEmail = $row['UserEmail'];
          $this->userUnitID= $row['UserUnitID'];
          // $this->userDept = $this->getOffice($row['User_id']);
          $this->userRole = $row['roleName'];
          $this->userRoleID = (int) $row['app_roles_id'];
          $this->userExpDate = $row['UserExpiryDate'];
          $this->userStatus = $row['StatusName'];
          $this->userStatusID = (int) $row['user_status_id'];
        }
      } else {
        $this->userError = 'unable to get the requested user details';
        $this->userClassState = false;
      }
    } catch (PDOException $e) {
      $msg = $e->getMessage();
      trigger_error($msg, E_USER_NOTICE);
    }
    return $this->userClassState;
  }

  // public function getSpecificUserByID($rid)
  // {
  //   $this->userClassState = true;
  //   try {
  //     // build the sql statement
  //     $sql = "SELECT User_id,irNo,UserName,FullName,UserEmail,UserStatus,UserRole,UserExpiryDate FROM users WHERE User_id=:id";
  //     $stmt = $this->connect()->prepare($sql);
  //     $stmt->bindparam(":id", $rid, PDO::PARAM_STR);
  //     $stmt->execute();

  //     // store found data in $rows
  //     $rows = $stmt->fetchAll();
  //     if (count($rows) > 0) {
  //       foreach ($rows as $row) {
  //         $this->userID = $row['User_id'];
  //         $this->irNum = $row['irNo'];
  //         $this->userName = $row['UserName'];
  //         $this->userFullname = $row['FullName'];
  //         $this->userEmail = $row['UserEmail'];
  //         $this->userDept = $this->getOffice($row['User_id']);
  //         $this->userRole = $row['UserRole'];
  //         $this->userExpDate = $row['UserExpiryDate'];
  //         $this->userStatus = $row['UserStatus'];
  //       }
  //     } else {
  //       $this->userError = 'getSpecificUserByID unable to get the requested user details';
  //       $this->userClassState = false;
  //     }
  //   } catch (PDOException $e) {
  //     $msg = $e->getMessage();
  //     trigger_error($msg, E_USER_NOTICE);
  //   }
  //   return $this->userClassState;
  // }

  public function getSpecificUserByID($rid)
  {
    $this->userClassState = true;
    try {
      // build the sql statement
      $sql = "SELECT us.User_id, us.irNo, us.UserName, us.FullName, ap.application_name , ap.app_roles_id, ur.roleName, ap.AppUserStatus, uss.StatusName, uss.user_status_id, us.UserUnitID FROM user_mgt_db.application_privileges ap 
      LEFT JOIN user_mgt_db.users us ON us.user_id = ap.user_id
      LEFT JOIN user_mgt_db.rt_user_roles ur ON ur.role_id = ap.app_roles_id
      LEFT JOIN user_mgt_db.user_status uss ON uss.user_status_id = ap.AppUserStatus WHERE us.User_id=:id";
      $stmt = $this->connect()->prepare($sql);
      $stmt->bindparam(":id", $rid, PDO::PARAM_STR);
      $stmt->execute();

      // store found data in $rows
      $rows = $stmt->fetchAll();
      if (count($rows) > 0) {
        foreach ($rows as $row) {
          $this->userID = $row['User_id'];
          $this->irNum = $row['irNo'];
          $this->userName = $row['UserName'];
          $this->userFullname = $row['FullName'];
          // $this->userEmail = $row['UserEmail'];
          // $this->userDept = $this->getOffice($row['User_id']);
          $this->userRole = $row['roleName'];
          $this->userRoleID = (int) $row['app_roles_id'];
          // $this->userExpDate = $row['UserExpiryDate'];
          $this->userStatus = $row['StatusName'];
          $this->userStatusID = (int) $row['user_status_id'];
          $this->userUnit = (int) $row['UserUnitID'];
        }
      } else {
        $this->userError = 'getSpecificUserByID unable to get the requested user details';
        $this->userClassState = false;
      }
    } catch (PDOException $e) {
      $msg = $e->getMessage();
      trigger_error($msg, E_USER_NOTICE);
    }
    return $this->userClassState;
  }

  public function displayUsers()
  {
    // die(var_dump($this));
    $html = '';
    try {
      if ($this->getAllUsersData($this->appname)) {
        $rows = $this->rec;
        // die(var_dump($rows));
        if (!empty($rows)) {
          foreach ($rows as $row) {
            //Assign the results to variables
            $uid = $row["User_id"];
            $uN = $row["UserName"];
            $uFN = $row['FullName'];
            $uR = $row['roleName'];
            $uStatus = $row['StatusName'];

            $html .= "<tr>";
            $html .= "<td>" . $this->userUpdateLink($uid) . "</td>";
            $html .= "<td>{$uN}</td>";
            $html .= "<td>{$uFN}</td>";
            $html .= "<td>{$uR}</td>";
            $html .= ($uStatus === "disabled") ? "<td style='text-align:center;color:red;font-weight:bold;'> " . $uStatus . "</td> " : "<td style='text-align:center;color:green;font-weight:bold;'> " . $uStatus . "</td>";
            $html .= "</tr>";

            // die(var_dump($html));
          }
        } else {
          $rtn = '<tr><td colspan="6" style="text-align:center;font-size:14pt;color:red;font-weight:bold;">No records to display</td></tr>';
        }
      }
    } catch (Exception $e) {
      $msg = $e->getMessage();
      trigger_error($msg, E_USER_NOTICE);
    }
    return $html;
  }

  public function searchUser($search_user)
  {
    $this->userClassState = true;
    try {
      $un = $_SESSION['userName'];
      $pd = $_SESSION['uspx'];
      $searchResult = array();
    // die(var_dump($un,$pd));

      $sql = "SELECT UserName FROM users WHERE UserName=:uNm ";
      $stmt = $this->connect()->prepare($sql);
      $stmt->bindparam(":uNm", $search_user, PDO::PARAM_STR);
      $stmt->execute();
      $user = $stmt->fetchAll(PDO::FETCH_ASSOC);
      if ($user == TRUE) { // if same records exist 
        saveTrail($_COOKIE['fullname'] . ' searched for already existing user: ' . $this->userName );
        $msg2 = "<div class='alert alert-warning alert-dismissable'>
            <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button> 
            <h5> 
              <i class='icon fas fa-exclamation'> </i>
              Alert
            </h5>
            Username already exists, Please try again!
          </div>";
        $this->searchUserMsg = $msg2;
      } else {
        $ldp = new ldapConnect($un, $pd);
        if ($ldp->isConnected()) {
          $searchResult = $ldp->searchLDAP($search_user);
          if (in_array('Successfully', $ldp->getResponses())) {
            $arr = $ldp->getSearchResult();
            $this->userFullname = $arr[0]["fullname"];
            $this->userEmail = $arr[0]["email"];
            $this->userName = $arr[0]["username"];
            // $this-> = $arr[0]["designation"];
            $this->userDept = $arr[0]["department"];
            // $this-> = $arr[0]["telephonenumber"];
            $this->irNum = $arr[0]["irnum"];

            saveTrail($_COOKIE['fullname'] . ' searched for user: '. $this->userName);
            $msg3 = ' <div class="alert alert-info alert-dismissable">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> 
                <h5> 
                  <i class="icon fas fa-check"> </i>
                  User Details Found !
                </h5>
                Please provide a  " <b><em> Role </em></b> "  and pick the  " <b><em> Unit </em></b>"  of user.
              </div> ';
            $this->searchUserMsg = $msg3;
          } else {
            $arr = $ldp->getSearchResult();
            saveTrail($_COOKIE['fullname'] . ' searched for non existing user: ' . $this->userName );
            $msg = "<div class='alert alert-danger alert-dismissable'>
                <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button> 
                <h5> 
                  <i class='icon fas fa-times'> </i>
                  Alert!
                </h5>" . $arr['data'] . "         
              </div>";
            $this->searchUserMsg = $msg;
          }
        } else {
          $msg = json_encode($ldp->getResponses());
          trigger_error($msg, E_USER_NOTICE);
        }
      }
      // $this->closeConnection();
    } catch (PDOException $e) {
      $this->userClassState = false;
      $msg = $e->getMessage();
      trigger_error($msg, E_USER_NOTICE);
    }
  }

  public function statusIfExists(...$msg)
  {
    if (strpos($msg, 'warnings') !== false) {
      return $msg;
    }
    // return $msg;
  }

  // create a new user
  private function createNewUser($usr)
  {
    $this->userClassState = true;
    $expr = $this->setUserExpiryDate();
    $usr['UserExpiryDate'] = $expr;
    $this->getUserOrg($usr['UserUnitID']);
    $usr['userDeptID'] = $this->userDeptID;

    // die(var_dump($usr));

    if ($r = $this->isValidData($usr, 'new')) { // if the data is valid
      // die(var_dump("here " . $r));
      $app = 'record-tracker';

      $this->userClassState = true;
      try {
        // die(var_dump($this));

        // build the sql statement to create a new record in users scheme
        $sql = "INSERT INTO users (irNo,UserName,FullName,UserEmail,UserUnitID,UserExpiryDate) 
          VALUES (:irN,:uNm,:uFN,:uEm,:uUT,:uED)";
        $stmt = $this->connect()->prepare($sql);

        $stmt->bindparam(":irN", $this->irNum, PDO::PARAM_STR);
        $stmt->bindparam(":uNm", $this->userName, PDO::PARAM_STR);
        $stmt->bindparam(":uFN", $this->userFullname, PDO::PARAM_STR);
        $stmt->bindparam(":uEm", $this->userEmail, PDO::PARAM_STR);
        $stmt->bindparam(":uUT", $this->userUnitID, PDO::PARAM_INT);
        $stmt->bindparam(":uED", $this->userExpDate, PDO::PARAM_STR);
        $stmt->execute();
        // die(var_dump($stmt));
        $myID = $this->connect()->lastInsertID();

        // build sql to create new users application privileges
        $sql2 = "INSERT INTO application_privileges (`user_id`, application_name, app_roles_id, AppUserStatus ) VALUES (:uID, :an, :apID, :aus )";
        $stmt = $this->connect()->prepare($sql2);
        $stmt->bindparam(":uID", $myID, PDO::PARAM_INT);
        $stmt->bindparam(":an", $app, PDO::PARAM_STR);
        $stmt->bindparam(":apID", $this->userRoleID, PDO::PARAM_INT);
        $stmt->bindparam(":aus", $this->userStatusID, PDO::PARAM_INT);
        $stmt->execute();

        // prepare the postingDate
        $d2 = new DateTime();
        $d3 = $d2->format('Y-m-d');

        // build sql to create new posting in user_posting scheme
        $sql = "INSERT INTO user_posting (userID,userCurrentDept,userCurrentUnit,userPostingDate) 
          VALUES (:usID,:ucDpt,:ucUnit,:ucPD)";
        $stmt = $this->connect()->prepare($sql);

        $stmt->bindparam(":usID", $myID, PDO::PARAM_INT);
        $stmt->bindparam(":ucDpt", $this->userDeptID, PDO::PARAM_INT);
        $stmt->bindparam(":ucUnit", $this->userUnitID, PDO::PARAM_INT);
        $stmt->bindparam(":ucPD", $d3, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt == TRUE) {
          saveTrail($_COOKIE['fullname'] . ' created the new user: '. $this->userName);
          $msg = "<div class='alert alert-success alert-dismissable'>
            <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button> 
            <h5> 
              <i class='icon fas fa-check'> </i>
              Success!
            </h5>
            New Account with: <b><em> " . $this->userName . " </b></em> has been created
          </div>";
        } else {
          saveTrail($_COOKIE['fullname'] . ' failed to create new user: '. $this->userName);
          $msg = "<div class='alert alert-danger alert-dismissable'>
            <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>×</button> 
            <h5> 
              <i class='icon fas fa-times'> </i>
              Alert!
            </h5>
            Account creation failed!
          </div>";
        }
        $this->searchUserMsg = $msg;
        $this->closeConnection();
      } catch (PDOException $e) {
        $this->userClassState = false;
        $msg = $e->getMessage();
        trigger_error($msg, E_USER_NOTICE);
      }
    } else {
      $this->userClassState = false;
    }
  }

  // update the statuss of an existing user
  public function updateUserStatus($rid, $nuStatus)
  {
    $arr = array('enabled', 'disabled');

    if (is_string($nuStatus) && in_array($nuStatus, $arr)) { // if the data is valid
      try {

        // build the sql statement
        $sql = "UPDATE users SET UserStatus=:uST WHERE User_ir=:id";
        $stmt = $this->connect()->prepare($sql);

        $stmt->bindparam(":id", $rid, PDO::PARAM_INT);
        $stmt->bindparam(":uST", $nuStatus, PDO::PARAM_STR);

        $stmt->execute();
        $this->userClassState = true;
      } catch (PDOException $e) {
        $this->userClassState = false;
        $msg = $e->getMessage();
        trigger_error($msg, E_USER_NOTICE);
      }
    } else {
      $this->userError = "Invalid user Status";
      $this->userClassState = false;
    }
    return $this->userClassState;
  }

  // update expiration date for an existing user
  public function updateExpiration($rid, $Expdate)
  {
    if (!preg_match("/^[0-9]{1,2}\\/[0-9]{1,2}\\/[0-9]{4}$/", $Expdate)) { // if the date is valid
      try {
        // build the sql statement
        $sql = "UPDATE users SET UserExpiryDate=:uED WHERE User_ir=:id";
        $stmt = $this->connect()->prepare($sql);

        $stmt->bindparam(":id", $rid, PDO::PARAM_INT);
        $stmt->bindparam(":uED", $Expdate, PDO::PARAM_STR);

        $stmt->execute();
        saveTrail($_COOKIE['fullname'] . ' updated the User Expiry Date of user id: '. $rid);
        $this->userClassState = true;
      } catch (PDOException $e) {
        $this->userClassState = false;
        $msg = $e->getMessage();
        trigger_error($msg, E_USER_NOTICE);
      }
    } else {
      $this->userError = "Invalid date format [dd/mm/yyyy]";
      $this->userClassState = false;
    }
    return $this->userClassState;
  }

  // update user role 
  public function updateUserRole($role)
  {
    $rid = $this->userID;
    if ($this->isValidRole($role)) { // if the data is valid
      try {

        // build the sql statement
        $sql = "UPDATE users SET UserRole=:uRo WHERE User_id=:id";
        $stmt = $this->connect()->prepare($sql);

        $stmt->bindparam(":id", $rid, PDO::PARAM_INT);
        $stmt->bindparam(":uRo", $role, PDO::PARAM_STR);

        $stmt->execute();
        $this->userClassState = true;
      } catch (PDOException $e) {
        $this->userClassState = false;
        $msg = $e->getMessage();
        trigger_error($msg, E_USER_NOTICE);
      }
    } else {
      $this->userClassState = false;
    }
    return $this->userClassState;
  }

  // post/transfer a user from one office
  public function postUser($unitID)
  {
    $rid = $this->userID;
    if ($this->isValidPosting($unitID)) { // if the data is valid
      try {

        // build the sql to update the users scheme
        $sql = "UPDATE users SET UserUnitID=:uUn WHERE User_id=:id";
        $stmt = $this->connect()->prepare($sql);

        $stmt->bindparam(":id", $rid, PDO::PARAM_INT);
        $stmt->bindparam(":uUn", $unitID, PDO::PARAM_STR);

        $stmt->execute();

        // build the sql to update the user_posting scheme

        // update the last record of posting by changing the status
        $sql = "UPDATE user_posting SET userPostingStatus='previous' WHERE UserID=:id";
        $stmt = $this->connect()->prepare($sql);

        $stmt->bindparam(":id", $rid, PDO::PARAM_INT);
        $stmt->execute();

        // create a new posting first get last posting details then create a new record
        $sql = "SELECT up_Id,userCurrentDept,userCurrentUnit FROM user_posting WHERE userPostingStatus='previous' 
          AND UserID=:id ORDER BY userPostingDate DESC LIMIT 1";
        $stmt = $this->connect()->prepare($sql);

        $stmt->bindparam(":id", $rid, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        if (count($rows) > 0) {
          foreach ($rows as $row) {
            $ucD = $row['userCurrentDept'];
            $ucC = $row['userCurrentUnit'];
          }
        }

        $tmp = new DateTime();
        $tDat = $tmp->format('Y-m-d');
        $nuDpt = $this->getDeptID($unitID);

        $sql = "INSERT INTO user_posting (userID,userPreviousDept,userPreviousUnit,
          userCurrentDept,userCurrentUnit,userPostingDate) VALUES (:id,:pDpt,:pUnt,:cDpt,:cUnt,:tDat)";

        $stmt = $this->connect()->prepare($sql);

        $stmt->bindparam(":id", $rid, PDO::PARAM_INT);
        $stmt->bindparam(":pDpt", $ucD, PDO::PARAM_INT);
        $stmt->bindparam(":pUnt", $ucC, PDO::PARAM_INT);
        $stmt->bindparam(":cDpt", $nuDpt, PDO::PARAM_INT);
        $stmt->bindparam(":cUnt", $unitID, PDO::PARAM_INT);
        $stmt->bindparam(":tDat", $tDat, PDO::PARAM_STR);
        $stmt->execute();

        $this->userClassState = true;
      } catch (PDOException $e) {
        $this->userClassState = false;
        $msg = $e->getMessage();
        trigger_error($msg, E_USER_NOTICE);
      }
    } else {
      $this->userClassState = false;
    }
    return $this->userClassState;
  }

  public function updateUserDetails($data)
  {
    $this->userClassState = true;
    try {
      // if ($data == true) {
      // $con = $db->connect();
      $sql = "UPDATE application_privileges SET app_roles_id=:uRole, AppUserStatus=:uStatus WHERE user_id=:uid AND application_name='record-tracker'";
      $stmt = $this->connect()->prepare($sql);
      $stmt->bindparam(":uRole", $data['uRole'], PDO::PARAM_INT);
      $stmt->bindparam(":uStatus", $data['uStatus'], PDO::PARAM_INT);
      $stmt->bindparam(":uid", $data['uid'], PDO::PARAM_INT);
      $stmt->execute();

      $sql2 = "UPDATE users SET UserUnitID=:unitID WHERE `User_id`=:uid ";
      $stmt = $this->connect()->prepare($sql2);
      $stmt->bindparam(":unitID", $data['uUnit'], PDO::PARAM_INT);
      $stmt->bindparam(":uid", $data['uid'], PDO::PARAM_INT);
      $stmt->execute();

      if ($stmt == TRUE) {
        // if succeefull 
        saveTrail($_COOKIE['fullname'] . ' updated : ' . $this->userFullname .' user  ');
        $msg = '<div class="alert alert-success alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> 
                    <h5> 
                      <i class="icon fas fa-check"> </i>
                      Success!
                    </h5> 
                     Role/Status/Unit updated succesfully for : <italic>' . $this->userFullname . '</italic>
                   
                  </div>';
      } else {
        // not succeessful
        saveTrail($_COOKIE['fullname'] . ' failed to update : '. $this->userFullname .' user' );
        $msg = '<div class="alert alert-danger alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button> 
                    <h5> 
                      <i class="icon fas fa-times"> </i>
                      Alert!
                    </h5> 
                    Failed to update  : ' . $this->userFullname . '
                   
                  </div>';
      }
      // } else {
      //   $this->userClassState = false;
      // }
    } catch (Exception $e) {
      $this->userClassState = false;
      $msg = $e->getMessage();
      trigger_error($msg, E_USER_NOTICE);
    }
    return $msg;
  }



  //*****************************************************************/
  //*********************** generic methods *************************/
  //*****************************************************************/

  // public function showListOfRoles($selRole)
  // {
  //   $ra = $this->recacc;
  //   $ra->listAllRoles();
  //   return $ra;

  // }

  public function getUserOrg($rid) // get unit ID from users
  {
    $this->userClassState = true;
    try {
      // build the sql statement
      $sql = "SELECT un.unit_id ,un.unitName, dv.div_id, dv.div_name, dp.dept_id, dp.dept_name, gr.group_id, gr.group_name from user_mgt_db.units un
      left join user_mgt_db.divisions dv on dv.div_id = un.div_id
      left join user_mgt_db.departments dp on dp.Dept_id = un.dept_id 
      left join user_mgt_db.groups gr on gr.group_id = un.group_id
      where un.unit_id =:id";
      $stmt = $this->connect()->prepare($sql);
      $stmt->bindparam(":id", $rid, PDO::PARAM_STR);
      $stmt->execute();

      // store found data in $rows
      $rows = $stmt->fetchAll();
      // die(var_dump($rows));

      if (count($rows) > 0) {
        foreach ($rows as $row) {
          $this->userUnit = $row['unitName'];
          $this->userUnitID = (int) $row['unit_id'];
          $this->userDivision = $row['div_name'];
          $this->userDivisionID = (int) $row['div_id'];
          $this->userDept = $row['dept_name'];
          $this->userDeptID = (int) $row['dept_id'];
          $this->userGroup = $row['group_name'];
          $this->userGroupID = (int) $row['group_id'];
          // $this->userEmail = $row['UserEmail'];
          // $this->userDept = $this->getOffice($row['User_id']);
          // $this->userRole = $row['UserRole'];
          // $this->userExpDate = $row['UserExpiryDate'];
          // $this->userStatus = $row['UserStatus'];
        }
      } else {
        $this->userError = 'unable to get the requested user details';
        $this->userClassState = false;
      }
    } catch (PDOException $e) {
      $msg = $e->getMessage();
      trigger_error($msg, E_USER_NOTICE);
    }
    return $this->userClassState;
  }


  //  call rt_user_roles and 
  public function showListOfRoles($selRole)
  {
    $rtn = '';
    if (!empty($selRole) && $this->isValidRole($selRole)) {
      try {
        $sql = "SELECT role_id, roleName  FROM rt_user_roles";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
          $rows = $stmt->fetchAll();
          // die(var_dump($rows));
          foreach ($rows as $row) {
            $rID = $row['role_id'];
            $rName = $row['roleName'];
            if ($rID == $selRole) {
              // die(var_dump($rID));
              $rtn .= '<option selected="selected" value="' . $rID . '">' . ucfirst($rName) . '</option>';
            } else {
              $rtn .= '<option value="' . $rID . '">' . ucfirst($rName) . '</option>';
            }
          }
        } else {
          $rtn = '<tr><td colspan="9" style="text-align:center;font-size:14pt;color:red;font-weight:bold;">No data record to display</td></tr>';
        }
      } catch (PDOException $e) {
        $this->userClassState = $rtn = false;
        $msg = $e->getMessage();
        trigger_error($msg, E_USER_NOTICE);
      }
    } elseif (is_null($selRole) || empty($selRole)) {
      try {
        $sq = "SELECT role_id, roleName FROM rt_user_roles";
        $stmt = $this->connect()->prepare($sq);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
          $rows = $stmt->fetchAll();
          foreach ($rows as $row) {
            $rID = $row['role_id'];
            $rName = $row['roleName'];
            // $rtn .= '<option selected="selected" value="0"> ** SLECT **</option>';
            $rtn .= '<option  value="' . $rID . '">' . ucfirst($rName) . '</option>';
          }
        } else {
          $rtn = '<tr><td colspan="9" style="text-align:center;font-size:14pt;color:red;font-weight:bold;">No data record to display</td></tr>';
        }
      } catch (PDOException $e) {
        $this->userClassState = $rtn = false;
        $msg = $e->getMessage();
        trigger_error($msg, E_USER_NOTICE);
      }
    } else {
      $this->userClassState = false;
    }
    $this->userClassState = $rtn;
    return $rtn;
  }

  public function getAllUnits()
  {
    $this->userClassState = false;
    $tmp = '';
    try {
      $sql = "SELECT unit_id, unitName, unitAbbr FROM units";
      $stmt = $this->connect()->prepare($sql);
      $stmt->execute();

      // store found data in $rec
      while ($rw = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tmp = array_push($this->rec, $rw);
      }

      $this->userClassState = ($tmp > 0) ? true : false;
    } catch (PDOException $e) {
      $this->userClassState = false;
      $msg = $e->getMessage();
      trigger_error($msg, E_USER_NOTICE);
    }
    return $this->userClassState;
  }


  //  call rt_user_roles and 
  public function showListOfUnits($selRole)
  {
    $rtn = '';
    if (!empty($selRole)) {
      try {
        if ($this->getAllUnits()) {
          $rows = $this->rec;
          if (!empty($rows)) {
            // $rows = $stmt->fetchAll();
            foreach ($rows as $row) {
              $uID = $row['unit_id'];
              $uName = $row['unitName'];
              if ($uID == $selRole) {
                $rtn .= '<option selected="selected" value="' . $uID . '">' . ucfirst($uName) . '</option>';
              } else {
                $rtn .= '<option value="' . $uID . '">' . ucfirst($uName) . '</option>';
              }
            }
          } else {
            $rtn = '<tr><td colspan="9" style="text-align:center;font-size:14pt;color:red;font-weight:bold;">No data record to display</td></tr>';
          }
        }
      } catch (PDOException $e) {
        $this->userClassState = $rtn = false;
        $msg = $e->getMessage();
        trigger_error($msg, E_USER_NOTICE);
      }
    } elseif (is_null($selRole) || empty($selRole)) {
      try {
        if ($this->getAllUnits()) {
          $rows = $this->rec;
          if (!empty($rows)) {
            // $rows = $stmt->fetchAll();
            foreach ($rows as $row) {
              $uID = $row['unit_id'];
              $uName = $row['unitName'];
              // $rtn .= '<option selected="selected" value="0"> ** SLECT **</option>';
              $rtn .= '<option  value="' . $uID . '">' . ucfirst($uName) . '</option>';
            }
          } else {
            $rtn = '<tr><td colspan="9" style="text-align:center;font-size:14pt;color:red;font-weight:bold;">No data record to display</td></tr>';
          }
        }
      } catch (PDOException $e) {
        $this->userClassState = $rtn = false;
        $msg = $e->getMessage();
        trigger_error($msg, E_USER_NOTICE);
      }
    } else {
      $this->userClassState = false;
    }
    $this->userClassState = $rtn;
    return $rtn;
  }

  // call user_status' table and store in an array
  public function getAllUserStatus()
  {
    $rtn = '';
    try {
      $this->userClassState = true;

      // die(var_dump($con));
      $sql = "SELECT * FROM user_status";
      $stmt = $this->connect()->prepare($sql);
      $stmt->setFetchMode(PDO::FETCH_ASSOC);
      $stmt->execute();
      $rtn = $stmt->fetchAll();
      return $rtn;
    } catch (PDOException $e) {
      $rtn = $e->getMessage();
    }
  }

  // put getAllUserStatus() in a drop down list
  public function getListOfUserStatus($selRole)
  {
    $rtn = '';
    try {
      $rows = $this->getAllUserStatus();
      if (count($rows) > 0) {
        // die(var_dump($rows));
        foreach ($rows as $row) {
          $rID = $row['user_status_id'];
          $rName = $row['StatusName'];
          if ($rID == $selRole) {
            // die(var_dump($rID));
            $rtn .= '<option selected="selected" value="' . $rID . '">' . ucfirst($rName) . '</option>';
          } else {
            $rtn .= '<option value="' . $rID . '">' . ucfirst($rName) . '</option>';
          }
        }
      } else {
        $rtn = '<tr><td colspan="9" style="text-align:center;font-size:14pt;color:red;font-weight:bold;">No data record to display</td></tr>';
      }
    } catch (PDOException $e) {
      $this->userClassState = $rtn = false;
      $msg = $e->getMessage();
      trigger_error($msg, E_USER_NOTICE);
    }
    $this->userClassState = $rtn;
    return $rtn;
  }

  public function userUpdateLink($userid)
  {
    $aref = '';
    $userData = [];
    if (in_array('update-users', $this->recacc)) {
      $aref = '<a href="home?p=update-user&u=' .  $userid . '"> update </a> ';
    }

    return $aref;
  }

  private function getOffice($id)
  {
    $rtn = '';
    try {

      $this->userClassState = true;

      // build the sql statement
      $sql = "SELECT DepartmentName, unitName
      FROM user_posting up 
      INNER JOIN departments ON up.userCurrentDept = Dept_id 
      INNER JOIN units ON up.userCurrentUnit = unit_id
      WHERE userPostingStatus='active' AND unitStatus='active' AND DeptStatus = 'active' AND userID=:id";

      $stmt = $this->connect()->prepare($sql);
      $stmt->bindparam(":id", $id, PDO::PARAM_INT);
      $stmt->execute();

      // store found data in $rows
      $rows = $stmt->fetchAll();
      if (count($rows) > 0) {
        foreach ($rows as $row) {
          $this->userUnit = $row['unitName'];
          $rtn = $row['DepartmentName'];
        }
      } else {
        $this->userError = 'Unable to get Department/Unit details for this user';
        $this->userClassState = false;
      }
    } catch (PDOException $e) {
      $this->userClassState = false;
      $msg = $e->getMessage();
      trigger_error($msg, E_USER_NOTICE);
    }

    return $rtn;
  }

  private function setUserExpiryDate()
  {
    // Create a DateTime object for the current date
    // $currentDate = new DateTime($usr['UserExpiryDate']);
    $currentDate = new DateTime();

    // Add one year to the current date
    $currentDate->modify('+1 year');

    // Format the result as a string (e.g., 'Y-m-d' for year-month-day)
    $result = $currentDate->format('Y-m-d');

    return $result;
  }

  private function isValidData($nuUser, $valTy)
  {
    $rtn = true;
    // the assumption is that all fields are required on the form
    // check the validity of email 
    // check that date is valid date
    // check if data does not exist in DB
    // die(var_dump($nuUser));

    if (!filter_var($nuUser['UserEmail'], FILTER_VALIDATE_EMAIL)) { // check email format
      $this->userError = "Invalid email Address";
      $rtn = false;
    } elseif (preg_match("/^[0-9]{1,2}\\/[0-9]{1,2}\\/[0-9]{4}$/", $nuUser['UserExpiryDate'])) { // check date format
      $this->userError = "Invalid date format [dd/mm/yyyy] " . $nuUser['UserExpiryDate'];
      $rtn = false;
    } else {
      $val = $this->getSpecificUser($nuUser['UserName']); // check if the user already exist
      if ($val) { // does the user already exist then it is a duplicate
        $rtn = false;
        $this->userError = "Duplicate User: <b>" . $nuUser['FullName'] . "</b>";
      } else { // otherwise check if this is a new user
        $rtn = ($valTy == 'new') ? true : false;
        // die(var_dump( $rtn));

      }
    }
    if ($rtn) { // set user details before saving to DB
      $this->irNum = $nuUser['irNum'];
      $this->userName = $nuUser['UserName'];
      $this->userEmail = $nuUser['UserEmail'];
      $this->userFullname = $nuUser['FullName'];
      $this->userRoleID = ($this->isValidRole($nuUser['UserRole'])) ? $nuUser['UserRole'] : 0;
      $this->userExpDate = $nuUser['UserExpiryDate'];
      $this->userUnitID = $nuUser['UserUnitID'];
      $this->userDeptID = $nuUser['userDeptID'];
      $this->userStatusID = $nuUser['UserStatusID'];
    }
    return $rtn;
  }

  private function isValidRole($rle)
  {
    $rtn = false;
    try {
      // build the sql statement
      $sql = "SELECT roleName FROM rt_user_roles WHERE role_id=:id";
      $stmt = $this->connect()->prepare($sql);
      $stmt->bindparam(":id", $rle, PDO::PARAM_INT);
      $stmt->execute();

      // store found data in $rows
      $rows = $stmt->fetchAll();
      if (count($rows) > 0) {
        $rtn = true;
      } else {
        $this->userError = 'Invalid user Role supplied';
      }
    } catch (PDOException $e) {
      $this->userClassState = $rtn = false;
      $msg = $e->getMessage();
      trigger_error($msg, E_USER_NOTICE);
    }

    $this->userClassState = $rtn;
    return $rtn;
  }

  // private function isValidRole($rle)
  // {
  //   $rtn = false;
  //   try {
  //     // build the sql statement
  //     $sql = "SELECT r.Role FROM user_role r WHERE role_id=:id";
  //     $stmt = $this->connect()->prepare($sql);
  //     $stmt->bindparam(":id", $rle, PDO::PARAM_INT);
  //     $stmt->execute();

  //     // store found data in $rows
  //     $rows = $stmt->fetchAll();
  //     if (count($rows) > 0) {
  //       $rtn = true;
  //     } else {
  //       $this->userError = 'Invalid user Role supplied';
  //     }
  //   } catch (PDOException $e) {
  //     $this->userClassState = $rtn = false;
  //     $msg = $e->getMessage();
  //     trigger_error($msg, E_USER_NOTICE);
  //   }

  //   $this->userClassState = $rtn;
  //   return $rtn;
  // }

  private function isValidPosting($pst)
  {
    $rtn = false;
    try {
      // build the sql statement to check that the selected unit exist
      $sql = "SELECT unitName FROM units WHERE unit_id=:id";
      $stmt = $this->connect()->prepare($sql);
      $stmt->bindparam(":id", $pst, PDO::PARAM_INT);
      $stmt->execute();

      // store found data in $rows
      $rows = $stmt->fetchAll();
      if (count($rows) > 0) {
        $rtn = true;
      } else {
        $this->userError = 'Invalid user unit supplied';
        $rtn = false;
      }

      // build sql statement to check if the person is not already in the same unit
      $sql = "SELECT up_Id FROM user_posting WHERE userPostingStatus='active' AND userCurrentUnit=:id";
      $stmt = $this->connect()->prepare($sql);

      $stmt->bindparam(":id", $pst, PDO::PARAM_INT);
      $stmt->execute();

      $rows = $stmt->fetchAll();
      if (count($rows) > 0) {
        $this->userError = 'Duplicate Posting';
        $rtn = false;
      }
    } catch (PDOException $e) {
      $this->userClassState = $rtn = false;
      $this->userError = $msg = $e->getMessage();
      trigger_error($msg, E_USER_NOTICE);
    }

    $this->userClassState = $rtn;
    return $rtn;
  }


  private function getDeptID($uID)
  {
    $rtn = 0;
    try {
      // build the sql statement
      $sql = "SELECT dept_id FROM units WHERE unit_id=:id";
      $stmt = $this->connect()->prepare($sql);
      $stmt->bindparam(":id", $uID, PDO::PARAM_INT);
      $stmt->execute();

      // store found data in $rows
      $rows = $stmt->fetchAll();
      if (count($rows) > 0) {
        foreach ($rows as $row) {
          $rtn = intval($row['dept_id']);
          $this->userClassState = true;
        }
      } else {
        $this->userError = 'Invalid user unit supplied';
      }
    } catch (PDOException $e) {
      $this->userClassState = false;
      $this->userError = $msg = $e->getMessage();
      trigger_error($msg, E_USER_NOTICE);
    }
    return $rtn;
  }

  public function __toString()
  {
    return json_encode($this);
  }

  public function isLOSuccess() //is last operation successful
  {
    return $this->userClassState;
  }

  public function getErrormessage()
  {
    return $this->userError;
  }

  public function getUserID()
  {
    return $this->userID;
  }

  public function getUserUnitID()
  {
    return $this->userUnitID;
  }

  public function getUserRole()
  {
    $this->userClassState = true;
    $tmp = '';
    $tmmp = array();
    try {
      $sql = "SELECT roleName FROM rt_user_roles";
      $stmt = $this->connect()->prepare($sql);
      // $stmt->bindparam(":id", $rle, PDO::PARAM_INT);
      $stmt->execute();

      // store found data in $rec
      while ($rw = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // $tmp = array_push($this->rec, $rw);
        $tmmp[] = ($rw['roleName']);
      }

      $this->userClassState = ($tmmp > 0) ? true : false;
      return $tmmp;
    } catch (PDOException $e) {
      $this->userClassState = false;
      $msg = $e->getMessage();
      trigger_error($msg, E_USER_NOTICE);
    }
    return $this->userClassState;
  }

  function getAllPrivileges()
  {
    $this->userClassState = false;
    try {
      // build the sql statement
      $sql = "SELECT ur.roleName, rp.rtRoleId, pr.PrivilegeNames,rp.rtPrivilegeId FROM user_mgt_db.rt_roles_to_privileges rp
      left join rt_user_roles ur on ur.role_id = rp.rtRoleId 
      left join rt_privileges pr on pr.rt_privileges_id = rp.rtPrivilegeId ";
      $stmt = $this->connect()->prepare($sql);
      $stmt->execute();

      // store found data in $rec
      while ($rw = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tmp = array_push($this->rec, $rw);
        // die(var_dump($tmp));
      }

      $this->userClassState = ($tmp > 0) ? true : false;
    } catch (PDOException $e) {
      $this->userClassState = false;
      $msg = $e->getMessage();
      trigger_error($msg, E_USER_NOTICE);
    }
    return $this->userClassState;
  }

  function getUserPrivileges($roleid)
  {
    $this->userClassState = false;
    $tmp = '';
    try {
      // build the sql statement
      $sql = "SELECT ur.roleName, rp.rtRoleId, pr.PrivilegeNames,rp.rtPrivilegeId FROM user_mgt_db.rt_roles_to_privileges rp
      left join rt_user_roles ur on ur.role_id = rp.rtRoleId 
      left join rt_privileges pr on pr.rt_privileges_id = rp.rtPrivilegeId where rtRoleId =:rid ; ";
      $stmt = $this->connect()->prepare($sql);
      $stmt->bindparam(":rid", $roleid, PDO::PARAM_INT);
      $stmt->execute();

      // store found data in $rec
      while ($rw = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tmp = array_push($this->rec, $rw);
        // die(var_dump($tmp));
      }

      $this->userClassState = ($tmp > 0) ? true : false;
    } catch (PDOException $e) {
      $this->userClassState = false;
      $msg = $e->getMessage();
      trigger_error($msg, E_USER_NOTICE);
    }
    $this->userClassState;

    return $this->setUserPrivileges();;
  }

  private function setUserPrivileges()
  {
    $rtn = array();
    try {
      foreach ($this->rec as $row) {
        $rtn[] = strtolower($row['PrivilegeNames']);
      }
    } catch (PDOException $e) {
      $this->userClassState = false;
      $msg = $e->getMessage();
      trigger_error($msg, E_USER_NOTICE);
    }
    // return $_SESSION['pages'] = $rtn;
    // return var_dump($rtn);
    return $rtn;
  }

  private function accessLevel($department_id, $accessrole )
  {
    // die(var_dump($department_id, $accessrole));
    try {
      $connection = $this->conn->connect();
      $sql = "SELECT rfa.accessLevel, rfa.access_id, rfa.rt_roles_id 
                FROM user_mgt_db.rt_roles_record_access_rights rfa
                LEFT JOIN user_mgt_db.rt_user_roles ur ON ur.role_id = rfa.rt_roles_id
                WHERE rfa.rt_dept_id = :department_id AND rfa.rt_roles_id = :accessrole";

      $stmt = $connection->prepare($sql);
      $stmt->bindParam(':department_id', $department_id, PDO::PARAM_INT);
      $stmt->bindParam(':accessrole', $accessrole, PDO::PARAM_INT);
      $stmt->execute();
      $accessrecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
      // die(var_dump($accessrecords));

      return $accessrecords;
    } catch (PDOException $e) {
      return 'accessLevel Error retrieving record: ' . $e->getMessage();
    }
  }

  public function getAccessLevels()
  {
    return $this->recacc = $this->accessLevel($this->userDeptID, $this->userRoleID);
  }



}
