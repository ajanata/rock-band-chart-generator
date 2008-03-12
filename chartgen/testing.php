<?php

    require_once "parselib.php";
    require_once "notevalues.php";
    require_once "songnames.php";



    //parseFile("../mids/dontfearthereaper.mid", "EXPERT", "RB", "GUITAR");

	list ($measures, $notetrack, $songname, $events) = parseFile("../mids/rb/shouldistay.mid", "EASY", "RB", "GUITAR");

    print_r($measures);

//print_r($notetrack);



?>