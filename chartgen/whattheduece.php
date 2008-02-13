<?php

	require_once "chartlib.php";
	require_once "notevalues.php";
	require_once "songnames.php";
	
	
	
	list ($measures, $notetrack, $songname) = parseFile("../mids/highwaystar.mid", "EXPERT", "RB");
	

    /* */
    print_r($measures);
    
    echo "\n\n---\n\n";
    
    print_r($notetrack);
    /* */



?>