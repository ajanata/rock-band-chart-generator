<?php

    require_once "parselib.php";
    require_once "notevalues.php";
    require_once "songnames.php";

    require_once 'classes/midi.class.php';


	define("WIDTH", 1010);
	define("PXPERBEAT", 60);
	define("STAFFHEIGHT", 12);
	define("DRAWPLAYERLINES", 0);

    require_once "chartlib.php";
    
    

  /* */
    $mid = new Midi;
    $mid->importMid("mids/rb/creep.mid");

    #echo "Time Signature Track\n";
    #echo $mid->getTrackTxt(0);
    
    #echo "\n\n\n\n\nGuitar Track\n";
    #echo $mid->getTrackTxt(4);
    
    /* * /
    for ($i = 0; $i < $mid->getTrackCount(); $i++) {
        echo "\n=== Track $i \n";
        $trk = $mid->getTrackTxt($i);
        $track = explode("\n", $trk);
        foreach ($track as $line) {
            if (strpos($line, "PART") !== false) {
                echo $line . "\n";
                continue;
            }
            if ($line == "MTrk") continue;
            $info = explode(" ", $line);
            
            if (!isset($info[1])) continue;
            if ($info[1] == "Meta") {
                echo $line . "\n";
                continue;
            }
            
            echo $line . "\n";
            continue;
            
            if (!isset($info[3]) || !isset($info[4])) continue;
            $note = (int)substr($info[3], 2);
            $xyzzy = false;
            
            #if ($note >= 40 && $note <= 59) $xyzzy = true;
            #if ($note == 12 || $note == 13) $xyzzy = true;
            
            /*foreach(array("EASY", "MEDIUM", "HARD", "EXPERT") as $diff) {
                foreach ($NOTES["RB"][$diff] as $n) {
                    if (is_array($n)) {
                        foreach ($n as $m) {
                            if ($m == $note) $xyzzy = true;
                        }
                        continue;
                    }
                    else {
                        if ($n == $note) $xyzzy = true;
                    }
                }
            } * /
            
            if (!$xyzzy) echo $line . "\n";
        }

    }
    
    exit;

  /* */


# (songname, events[guitar...vocals], timetrack, measures[guitar...drums][easy...expert], notetracks[guitar...drums][easy...expert], vocals)

    list ($songname, $events, $timetrack, $measures, $notetracks, $vocals) = parseFile("mids/rb/creep.mid", "RB", true);

//	list ($measures, $notetrack, $songname, $events) = parseFile("../mids/rb/shouldistay.mid", "EASY", "RB", "GUITAR");

//    print_r($measures);

//print_r($notetrack);



echo "=== Song Length\n\n";
echo getClockTimeBetweenPulses($timetrack, 0, $vocals["TrkEnd"]);

echo "\n\n=== Measures\n\n";
print_r($measures);

echo "\n\n=== Notes\n\n";
print_r($notetracks);

echo "\n\n=== Time Track\n\n";
print_r($timetrack);

echo "\n\n=== Events\n\n";
print_r($events);

echo "\n\n=== Vocals\n\n";
print_r($vocals);


?>
