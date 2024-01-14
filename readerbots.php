<?php
require_once __DIR__ . '/vendor/autoload.php';

use Spatie\PdfToText\Pdf;


// if (isset($_POST['submit'])) {
//     $uploadDir = 'uploads/';
//     $uploadFile = $uploadDir . basename($_FILES['pdfFile']['name']);
//     $fileType = pathinfo($uploadFile, PATHINFO_EXTENSION);

//     // Check if the file is a PDF
//     if ($fileType != "pdf") {
//         echo "Only PDF files are allowed.";
//     } else {
//         // Move the uploaded file to the specified directory
//         if (move_uploaded_file($_FILES['pdfFile']['tmp_name'], $uploadFile)) {
//             echo "File has been successfully uploaded.";
//         } else {
//             echo "Error uploading the file.";
//         }
//     }
// }
// die('it works '.var_dump(pathinfo($uploadFile,PATHINFO_ALL)));



if (isset($_POST['submit'])) {
    $dfile = $_FILES['pdfFile']['tmp_name'];
    try {
        $text = Pdf::getText($dfile);
        // $bala = getBalanceInfo($text);
        $bala = getFigures($text);

        echo json_encode($bala);
    } catch (Exception $e) {
        echo 'There is error ' . $e->getMessage();
    }
} else {
    echo 'you did not submit the form';
}


function getBalanceInfo($str)
{
    $extracts = array();
    // split the contents by spaces
    // get account nummber entry
    // iterate over the array for the words 'Balance','Multi Debit Entry'
    // iterate over the possibleBalance to get entries with comma (,)
    // iterate over the resulting array and split using ' '
    // iterate over the resulting array and remove unwanted data
    // iterate over the resulting array and extract only number and the decimal point data


    // split the content by spaces
    $tmp[] = explode(" ", $str);
    for ($a = 0; $a < count($tmp[0]); $a++) {
        echo $tmp[0][$a] . '<br>';
    }


    // get account nummber entry
    $acct = str_split($tmp[0][4]);
    $acctNum = '';
    $accttmp = '';
    for ($i = 0; $i <= count($acct) - 1; $i++) {
        if (ord($acct[$i]) == 10) {
            if (substr($accttmp, 0, 1) == '0') {
                $acctNum = $accttmp;
                break;
            }
            $accttmp = '';
        } else {
            $accttmp .= $acct[$i];
        }
    }

    $extracts['accountNumber'] = $acctNum;

    $possibleBalance = array();

    // iterate over the array and find the word Balance
    for ($x = 0; $x <= count($tmp[0]) - 1; $x++) {
        $hays = $tmp[0][$x];
        $myit = $x + 4;
        if (substr_count($hays, 'Balance') >= 1) {  // extract strings that have Balance in it
            $possibleBalance[] = $tmp[0][$x];
            // extract the next strings after the above but has commas (figures) 
            // and does not contain the word Balance
            if (!(str_contains($tmp[0][$x], 'Balance') && substr_count($tmp[0][$x], ',') >= 1)) {
                for ($u = $x + 1; $u <= $myit; $u++) {
                    $e = $tmp[0][$u];
                    if (substr_count($e, ',') >= 1 && !(str_contains($e, 'Balance'))) {
                        // this is the correspnding figure for the Balance identified above
                        $possibleBalance[] = $tmp[0][$u];
                        break;
                    }
                }
            }
        }
    }
    // die(var_dump($possibleBalance));

    // iterate over the array and find the word 'Multi Debit Entry' 
    for ($x = 0; $x <= count($tmp[0]) - 1; $x++) {
        $hays = $tmp[0][$x];
        $myit = $x + 7;
        if (substr_count($hays, '0156\B') >= 1) {
            $possibleBalance[] = $tmp[0][$x];
            for ($u = $x + 1; $u <= $myit; $u++) {
                $e = $tmp[0][$u];
                if (substr_count($e, ',') >= 1) {
                    $possibleBalance[] = $tmp[0][$u];
                    // break;
                }
            }
        }
    }

    // die(var_dump($possibleBalance));

    // iterate over the possibleBalance to get entries with comma (,)
    for ($x = 0; $x <= count($possibleBalance) - 1; $x++) {
        $hays = $possibleBalance[$x];
        if (substr_count($hays, ',') >= 1) {
            $Balance[] = $possibleBalance[$x];
        }
    }

    // die(var_dump($Balance));


    // iterate over the Balances and split using the tab
    $c = 0;
    $tabVal = array();
    $b = 0;
    $tmp = '';
    for ($x = 0; $x <= count($Balance) - 1; $x++) {
        $splt[$c] = str_split($Balance[$x]);
        $arrlgt = count($splt[$c]);
        for ($y = 0; $y <= count($splt[$c]); $y++) {
            if (ord($splt[$c][$y]) == 10 || $y == $arrlgt) {
                if (strlen($tmp) > 1) {
                    $tabVal[$b] = $tmp;
                    $b++;
                    $tmp = '';
                }
            } else {
                $tmp .= $splt[$c][$y];
            }
        }
        $c++;
    }

    // die(var_dump($tabVal));

    $Balance = array();
    // iterate over the tabVal to get entries with comma (,)
    for ($x = 0; $x <= count($tabVal) - 1; $x++) {
        $hays = $tabVal[$x];
        if (substr_count($hays, ',') >= 1) {
            $Balance[] = $tabVal[$x];
        }
    }

    // die(var_dump($Balance));

    // iterate over the Balance and extract only number and the decimal point
    $c = 0;
    for ($x = 0; $x <= count($Balance) - 1; $x++) {
        $splt[$c] = str_split($Balance[$x]);
        $amount = '';
        for ($y = 0; $y <= count($splt[$c]); $y++) {
            // echo var_dump($splt[$c][$y]).'<br>';
            $amount .= (is_numeric($splt[$c][$y]) || $splt[$c][$y] == '.')
                ? $splt[$c][$y]
                : '';
        }
        $finalBal[] = $amount;
        $c++;
    }

    // die(var_dump($finalBal));

    // compare figures to remove duplicates
    // the first and second figures are the opening and closing balances respectively
    // define the opening, closing and debits parameters

    $extracts['openingBalance'] = $finalBal[0];
    $extracts['closingBalance'] = $finalBal[1];
    $c = 1;
    if (count($finalBal) > 2) {
        $x = 2;
        while ($x <= count($finalBal) - 1) {
            if (!(in_array($finalBal[$x], $extracts))) { // remove dplicates
                if ($finalBal[$x + 1] < $finalBal[0]) {
                    $lbl = 'Debit Entry-' . $c;
                    $extracts[$lbl] = $finalBal[$x];
                    $c++;
                    $x += 2;
                }
            }
            $x++;
        }
    }

    return $extracts;
}


