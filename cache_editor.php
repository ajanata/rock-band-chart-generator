<!DOCTYPE HTML SYSTEM>
<?php

	require_once "parselib.php";
	require_once "notevalues.php";
	require_once "songnames.php";

/*
    $cache = loadCache("_rockband_chartgen.cache");

foreach ($cache as $song => $data) {
    foreach ($data as $inst => $junk) {
	if ($inst != "bass") continue;
	unset($cache[$song][$inst]);
    }
}
        saveCache("_rockband_chartgen.cache", $cache);
exit;
*/

    if (!isset($_SERVER["HTTP_HOST"])) die("This program must be run through a web server.\n");

    $cache = loadCache("_rockband_chartgen.cache");

    if (isset($_POST["process"])) {
        unset($_POST["process"]);
        
        ?>
<html><head><title>Processing...</title></head>
<body>
<ul>
        <?php
        
        foreach ($_POST as $what => $junk) {
            $details = split("-", $what);
            
            switch ($details[0]) {
                case "song":
                    if (isset($cache[$details[1]])) unset($cache[$details[1]]);
                    echo "<li>Deleted song entry for " . (isset($NAMES[$details[1]]) ? $NAMES[$details[1]] : $details[1]) . ".</li>\n";
                    break;
                case "inst":
                    if (isset($cache[$details[2]][$details[1]])) unset($cache[$details[2]][$details[1]]);
                    echo "<li>Deleted instrument entry for " . (isset($NAMES[$details[2]]) ? $NAMES[$details[2]] : $details[2])
                            . " " . $details[1] . ".</li>\n";
                    break;
            }
        }
        ?>
</ul>
<p><a href="cache_editor.php">Back to cache editor.</a></p>
</body></html>
        <?php
        
        saveCache("_rockband_chartgen.cache", $cache);
        exit;
    }

?>
<html><head><title>phpspopt2 chartgen cache editor</title></head><body>

<p>Currently, you may only delete items from the cache. Check the box next to the items you wish to be deleted.</p>

<form action="cache_editor.php" method="post">
<ul>
<?php
foreach ($cache as $song => $data) {
    ?>
    <li><input type="checkbox" name="song-<?= $song ?>"> <?= isset($NAMES[$song]) ? $NAMES[$song] : $song ?>
    <ul>
    <?php
    foreach ($data as $inst => $junk) {
        ?>
        <li><input type="checkbox" name="inst-<?= $inst ?>-<?= $song ?>"> <?= $inst ?></li>
    <?php
    }
    ?>
    </ul></li>
    <?php
}
?>
</ul>
<input type="submit" name="process" value="Delete selected">
<input type="reset" value="Select none">
</form>

</body></html>
<?php

    function loadCache($file) {
        // if the file doesn't exist, return an empty array (i.e., no cache data)
        if (!file_exists($file)) {
            return array();
        }
        
        $cache = fopen($file, 'r');
        $stat = fstat($cache);
        $serialized = fread($cache, $stat["size"]);
        $unserialized = unserialize($serialized);
        return $unserialized;
    }
    
    function saveCache($file, $array) {
        $cache = fopen($file, 'w');
        if ($cache) {
            fwrite($cache, serialize($array));
        }
    }
    
?>
