<?php

	define("WIDTH", 1170);
	define("PXPERBEAT", /*70*/ 70 /*275*/);
	define("STAFFHEIGHT", 12);
	define("DRAWPLAYERLINES", 0);
	define("CHARTGENVERSION", "0.5.0");

	require_once "parselib.php";
	require_once "notevalues.php";
	require_once "songnames.php";
	require_once "chartlib.php";
	
	
	if (!isset($_GET["file"])) {
		echo "File not specified - Use query string parameter, i.e., chartgen.php?file=danicalifornia (no extension)";
		exit;
	}
	
	$file = preg_replace("-[\./]-", "", $_GET["file"]);
	if (!file_exists("mids/" . strtolower($_GET["game"]) . "/" . $file . ".mid")) {
	   die("Specified file does not exist.");
	}


	
	if (!isset($_GET["game"])) {
		echo "Game not specified - Use query string parameter, i.e., chartgen.php?game=rb (or gh1, or gh2, or gh3)";
		exit;
	}
	//global $game;
	$game = strtolower($_GET["game"]);
	if (!($game == "gh1" || $game == "gh2" || $game == "gh3" || $game == "rb")) {
	   die("Invalid game -- specify one of gh1, gh2, gh3, or rb.");
	}
	
	

	if ((isset($_GET["guitar"]) || isset($_GET["bass"]) || isset($_GET["drums"])) && !isset($_GET["difficulty"])) {
		echo "Difficulty not specified - Use query string paramenter, i.e., chartgen.php?difficulty=expert";
		exit;
	}
	
	$diff = (isset($_GET["difficulty"]) ? strtolower($_GET["difficulty"]) : "");
	// don't ask why I typed those out of order
	if ((isset($_GET["guitar"]) || isset($_GET["bass"]) || isset($_GET["drums"])) && !($diff == "easy" || $diff == "medium"
	       || $diff == "expert" || $diff == "hard")) {
	   die("Invalid difficulty -- specify one of easy, medium, hard, or expert.");
	}
	
	
	//////////
	// call to makeChart here
	
	
	list ($songname, $events, $timetrack, $measures, $notetracks, $vocals, $beat) = parseFile("mids/" . $game . "/" . $file . ".mid", $game, true);
	
	$im = makeChart($notetracks, $measures, $timetrack, $events, $vocals, $diff, $game, isset($_GET["guitar"]),
           isset($_GET["bass"]), isset($_GET["drums"]), isset($_GET["vocals"]), (isset($NAMES[$file]) ? $NAMES[$file] : $file), $beat);

	
	header("Content-type: image/png");
	imagepng($im);
	imagedestroy($im);
	
?>