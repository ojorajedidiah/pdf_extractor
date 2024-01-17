<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/classes/databaseConnection.class.php';

use Spatie\PdfToText\Pdf;


if (isset($_POST['submit'])) {
  $uploadDir = 'uploads/';
  $uploadFile = $uploadDir . basename($_FILES['pdfFile']['name']);
  $fileType = pathinfo($uploadFile, PATHINFO_EXTENSION);

  //Check if the file is a PDF
  if ($fileType != "pdf") {
    die('wrong file type!');
  } else {

    $dfile = $_FILES['pdfFile']['tmp_name'];
    try {
      $text = Pdf::getText($dfile);
      $bala = getFigures($text);

      //die(json_encode($bala));
      if (saveToDatabase($bala)) {
        $bala['message'] = 'The record <b>' . $bala['accountNumber'] . '</b> has been successfully saved!';
      }
      echo json_encode($bala);
    } catch (Exception $e) {
      die('there is an error ' . $e->getMessage());
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

  $extracts = array();

  // split the content by tabs
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

  //die(json_encode($words));

  // extract the account number, reporting date and account date/time
  $extracts['reportDate'] = trim($words[0]) . ' ' . trim($words[1]);
  $extracts['accountNumber'] = $words[4];

  $dt = new DateTime($words[0]);
  $intv = new DateInterval('P1D');
  $extracts['accountDate'] = $dt->sub($intv)->format('Y-m-d');

  //die(json_encode($extracts));

  // extract the opening balance
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
            $extracts['openingBalance'] = $words[$u];
            break;
          }
        }
      }
    }
  }

  //die(json_encode($extracts));

  // extract the closing balance
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
            $extracts['closingBalance'] = $words[$u];
            break;
          }
        }
      }
    }
  }

  //die(json_encode($extracts));

  // extract any debit entry(ies)
  $c = 1;
  for ($x = 0; $x < count($words); $x++) {
    $hays = $words[$x];
    $myit = $x + 5;
    // extract strings that have and find the word 'Multi Debit Entry' in it

    // if ((str_contains($words[$x], 'Multi Debit Entry') && substr_count($words[$x], ',') >= 1)) {
    if (substr_count($hays, '0156\\B') >= 1) {
      // extract the next strings after the above but has commas (figures) 
      for ($u = $x + 1; $u <= $myit; $u++) {
        $e = $words[$u];
        if (substr_count($e, ',') >= 1) {
          $lbl = 'Debit-Entry-' . $c;
          // this is the correspnding figure for the Balance identified above
          $extracts[$lbl] = $words[$u];
          $c++;
          break;
        }
      }
      // }
    }
  }

  //die(json_encode($extracts));
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
      $lbl = 'Debit-Entry-' . $c;
      $total += convertToNumber($data[$lbl]); //sum up all debits
      $debits .= '[' . $data[$lbl] . ']:';
      $c++;
    }
  }


  try {
    // echo 'all is fine inside savetoDatabase<br>';

    $db = new connectDatabase();
    if ($db->isLastQuerySuccessful()) {
      $con = $db->connect();
      $sql = "INSERT INTO cbn_collection_test (crd,cad,can,obal,cbal,deb,debtot) VALUES (:crd,:cad,:obal,:cbal,:deb,:debtot)";

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
    die('saveToDatabase error ' . $db->connectionError());
    $rtn = false;
  }

  $con->closeConnection();
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