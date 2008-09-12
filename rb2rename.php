<?php

	define("MIDIPATH", "mids/");

    require_once 'classes/midi.class.php';


    $files = array();
    
    $dir = opendir(MIDIPATH . "rb2/");
    while (false !== ($file = readdir($dir))) {
        if ($file == "." || $file == "..") continue;
        if (substr($file, -11) == ".parsecache") continue;
        if (substr($file, 0, 1) == "_") continue;
        $files[] = $file;
    }



    foreach ($files as $f) {

        $mid = new Midi;
        $mid->importMid("mids/rb2/" . $f);
        $songname = "";

        $trk = $mid->getTrackTxt(0);
        $track = explode("\n", $trk);
        foreach($track as $t) {
            $info = explode(" ", $t);
            if ($info[1] == "Meta") {
                if ($info[2] == "TrkName") {
                    preg_match('/.*\"(.*)\"$/', $t, $matches);
                    $songname = $matches[1];
                    echo "mv \"" . $f . "\" \"" . $songname . ".mid\"\n";
                    break;
                }
                continue;
            }
        }
    }
    
    
    
?>