<?php
#   Author of the script
#   Name: Adeleke Ojora
#   Email : adeleke.ojora@firs.gov.ng
#   Date created: 
#   Date modified: 28th June 2022 
#   Date updated:16th November 2022
#		Modified and Updated by: Adeleke Ojora

class ldapConnect
{
  // internal properties they are input values for this class
  protected $username;
  protected $password;

  // ldap connection 
  protected $ldapConn;

  // external properties output variables 
  protected $userDetails = array();
  protected $response = array();
  public $userProfile; // user profile as a JSON dataset
  protected $connected=false;



  // The Class contrust it access username and password used as credentials 
  // to access the Active Directory datase
  public function __construct($username, $password)
  {
    try {
      //---------start FIRS Specific settings--------//
      $this->username = strtolower($username);
      $this->password = $password;
      $ldapuri = "firs-hq-dc-01.internal.firs.gov.ng";
      $ldapconn = ldap_connect($ldapuri, 389); // or die("That LDAP-URI was not parseable");

      $dn = "firs\\" . $this->username;
      //---------end FIRS Specific settings--------//
      
      if (!empty($this->username) && !empty($this->password)) {
        $ldapbind = @ldap_bind($ldapconn, $dn, $this->password); // attempt to connect to the AD      

        if ($ldapbind) {
          $this->setResponses(200, 'success', 'Successfully');
          $this->ldapConn = $ldapconn;
          $this->connected=true;
          if ($this->connected) {$this->setDetails();};
        } else {
          $this->setResponses(ldap_errno($ldapconn), 'ldap error', ldap_err2str(ldap_errno($ldapconn)));
        }
      } else {
        $this->setResponses(401, 'ldap error', 'Empty username or password');
      }
    } catch (Exception $e) {
      $this->setResponses(ldap_errno($ldapconn), 'ldap error', ldap_err2str(ldap_errno($ldapconn)));
    }
  }

  //-------another external interface to confirm connectino to LDAP-------//
  public function isConnected()
  {
    return $this->connected;
  }

  //-------another external interface for user details from LDAP-------//
  public function __toString()
  {
    return (isset($this->userDetails) && $this->getUserProfile()) ? json_encode($this->userProfile) : "Empty user details";
  }

  //-------another external interface for user details from LDAP-------//
  public function userDetails()
  {
    return (isset($this->userDetails) && $this->getUserProfile()) ? $this->userProfile : "Empty user details";
  }

  //-------external interface to search LDAP for specific user--------//
  public function searchLDAP($s)
  {
    $dnCnn = "OU=FIRS,DC=internal,DC=firs,DC=gov,DC=ng";
    $flt = "(samaccountname=$s)";
    $resSet = array("dn", "cn", "title", "samaccountname", "mail", "department", "company", "telephonenumber", "displayname", "mobile");    

    if (ldap_search($this->ldapConn, $dnCnn, $flt) === false) {
      $this->setResponses(401, 'User Details not found', 'AD details for ' . $s . ' not found');
    } else {
      $ldapsearch = ldap_search($this->ldapConn, $dnCnn, $flt, $resSet);
      $this->userDetails = ldap_get_entries($this->ldapConn, $ldapsearch);
      if ($this->userDetails["count"] == 0){
        $this->setResponses(401, 'User Details not found', 'AD details for ' . $s . ' not found');
      }
      // echo json_encode(ldap_get_entries($this->ldapConn, $ldapsearch));
    }
  }

  public function getSearchResult()
  {
    $rtn = false;
    if (isset($this->userDetails) && $this->userDetails["count"] > 0) {
      for ($i = 0; $i < $this->userDetails["count"]; $i++) {
        if ($this->isDisabledUsed($this->userDetails[$i]["dn"])) {
          //get user profile into a array
          $this->userProfile[$i]['fullname'] = (strlen($this->userDetails[$i]["displayname"][0]) > 0)
            ? $this->userDetails[$i]["displayname"][0] : $this->userDetails[$i]["cn"][0];

          $this->userProfile[$i]['username'] = (strlen($this->userDetails[$i]["samaccountname"][0]) > 0)
            ? $this->userDetails[$i]["samaccountname"][0] :  "None";

          $this->userProfile[$i]['email'] = (isset($this->userDetails[$i]["mail"][0]) && strlen($this->userDetails[$i]["mail"][0]) > 0)
            ? $this->userDetails[$i]["mail"][0] : "None";

          $this->userProfile[$i]['designation'] = (isset($this->userDetails[$i]["title"][0]) && strlen($this->userDetails[$i]["title"][0]) > 0)
            ? $this->userDetails[$i]["title"][0] : "None";

          $this->userProfile[$i]['department'] = (isset($this->userDetails[$i]["department"][0]) && strlen($this->userDetails[$i]["department"][0]) > 0)
            ? $this->userDetails[$i]["department"][0] : "None";

          $this->userProfile[$i]['telephonenumber'] = (isset($this->userDetails[$i]["telephonenumber"][0]) && strlen($this->userDetails[$i]["telephonenumber"][0]) > 0)
            ? $this->userDetails[$i]["telephonenumber"][0] : "None";

          $this->userProfile[$i]['irnum'] = (isset($this->userDetails[$i]["company"][0]) && strlen($this->userDetails[$i]["company"][0]) > 0)
            ? $this->userDetails[$i]["company"][0] : "None";

          $rtn = true;
        }
      }
    }
    return ($rtn) ? $this->userProfile : $this->getResponses();
  }

