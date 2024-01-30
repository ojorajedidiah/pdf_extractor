<?php

#****Statement Reader Bot v1.5
#***Author of the script
#***Name: Adeleke Ojora
#***Email : adeleke.ojora@firs.gov.ng
#***Date created: 11/01/2024
#***Version number: v1.0
#***Date modified: 22/01/2024
#***Date modified by: Adeleke Ojora
#***Version number: v1.25 Update with ability to understand protrait statements
#***Date modified: 24/01/2024
#***Date modified by: Adeleke Ojora
#***Version number: v1.5  Update to enhance the functionality and incorporate business logic


#   The code is to read the content of specific bank statement 
#   to extract opening balance, closing balance and debit entry(s) 
#   and other relevant information.


require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/classes/databaseConnection.class.php';

use Spatie\PdfToText\Pdf;


if (isset($_POST['submit'])) {
    //$uploadDir = 'uploads/';
    //$uploadFile = $uploadDir . basename($_FILES['pdfFile']['name']);
    $fileType = pathinfo($_FILES['pdfFile']['name'], PATHINFO_EXTENSION);

    //************** Check if the uploaded file is a PDF ***************//
    if ($fileType != "pdf") {
        die('wrong file type!');
    } else {

        $dfile = $_FILES['pdfFile']['tmp_name'];
        try {
            echo '<br>Reading the content of the uploaded file...<br>';
            $text = Pdf::getText($dfile);
            $bala = getFigures($text);

            if (saveToDatabase($bala)) {
                $dtt=new DateTime($bala['accountDate']);
                $bala['message'] = 'The record of <b>' . $bala['accountNumber'] .'</b> for <b>'. $dtt->format('d M Y'). '</b> has been successfully saved!';
            }
            presentExtracts($bala);
        } catch (Exception $e) {
            die('there is an error <b>' . $e->getMessage().'</b>');
        }
    }
}




