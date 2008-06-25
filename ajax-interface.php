<?php
	define("MIDIPATH", "mids/");
	
    require_once "songnames.php";

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Rock Band Pathing Assistant</title>

<script type="text/javascript">
var http;

function getHTTPObject() {
    if (window.ActiveXObject) {
        return new ActiveXObject("Microsoft.XMLHTTP");
    }
    else if (window.XMLHttpRequest) {
        return new XMLHttpRequest();
    }
    else {
        alert("Your browser does not support AJAX.");
        return null;
    }
}

function getHTTP() {
    http = getHTTPObject();
    if (http == null) {
        window.status = "Unable to create an XMLHttpRequest object - page will not function!";
        document.getElemenentByID("status").value = "Unable to create an XMLHttpRequest object - page will not function!";
        alert("Unable to create an XMLHttpRequest object - page will not function!");
    }
    else {
        document.getElementByID('status').value = "Ready.";
    }
    http.onReadyStateChange = callback;
}

function updateParameters() {
    if (http == null) return;
    http.open("GET", "ajax-server.php?song="+document.getElementById('song').value, true);
    http.send(null);
    alert('asdf');
}

function callback() {
    if (http.readyState == 4) {
        document.getElementById("status").value = http.responseText;
        alert(http.responseText);
    }
}
</script>
</head>
 
<body onLoad="getHTTP();">

<form name="control">
Song: <select name="song" id="song">
<option value="" selected="true">choose</option>
<?php
    $files = getFiles();
    foreach ($files as $file) {
        echo "<option value=\"" . $file ."\">" . (isset($NAMES[$file]) ? $NAMES[$file] : $file) . "</option>\n";
    }
?>
</select>
Guitar: <select name="guitar" id="guitar">
<?php difficultyOptions(); ?>
</select>
Bass: <select name="bass" id="bass">
<?php difficultyOptions(); ?>
</select>
Drums: <select name="drums" id="drums">
<?php difficultyOptions(); ?>
</select>
Vocals: <select name="vocals" id="vocals">
<?php difficultyOptions(); ?>
</select>
<input type="button" name="update" value="Update" onClick="updateParameters();" />
<input type="text" name="status" id="status" readonly="true" value="Loading..." size="50" />
</form>
</body>
</html>
<?php

function getFiles() {
    $files = array();
    
    $dir = opendir(MIDIPATH . "rb/");
    if ($dir === false) die("Unable to open directory " . MIDIPATH . "rb/ for reading.\n");
    while (false !== ($file = readdir($dir))) {
        if ($file == "." || $file == "..") continue;
        if (substr($file, -4) != ".mid") continue;
        $files[] = substr($file, 0, strlen($file) - 4);
    }
    
    closedir($dir);
    return $files;
}

function difficultyOptions() {
    ?>
    <option value="none" selected="true">none</option>
    <option value="easy">easy</option>
    <option value="medium">medium</option>
    <option value="hard">hard</option>
    <option value="expert">expert</option>
    <?php
}


?>