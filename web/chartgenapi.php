<?php

    define("LIB_PATH", "../");

    define("DRAWPULSES", false);
    define("SHOWFORCED", false);

    # these defaults must be defined below as they can be changed based on the input
    #define("WIDTH", 1010);
    #define("BPMPRECISION", 1);
    #define("PXPERBEAT", 60);
    #define("STAFFHEIGHT", 12);
    define("DRAWPLAYERLINES", false);

    define("MID_PATH", "../mids/");
    define("ACCESS_LOG", LIB_PATH . "api_access.log");

    require_once LIB_PATH . "parselib.php";
    require_once LIB_PATH . "notevalues.php";
    require_once LIB_PATH . "songnames.php";
    require_once LIB_PATH . "chartlib.php";
    require_once "users.php";


    if (!isset($_GET["game"])) {
        echo "Game not specified - Use query string parameter, i.e., chartgenapi.php?game=rb (or gh1, or gh2, or gh3)";
        exit;
    }

    $game = strtolower($_GET["game"]);
    if (!($game == "gh1" || $game == "gh2" || $game == "gh3" || $game == "rb" || $game == "ghot")) {
       die("Invalid game -- specify one of gh1, gh2, gh3, ghot, or rb (use rb for rb2).");
    }
    
    
    if (!isset($_GET["file"])) {
        echo "File not specified - Use query string parameter, i.e., chartgenapi.php?file=danicalifornia (no extension)";
        exit;
    }
    
    $file = preg_replace("-[\./]-", "", $_GET["file"]);
    if (!file_exists(MID_PATH . strtolower($_GET["game"]) . "/" . $file . ".mid")) {
       die("Specified file does not exist.");
    }
    
    
    if (!(isset($_GET["guitar"]) || isset($_GET["bass"]) || isset($_GET["drums"]) || isset($_GET["vocals"]))) {
       die("No instruments specified - Use query string parameter, i.e., chartgenapi.php?guitar=expert");
    }
    
    // FIXME need to make the back-end work with different difficulties first, then we can do the same here
    // for now just use the first of guitar, bass, drums, vocals
    $diff = "";
    if (isset($_GET["guitar"])) $diff = $_GET["guitar"];
    else if (isset($_GET["bass"])) $diff = $_GET["bass"];
    else if (isset($_GET["drums"])) $diff = $_GET["drums"];
    else $diff = $_GET["vocals"];
    
    if ($diff != "easy" && $diff != "medium" && $diff != "hard" && $diff != "expert") {
       die("Invalid difficulty $diff -- choose one of easy, medium, hard, or expert.");
    }
    
    if (isset($_GET["input"])) {
       if (substr($_GET["input"], 0, 7) != "http://") die ("Input location must be an http:// URL.");
       $inputloc = $_GET["input"];
    }
    else $inputloc = "php://input";
    
    if (($input = fopen($inputloc, "r")) === false) {
       die("Unable to open $inputloc for reading");
    }
    
    // make sure the user is valid
    $line = explode(" ", rtrim(fgets($input)));
    $log = fopen(ACCESS_LOG, "a");
    fwrite($log, date("r") . " - " . $_SERVER["REMOTE_ADDR"] . " - ");
    if ($line[0] != "user" || !isset($USERS[$line[1]]) || $line[2] != md5($USERS[$line[1]] . $file)) {
        fwrite($log, "Access denied for user " . $line[1] . " for song " . $file . " with hash " . $line[2] . "\n"); 
        die("First line of input does not contain valid user information.");
    }
    else {
        $user = $line[1];
        fwrite($log, "User " . $user . " requested song " . $file . "\n");
    }
    
    // now we know they're valid, so do the normal parsing so we can start messing with it
    
    list ($songname, $events, $timetrack, $measures, $notetracks, $vocals, $beat) = parseFile(MID_PATH . $game . "/" . $file . ".mid", $game, false);
    
    // now that we have the song parsed, we can loop over everything they want us to do and mutilate the image
    
    $events["colors"] = array();
    $later_text = array();
    $later_colorname = array();
    $shift_down = 0;
    
    while (!feof($input)) {
        $rawline = rtrim(fgets($input));
        if ($rawline == "") continue;
        $line = explode(" ", strtolower($rawline));
        if ($line[0] != "option" && $line[0] != "comment") break;
        if ($line[0] == "comment") continue;
        
        switch ($line[1]) {
            case "shift":
                $shift_down = ($line[2] < 0 ? 0 : ($line[2] > 1000 ? 1000 : $line[2]));
                break;
                
            case "width":
                define("WIDTH", ($line[2] < 1000 ? 1000 : ($line[2] > 3000 ? 3000 : $line[2])));
                break;
                
            case "ppqn":
                define("PXPERBEAT", ($line[2] < 20 ? 20 : ($line[2] > 480 ? 480 : $line[2])));
                break;
                
            case "lineheight":
                define("STAFFHEIGHT", ($line[2] < 10 ? 10 : ($line[2] > 40 ? 40 : $line[2])));
                break;
                
            case "tempoprecision":
                define("BPMPRECISION", ($line[2] < 0 ? 0 : ($line[2] > 5 ? 5 : $line[2])));
                break;
        }
    }

    if (!defined("WIDTH")) define("WIDTH", 1010);
    if (!defined("BPMPRECISION")) define("BPMPRECISION", 1);
    if (!defined("PXPERBEAT")) define("PXPERBEAT", 60);
    if (!defined("STAFFHEIGHT")) define("STAFFHEIGHT", 12);

    $skip = true;
    while (!feof($input)) {
        if (!$skip) {
            $rawline = rtrim(fgets($input));
            if ($rawline == "") continue;
            $line = explode(" ", strtolower($rawline));
        }
        else $skip = false;
        switch ($line[0]) {
            case "comment": continue;
            // option isn't valid after non-comment non-option commands
            case "option": continue;
            case "color":
                $e = array();
                $e["type"] = "api-color";
                $e["n"] = $line[1];
                $e["r"] = $line[2];
                $e["g"] = $line[3];
                $e["b"] = $line[4];
                $events["colors"][] = $e;
                break;
                
            case "coloralpha":
                $e = array();
                $e["type"] = "api-coloralpha";
                $e["n"] = $line[1];
                $e["r"] = $line[2];
                $e["g"] = $line[3];
                $e["b"] = $line[4];
                $e["a"] = $line[5];
                $events["colors"][] = $e;
                break;

            case "colorname":
                $e = array();
                $e["color"] = $line[1];
                $e["name"] = preg_replace("/colorname (\d|\w)+ /", "", $rawline, 1);
                $later_colorname[] = $e;
                break;
                
            case "string":
                $size = $line[1];
                if ($size > 5) $size = 5;
                else if ($size < 1) $size = 1;
                $color = $line[2];
                $x = $line[3];
                $y = $line[4];
                $text = preg_replace("/string \d+ (\d|\w)+ \d+ (-)?\d+ /", "", $rawline, 1);
                if ($y == 0) {
                    // this text gets drawn above a notechart
                    $e = array();
                    $e["type"] = "api-text";
                    $e["start"] = $x;
                    $e["end"] = $x;
                    $e["color"] = $color;
                    $e["text"] = strstr($text, " ");
                    $e["size"] = $size;
                    $xyzzy = explode(" ", $text);
                    $events[$xyzzy[0]][] = $e;
                }
                else {
                    // absolutely positioned text. we have to store it for later as we don't have the image yet
                    $e = array();
                    $e["x"] = $x;
                    // we fix for bounds later
                    $e["y"] = $y;
                    $e["size"] = $size;
                    $e["color"] = $color;
                    $e["text"] = $text;
                    $later_text[] = $e;
                }
                break;
                
            case "line":
                //line <instrument> <color> <start> <end> <y> <height>
                $e = array();
                $e["type"] = "api-line";
                $e["start"] = $line[3];
                $e["end"] = $line[4];
                $e["color"] = $line[2];
                $e["offset"] = $line[5];
                if ($e["offset"] < -4 * STAFFHEIGHT) $e["offset"] = -4 * STAFFHEIGHT;
                else if ($e["offset"] > 30) $e["offset"] = 30;
                $e["height"] = $line[6];
                if ($e["height"] > 5) $e["height"] = 5;
                else if ($e["height"] < 1) $e["height"] = 1;
                $events[$line[1]][] = $e;
                break;
                
            case "fill":
                //fill <instrument> <color> <start> <end> <height>
                $e = array();
                $e["type"] = "api-fill";
                $e["start"] = $line[3];
                $e["end"] = $line[4];
                $e["color"] = $line[2];
                $e["height"] = $line[5];
                if ($e["height"] > 30) $e["height"] = 30;
                else if ($e["height"] < 0) $e["height"] = 0;
                $events[$line[1]][] = $e;
                break;
                
            // FIXME these 4 need to know what difficulty the individual instrument is using when I fix that above
            case "measscore":
                //measscore <instrument> <#> <score>
                if (!isset($measures[$line[1]][$line[2]-1])) break;
                $measures[$line[1]][$line[2]-1]["mscore"][$diff] = $line[3];
                break;
                
            case "totalscore":
                if (!isset($measures[$line[1]][$line[2]-1])) break;
                $measures[$line[1]][$line[2]-1]["cscore"][$diff] = $line[3];
                break;
                
            case "bonusscore":
                if (!isset($measures[$line[1]][$line[2]-1])) break;
                $measures[$line[1]][$line[2]-1]["bscore"][$diff] = $line[3];
                break;
                
            case "whammy":
                if (!isset($measures[$line[1]][$line[2]-1])) break;
                // this isn't actually stored in the array presently, but we're sticking it there for the override
                // chartlib has been updated to use this if present and fall back to default behavior if it isn't
                $measures[$line[1]][$line[2]-1]["whammy"][$diff] = $line[3];
                break;
        }
       
    }
    fclose($input);
    
    // and now draw the image with the new data structures
    
    $im = makeChart($notetracks, $measures, $timetrack, $events, $vocals, $diff, $game, isset($_GET["guitar"]),
           isset($_GET["bass"]), isset($_GET["drums"]), isset($_GET["vocals"]), (isset($NAMES[$file]) ? $NAMES[$file] : $file), $beat, $shift_down);

    global $APICOLORS;
    foreach ($later_text as $e) {
        if ($e["y"] < 0) $e["y"] = imagesy($im) + $e["y"];
        if ($e["y"] < 45) {
            if ($e["x"] < 300) $e["x"] = 300;
            if ($e["x"] + imagefontwidth($e["size"]) * strlen($e["text"]) > WIDTH - 200)
                $e["x"] = WIDTH - 200 - imagefontwidth($e["size"]) * strlen($e["text"]);
        }
        else if ($e["y"] > imagesy($im) - 60) $e["y"] = imagesy($im) - 60;
        imagestring($im, $e["size"], $e["x"], $e["y"], $e["text"], $APICOLORS[$e["color"]]);
    }

    if (count($later_colorname) > 0) {
        $silver = imagecolorallocate($im, 168, 168, 168);
        $boxwidth = 10;
        foreach ($later_colorname as $e) {
            $boxwidth += imagefontwidth(3) * (strlen($e["name"]) + 1);
        }
        imagefilledrectangle($im, WIDTH-$boxwidth, 15 + SHOWFORCED*15 + DRAWPLAYERLINES*15, WIDTH, 30 + SHOWFORCED*15 + DRAWPLAYERLINES*15, $silver);
        $boxwidth -= 10;
        foreach ($later_colorname as $e) {
            imagestring($im, 3, WIDTH - $boxwidth, 15 + SHOWFORCED*15 + DRAWPLAYERLINES*15, $e["name"], $APICOLORS[$e["color"]]);
            $boxwidth -= imagefontwidth(3) * (1 + strlen($e["name"]));
        }
    }


    // draw the chart api information
    $gray = imagecolorallocate($im, 134, 134, 134);
    imagestring($im, 3, 0, imagesy($im) - 40, "Generated with chartgen API by user " . $user . " -- see http://ajanata.com/projects/phpspopt/api for more information.", $gray);
    
    header("Content-type: image/png");
    imagepng($im);
    imagedestroy($im);


?>