function getFigures($str)
{
    // split the content by tabs
    // extract the account number, reporting date and account date/time
    // extract the opening balance
    // extract the closing balance
    // extract any debit entry(ies)

    $extracts=array();

    // split the content by tabs
    $txt = str_split($str);
    $wrd = '';
    $words=array();
    for ($i = 0; $i < count($txt); $i++) {
        if (ord($txt[$i]) == 10) {
            if (strlen($wrd) > 1){
                $words[]=$wrd;
                $wrd = '';
            }
        } else {
            // if ()
            $wrd .= $txt[$i];
        }
    }

    // die(json_encode($words));
    
    // extract the account number, reporting date and account date/time
    $extracts['reportDate']=$words[0].' '.$words[1];
    $extracts['accountNumber']=$words[4];
    
    $dt=new DateTime($words[0]);
    $intv=new DateInterval('P1D');
    $extracts['accountDate']=$dt->sub($intv)->format('Y-m-d');
    
    // die(json_encode($extracts));

    // extract the opening balance
    for ($x=0; $x<count($words); $x++){
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

    // die(json_encode($extracts));

    // extract the closing balance
    for ($x=0; $x<count($words); $x++){
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

    // die(json_encode($extracts));

    // extract any debit entry(ies)
    $c = 1;
    for ($x=0; $x<count($words); $x++){
        $hays = $words[$x];
        $myit = $x + 5;
        // extract strings that have and find the word 'Multi Debit Entry' in it

            // if ((str_contains($words[$x], 'Multi Debit Entry') && substr_count($words[$x], ',') >= 1)) {
        if (substr_count($hays, '0156\\B') >= 1) {  
            // extract the next strings after the above but has commas (figures) 
                for ($u = $x + 1; $u <= $myit; $u++) {
                    $e = $words[$u];
                    if (substr_count($e, ',') >= 1) {
                        $lbl='Debit Entry-'.$c;
                        // this is the correspnding figure for the Balance identified above
                        $extracts[$lbl] = $words[$u];
                        $c++;
                        break;
                    }
                }
            // }
        }
    }

    // die(json_encode($extracts));
    return $extracts;
}