function getFigures($str)
{
    // split the content by tabs
    // extract the account number, reporting date and account date/time
    // extract the opening balance
    // extract the closing balance
    // extract any debit entry(ies)
    echo 'Processing the contents of the file...<br>';

    $extracts = array();

    //*************** split the content by tabs ***************//
    $txt = str_split($str);
    $wrd = '';
    $words = array();
    for ($i = 0; $i < count($txt); $i++) {
        if (ord($txt[$i]) == 10) {
            if (strlen($wrd) > 1) {
                $words[] = $wrd;
                $wrd = '';
            }
        } else {
            // if ()
            $wrd .= $txt[$i];
        }
    }

    // die(json_encode($words));

    if (
        in_array('Account Statement', $words)
        || in_array('CBN STATEMENT OF ACCOUNT', $words)
        || in_array('FEDERAL INLAND REVENUE SERV', $words)
        || in_array('ACCOUNTANT GEN OF THE FEDER', $words) 
        || in_array('Closing Balance', $words)
        || in_array('AT PERIOD', $words) 
        || in_array('Balance at Period E', $words)  //'
    ) {


        echo 'Relevant content has been found and being processed...<br>';

        //*************** extract the reporting date/time ***************//
        $extracts['reportDate'] = trim($words[0]) . ' ' . trim($words[1]);

        //*************** extract the account number ***************//
        $acctNum = '';
        for ($i = 0; $i <= count($words) - 1; $i++) {
            if (substr($words[$i], 0, 2) == '00' && strlen($words[$i]) == 13) {
                $acctNum = $words[$i];
                break;
            }
        }
        $extracts['accountNumber'] = $acctNum;


        //*************** extract the account date ***************//
        $acctDat = '';
        for ($i = 3; $i <= count($words) - 1; $i++) {
            if ($words[$i] == 'Value Date') { // get the first occurrance of Value Date
                $nu=$i+1;
                for($j=$nu; $j<=count($words); $j++) {
                    if (substr_count($words[$j],' ') > 1    // if the word has more than one space
                        && is_numeric(substr($words[$j],0,2))  // if the first two characters are numbers
                        && strlen($words[$j]) == 9) {  // if the length of the word is 9 then it is a date
                        $acctDat = $words[$j];
                        break;
                    }
                }
            }
        }

        // if the extracted date is empty then use the day before the report date
        if(strlen($acctDat)==0){
            $dt = new DateTime($words[0]);
            $intv = new DateInterval('P1D');
            $extracts['accountDate'] = $dt->sub($intv)->format('Y-m-d');
        } else{ // other wise format the value
            $dt = new DateTime($acctDat);
            $extracts['accountDate'] = $dt->format('Y-m-d');
        }

        // die(json_encode($extracts));

        //*************** extract the opening balance ***************//
        $tm='';
        if (in_array('CBN STATEMENT OF ACCOUNT', $words)){ // strategy one
            for ($x = 0; $x < count($words); $x++) {
                $hays = $words[$x];                
                if ($hays == 'Balance At') {  // extract strings that have Balance At in it
                    // extract the next strings after the above but has commas (figures) 
                    // and does not contain the word Balance At
                    // !(str_contains($words[$u], 'Balance At') && 
                    $myit = $x + 10;
                    for ($u = $x + 1; $u <= $myit; $u++) {
                        if (substr_count($words[$u], ',') >= 1) {
                            // this is the corresponding figure for the Balance identified above
                            $tm='';  //substr($e,$n,1) == '.' || substr($e,$n,1) == ',' || 
                            for($n=$u;$n<=$u+4;$n++){
                                if((substr_count($words[$n], ',') >= 1 
                                  || substr_count($words[$n], '.') >= 1) 
                                  && is_numeric(substr($words[$n],0,1))) {
                                    $tm.= $words[$n];
                                }
                            }                            
                            break 2;
                        }
                    }
                }
            }
        } else { //if(in_array('ACCOUNTANT GEN OF THE FEDER', $words) //strategy two
          //|| in_array('FEDERAL INLAND REVENUE SERV', $words)){    
            for ($x = 0; $x < count($words); $x++) {
                $hays = $words[$x];
                $myit = $x + 4;
                if (substr_count($hays, 'Closing Balance') >= 1) {  // extract strings that have Balance in it
                    // extract the next strings after the above but has commas (figures) 
                    // and does not contain the word Balance
                    if (!(str_contains($words[$x], 'Closing Balance') && substr_count($words[$x], ',') >= 1)) {
                        for ($u = $x + 1; $u <= $myit; $u++) {
                            $e = $words[$u];
                            if (substr_count($e, ',') >= 1 && !(str_contains($e, 'Balance'))) {
                                // this is the correspnding figure for the Balance identified above
                                $tm='';
                                for($n=0;$n<=strlen($e);$n++){
                                    if(substr($e,$n,1) == '.' || substr($e,$n,1) == ',' || is_numeric(substr($e,$n,1))) {
                                        $tm.= substr($e,$n,1);
                                    }
                                }
                                break;
                            }
                        }
                    }
                }
            }
        }
        $extracts['openingBalance'] = $tm;

        // die(json_encode($extracts));


        //********** extract the closing balance ***************//
        $tm='';
        if (in_array('CBN STATEMENT OF ACCOUNT', $words)){ // strategy one 
            for ($x = 0; $x < count($words); $x++) {
                $hays = $words[$x];
                $myit = $x + 4;
                if (substr_count($hays, 'AT PERIOD') >= 1) { // extract strings that have PERIOD in it
                    // extract the next strings after the above but has commas (figures) 
                    // and does not contain the word Balance At
                    for ($u = $x + 1; $u <= $myit; $u++) {
                        if (!(str_contains($words[$u], 'AT PERIOD') && substr_count($words[$u], ',') >= 1)) {
                            // this is the corresponding figure for the Balance identified above
                            $tm='';  //substr($e,$n,1) == '.' || substr($e,$n,1) == ',' || 
                            for($n=$u;$n<=$u+3;$n++){
                                if((substr_count($words[$n], ',') >= 1 
                                  || substr_count($words[$n], '.') >= 1) 
                                  && is_numeric(substr($words[$n],0,1))) {
                                    $tm.= $words[$n];
                                }
                            }                            
                            break;
                        }
                    }
                }
            }
        } else  { //if(in_array('ACCOUNTANT GEN OF THE FEDER', $words)  //strategy two 
          //|| in_array('FEDERAL INLAND REVENUE SERV', $words)){   
            for ($x = 0; $x < count($words); $x++) {
                $hays = $words[$x];
                $myit = $x + 4;
                if (substr_count($hays, 'Balance at Period E') >= 1) {  // extract strings that have Balance in it
                    // extract the next strings after the above but has commas (figures) 
                    // and does not contain the word Balance
                    if (!(str_contains($words[$x], 'Balance') && substr_count($words[$x], ',') >= 1)) {
                        for ($u = $x + 1; $u <= $myit; $u++) {
                            $e = $words[$u];
                            if (substr_count($e, ',') >= 1 && !(str_contains($e, 'Balance'))) {
                                // this is the correspnding figure for the Balance identified above
                                $tm='';
                                for($n=0;$n<=strlen($e);$n++){
                                    if(substr($e,$n,1) == '.' || substr($e,$n,1) == ',' || is_numeric(substr($e,$n,1))) {
                                        $tm.= substr($e,$n,1);
                                    }
                                }
                                break;
                            }
                        }
                    }
                }
            }
        }

        $extracts['closingBalance'] = $tm;
        // die(json_encode($extracts));

        //*************** extract any debit entry(ies) ***************//
        $c = 1;
        $acctNum=$extracts['accountNumber'];$lgt=strlen($acctNum)-3;
        for ($x = 0; $x < count($words); $x++) {
            $hays = $words[$x];
            $myit = $x + 5;
            // extract strings that have the following account numbers in it
            // if ((str_contains($words[$x], 'Multi Debit Entry') && substr_count($words[$x], ',') >= 1)) {
            if  (debitConditions($hays)) {
                // extract the next strings after the above but has commas (figures) 
                if(substr($acctNum,$lgt,3) == '247' 
                  || substr($acctNum,$lgt,3) == '032') { // 247 & 032 accts have a special treatment of debit entries
                    $tmp=array_slice($words,$x,10);
                    // echo (json_encode($tmp).'<br>');
                    if (arrayContainsValidDebit($tmp)) {
                        for ($u = 0; $u < count($tmp); $u++) {
                            if (substr_count($tmp[$u], ',') >= 1) {  /// 
                                $lbl = 'debitEntry-' . $c;
                                // this is the correspnding figure for the Balance identified above
                                $extracts[$lbl] = $tmp[$u];
                                $c++;
                                break 2;  // it is assumed that there can only be only debit entry on these special accounts
                            }
                        }
                    }
                } else if(substr_count($hays, 'OAGF Operating Su') >= 1) {  // OAGF Operating Surplus Debits
                    $y=(($x-10)>=0)?$x-10:0;
                    $tmp=array_slice($words,$y,10); // extract the last ten items into $tmp
                    // echo (json_encode($tmp).'<br>');
                    if(!in_array('OAGF Operating Su',$tmp)){
                        for($u=0;$u<count($tmp);$u++){
                            if (substr_count($tmp[$u], ',') >= 1) {
                                $lbl = 'debitEntry-' . $c;
                                // this is the correspnding figure for the Balance identified above
                                $extracts[$lbl] = $tmp[$u];
                                $c++;
                                break;  // it is assumed that there can only be only debit entry on these special accounts
                            }
                        }
                    }
                } else {
                    for ($u = $x + 1; $u <= $myit; $u++) {
                        $e = $words[$u];
                        if (substr_count($e, ',') >= 1) {
                            $lbl = 'debitEntry-' . $c;
                            // this is the correspnding figure for the Balance identified above
                            $extracts[$lbl] = $words[$u];
                            $c++;
                            break;
                        }
                    }
                }
            }
        }
    } else {
        die('<b>File does not content the appropriate dataset</b>');
    }

    return $extracts;
}



