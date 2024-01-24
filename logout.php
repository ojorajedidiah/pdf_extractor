<?php 
session_start();

tidyUp();
die('<head><script LANGUAGE="JavaScript">window.location="index.php";</script></head>');

function tidyUp(){
    $_SESSION = array();
    session_unset();
    session_destroy(); 
    return true;
}

?>