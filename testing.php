<?php

    require_once "parselib.php";
    require_once "notevalues.php";
    require_once "songnames.php";

    require_once 'classes/midi.class.php';

    #$mid = new Midi;
    #$mid->importMid("mids/rb/whenyouwereyoung.mid");

    #echo "Time Signature Track\n";
    #echo $mid->getTrackTxt(0);
    
    #echo "\n\n\n\n\nVocals Track\n";
    #echo $mid->getTrackTxt(4);

# (songname, events[guitar...vocals], timetrack, measures[guitar...drums][easy...expert], notetracks[guitar...drums][easy...expert], vocals)

    list ($songname, $events, $timetrack, $measures, $notetracks, $vocals) = parseFile("mids/rb/danicalifornia.mid", "RB");

//	list ($measures, $notetrack, $songname, $events) = parseFile("../mids/rb/shouldistay.mid", "EASY", "RB", "GUITAR");

//    print_r($measures);

//print_r($notetrack);

print_r($vocals);


?>