<?php
    define("LIB_PATH", "../");
    require_once LIB_PATH . "songnames.php";

    header("Content-type:text/plain");
    if (isset($NAMES[$_SERVER["QUERY_STRING"]])) echo $NAMES[$_SERVER["QUERY_STRING"]];
    else echo $_SERVER["QUERY_STRING"];
?>