function saveToDatabase($data)
{
    $rtn = false;
    $debits = '';
    $total = 0;

    if (count($data) > 5) {
        $c = 1;
        for ($s = 5; $s < count($data); $s++) {
            $lbl = 'debitEntry-' . $c;
            $total += convertToNumber($data[$lbl]); //sum up all debits
            $debits .= '[' . $data[$lbl] . ']:';
            $c++;
        }
    }


    try {

        $db = new connectDatabase();
        if ($db->isLastQuerySuccessful()) {
            $con = $db->connect();
            $sql = "INSERT INTO cbn_collection_test (crd,cad,can,obal,cbal,deb,debtot) VALUES (:crd,:cad,:can,:obal,:cbal,:deb,:debtot)";

            $stmt = $con->prepare($sql);

            $stmt->bindparam(":crd", $data['reportDate'], PDO::PARAM_STR);
            $stmt->bindparam(":cad", $data['accountDate'], PDO::PARAM_STR);
            $stmt->bindparam(":can", $data['accountNumber'], PDO::PARAM_STR);
            $stmt->bindparam(":obal", convertToNumber($data['openingBalance']), PDO::PARAM_STR);
            $stmt->bindparam(":cbal", convertToNumber($data['closingBalance']), PDO::PARAM_STR);
            $stmt->bindparam(":deb", $debits, PDO::PARAM_STR);
            $stmt->bindparam(":debtot", $total, PDO::PARAM_STR);

            $row = $stmt->execute();

            $rtn = true;
        } else {
            die($db->connectionError());
        }
    } catch (Exception $e) {
        die('saveToDatabase error ' . $db->connectionError() . ' ' . $e->getMessage());
        $rtn = false;
    }

    $db->closeConnection();
    return $rtn;
}

function convertToNumber($str)
{
    $rtn = '';
    $stx = str_split($str);
    for ($i = 0; $i < count($stx); $i++) {
        if ($stx[$i] != ',') {
            $rtn .= $stx[$i];
        }
    }
    return floatval($rtn);
}

function presentExtracts($arr)
{
    echo '<br>';
  foreach ($arr as $key => $val) {
    echo '<b>'. $key.'</b> => ' .$val.'<br>';
  }
  return true;
}

function debitConditions($hay)
{
    $rtn=false;
    if(substr_count($hay, 'MDC2334800161') >= 1 
    || substr_count($hay, 'MDC2336300537') >= 1
    || substr_count($hay, 'MDC2335300162') >= 1
    || substr_count($hay, 'MDC2335500138') >= 1
    || substr_count($hay, 'MDC2335200043') >= 1
    || substr_count($hay, 'MDC2401100156') >= 1
    || substr_count($hay, 'MDC2335200062') >= 1
    || substr_count($hay, 'OAGF Operating Su') >= 1)
    {
        $rtn=true;
    }


    return $rtn;
}

function arrayContainsValidDebit($arr){
    $rtn=false;
    for($i=0;$i<count($arr);$i++){
        if (str_contains($arr[$i],'POSTING')){
            $rtn=true;
        }
    }

    return $rtn;
}
?>