  private function isDisabledUsed($str)
  {
    $rtn = false;
    $found = stristr($str, "Disabled Users");
    $rtn = ($found) ? false : true;
    return $rtn;
  }

  //----format the user details from LDAP into a presentable format----//
  private function getUserProfile()
  {
    $rtn = false;
    if (isset($this->userDetails) && $this->userDetails["count"] > 0) {
      for ($i = 0; $i < $this->userDetails["count"]; $i++) {
        //get user profile into a array
        $this->userProfile['fullname'] = (strlen($this->userDetails[$i]["displayname"][0]) > 0)
          ? $this->userDetails[$i]["displayname"][0] : $this->userDetails[$i]["cn"][0];

        $this->userProfile['mail'] = (isset($this->userDetails[$i]["mail"][0]) && strlen($this->userDetails[$i]["mail"][0]) > 0)
          ? $this->userDetails[$i]["mail"][0] : "None";

        $this->userProfile['designation'] = (isset($this->userDetails[$i]["title"][0]) && strlen($this->userDetails[$i]["title"][0]) > 0)
          ? $this->userDetails[$i]["title"][0] : "None";

        $this->userProfile['department'] = (isset($this->userDetails[$i]["department"][0]) && strlen($this->userDetails[$i]["department"][0]) > 0)
          ? $this->userDetails[$i]["department"][0] : "None";

        $this->userProfile['telephonenumber'] = (isset($this->userDetails[$i]["telephonenumber"][0]) && strlen($this->userDetails[$i]["telephonenumber"][0]) > 0)
          ? $this->userDetails[$i]["title"][0] : "None";

        $this->userProfile['irnum'] = (isset($this->userDetails[$i]["company"][0]) && strlen($this->userDetails[$i]["company"][0]) > 0)
          ? $this->userDetails[$i]["company"][0] : "None";

        $rtn = true;
      }
    }

    return $rtn;
  }

  //---------retrieve user details from the LDAP connect--------//
  //---------This function is to retrun the user details--------//
  //------------used in establishing connection to LDAP--------//
  private function setDetails()
  {
    $s = $this->getUsername();
    $dnCnn = "OU=FIRS,DC=internal,DC=firs,DC=gov,DC=ng";
    $flt = "(samaccountname=$s*)";
    $resSet = array("cn", "title", "mail", "department", "company", "telephonenumber", "displayname", "mobile");

    if (ldap_search($this->ldapConn, $dnCnn, $flt) === false) {
      $this->setResponses(401, 'User Details not found', 'AD details ' . $s . ' not found');
    } else {
      $ldapsearch = ldap_search($this->ldapConn, $dnCnn, $flt, $resSet);
      $this->userDetails = ldap_get_entries($this->ldapConn, $ldapsearch);
    }
  }


  //----------set responses of the attempt to connect to LDAP--------//
  private function setResponses($stCode, $stMessage, $stData)
  {
    $this->response['status'] = $stCode;
    $this->response['status_message'] = $stMessage;
    $this->response['data'] = $stData;
    // $this->response['interface'] = $stInter;
    return true;
  }

  //--------external interface for LDAP response--------//
  public function getResponses()
  {
    return $this->response;
  }




  // Below functions/interfaces are updates to enhance utilisation

  public function getUsername()
  {
    return $this->username;
  }

  public function getDepartment()
  {
    return (isset($this->userProfile['department'])) ? $this->userProfile['department'] : '';
  }

  public function getFullName()
  {
    return (isset($this->userProfile['fullname'])) ? $this->userProfile['fullname'] : '';
  }

  
  public function getEmail()
  {
    return (isset($this->userProfile['email'])) ? $this->userProfile['email'] : '';
  }

  public function closeLDAP()
  {
    ldap_close($this->ldapConn);
    return true;
  }
}
