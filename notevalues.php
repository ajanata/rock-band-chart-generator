<?php

    define('REVISION', 245);
    
    /*
    global $REVISION;
    $rev = array();
    #exec("/usr/bin/svnversion /Users/ajanata/Development/SPOpt/phpspopt2", &$rev);
    exec("printenv", &$rev);
    $REVISION = $rev[0];
    print_r($rev);
    */

    define('EPS', 1.0e-10);

    // notes closer than 15 pulses are a chord
    define('CHORD', 15);


    // notes longer than 160 pulses are sustains
    define('SUSTAIN', 160);
    
    // in seconds
    define('VOCAL_FILL_WINDOW', .6);
    
    
    $CONFIG["GH1"]["multi"][] = 10;
    $CONFIG["GH1"]["multi"][] = 20;
    $CONFIG["GH1"]["multi"][] = 30;
    // if chord sustains are worth # notes times as much
    $CONFIG["GH1"]["chord_sustain_bonus"] = true;
    $CONFIG["GH1"]["gem_score"] = 50;
    $CONFIG["GH1"]["ticks_per_beat"] = 25;
    $CONFIG["GH1"]["ticks_at_new_multi"] = true;
    $CONFIG["GH1"]["hopo_threshold"] = 0;              // check
    
    $CONFIG["GH2"]["multi"][] = 9;
    $CONFIG["GH2"]["multi"][] = 19;
    $CONFIG["GH2"]["multi"][] = 29;
    $CONFIG["GH2"]["chord_sustain_bonus"] = true;
    $CONFIG["GH2"]["gem_score"] = 50;
    $CONFIG["GH2"]["ticks_per_beat"] = 25;
    $CONFIG["GH2"]["ticks_at_new_multi"] = false;           // check
    $CONFIG["GH2"]["hopo_threshold"] = 0;              // check
        
    $CONFIG["GHOT"]["multi"][] = 9;
    $CONFIG["GHOT"]["multi"][] = 19;
    $CONFIG["GHOT"]["multi"][] = 29;
    $CONFIG["GHOT"]["chord_sustain_bonus"] = true;
    $CONFIG["GHOT"]["gem_score"] = 50;
    $CONFIG["GHOT"]["ticks_per_beat"] = 22;
    $CONFIG["GHOT"]["ticks_at_new_multi"] = false;           // check
    $CONFIG["GHOT"]["hopo_threshold"] = 0;              // check
        
    $CONFIG["GH3"]["multi"][] = 9;
    $CONFIG["GH3"]["multi"][] = 19;
    $CONFIG["GH3"]["multi"][] = 29;
    $CONFIG["GH3"]["chord_sustain_bonus"] = false;
    $CONFIG["GH3"]["gem_score"] = 50;
    $CONFIG["GH3"]["ticks_per_beat"] = 25;
    $CONFIG["GH3"]["ticks_at_new_multi"] = false;           // check
    $CONFIG["GH3"]["hopo_threshold"] = 0;              // check

    
    $CONFIG["RB"]["multi"][] = 9;
    $CONFIG["RB"]["multi"][] = 19;
    $CONFIG["RB"]["multi"][] = 29;
    $CONFIG["RB"]["multi"][] = 39;                      // only for bass
    $CONFIG["RB"]["multi"][] = 49;
    $CONFIG["RB"]["chord_sustain_bonus"] = true;
    $CONFIG["RB"]["gem_score"] = 25;
    $CONFIG["RB"]["ticks_per_beat"] = 12;
    $CONFIG["RB"]["ticks_at_new_multi"] = false;            // check
    $CONFIG["RB"]["hopo_threshold"] = 170;              // pulses    
    

    $NOTES["GH1"]["EASY"]["G"] = 60;
    $NOTES["GH1"]["EASY"]["R"] = 61;
    $NOTES["GH1"]["EASY"]["Y"] = 62;
    $NOTES["GH1"]["EASY"]["B"] = 63;
    $NOTES["GH1"]["EASY"]["O"] = 64;
    $NOTES["GH1"]["EASY"]["STAR"] = 67;
    $NOTES["GH1"]["EASY"]["P1"] = 69;
    $NOTES["GH1"]["EASY"]["P2"] = 70;
    
    $NOTES["GH1"]["MEDIUM"]["G"] = 72;
    $NOTES["GH1"]["MEDIUM"]["R"] = 73;
    $NOTES["GH1"]["MEDIUM"]["Y"] = 74;
    $NOTES["GH1"]["MEDIUM"]["B"] = 75;
    $NOTES["GH1"]["MEDIUM"]["O"] = 76;
    $NOTES["GH1"]["MEDIUM"]["STAR"] = 79;
    $NOTES["GH1"]["MEDIUM"]["P1"] = 81;
    $NOTES["GH1"]["MEDIUM"]["P2"] = 82;
    
    $NOTES["GH1"]["HARD"]["G"] = 84;
    $NOTES["GH1"]["HARD"]["R"] = 85;
    $NOTES["GH1"]["HARD"]["Y"] = 86;
    $NOTES["GH1"]["HARD"]["B"] = 87;
    $NOTES["GH1"]["HARD"]["O"] = 88;
    $NOTES["GH1"]["HARD"]["STAR"] = 91;
    $NOTES["GH1"]["HARD"]["P1"] = 93;
    $NOTES["GH1"]["HARD"]["P2"] = 94;
    
    $NOTES["GH1"]["EXPERT"]["G"] = 96;
    $NOTES["GH1"]["EXPERT"]["R"] = 97;
    $NOTES["GH1"]["EXPERT"]["Y"] = 98;
    $NOTES["GH1"]["EXPERT"]["B"] = 99;
    $NOTES["GH1"]["EXPERT"]["O"] = 100;
    $NOTES["GH1"]["EXPERT"]["STAR"] = 103;
    $NOTES["GH1"]["EXPERT"]["P1"] = 105;
    $NOTES["GH1"]["EXPERT"]["P2"] = 106;
    
    
    $NOTES["GH2"] = &$NOTES["GH1"];
    $NOTES["GH3"] = &$NOTES["GH1"];
    $NOTES["GHOT"] = &$NOTES["GH1"];
    
    
    $NOTES["RB"]["EASY"]["G"] = 60;
    $NOTES["RB"]["EASY"]["R"] = 61;
    $NOTES["RB"]["EASY"]["Y"] = 62;
    $NOTES["RB"]["EASY"]["B"] = 63;
    $NOTES["RB"]["EASY"]["O"] = 64;
    $NOTES["RB"]["EASY"]["HOPO"] = 65;
    $NOTES["RB"]["EASY"]["STRUM"] = 66;
    $NOTES["RB"]["EASY"]["STAR"] = 116;
    $NOTES["RB"]["EASY"]["SOLO"] = 67;
    $NOTES["RB"]["EASY"]["P1"] = 105; #69;
    $NOTES["RB"]["EASY"]["P2"] = 106; #70;
    
    $NOTES["RB"]["MEDIUM"]["G"] = 72;
    $NOTES["RB"]["MEDIUM"]["R"] = 73;
    $NOTES["RB"]["MEDIUM"]["Y"] = 74;
    $NOTES["RB"]["MEDIUM"]["B"] = 75;
    $NOTES["RB"]["MEDIUM"]["O"] = 76;
    $NOTES["RB"]["MEDIUM"]["HOPO"] = 77;
    $NOTES["RB"]["MEDIUM"]["STRUM"] = 78;
    $NOTES["RB"]["MEDIUM"]["STAR"] = 116;
    $NOTES["RB"]["MEDIUM"]["SOLO"] = 79;
    $NOTES["RB"]["MEDIUM"]["P1"] = 105; #81;
    $NOTES["RB"]["MEDIUM"]["P2"] = 106; #82;
    
    $NOTES["RB"]["HARD"]["G"] = 84;
    $NOTES["RB"]["HARD"]["R"] = 85;
    $NOTES["RB"]["HARD"]["Y"] = 86;
    $NOTES["RB"]["HARD"]["B"] = 87;
    $NOTES["RB"]["HARD"]["O"] = 88;
    $NOTES["RB"]["HARD"]["HOPO"] = 89;
    $NOTES["RB"]["HARD"]["STRUM"] = 90;
    $NOTES["RB"]["HARD"]["STAR"] = 116;
    $NOTES["RB"]["HARD"]["SOLO"] = 91;
    $NOTES["RB"]["HARD"]["P1"] = 105; #93;
    $NOTES["RB"]["HARD"]["P2"] = 106; #94;
    
    $NOTES["RB"]["EXPERT"]["G"] = 96;
    $NOTES["RB"]["EXPERT"]["R"] = 97;
    $NOTES["RB"]["EXPERT"]["Y"] = 98;
    $NOTES["RB"]["EXPERT"]["B"] = 99;
    $NOTES["RB"]["EXPERT"]["O"] = 100;
    $NOTES["RB"]["EXPERT"]["STAR"] = 116;
    // forced hopo
    $NOTES["RB"]["EXPERT"]["HOPO"] = 101;
    // forced strum
    $NOTES["RB"]["EXPERT"]["STRUM"] = 102;
    $NOTES["RB"]["EXPERT"]["SOLO"] = 103;
    $NOTES["RB"]["EXPERT"]["P1"] = 105;
    $NOTES["RB"]["EXPERT"]["P2"] = 106;
    
    
    $NOTES["RB"]["EASY"]["FILL"]["G"] = 120;
    $NOTES["RB"]["EASY"]["FILL"]["R"] = 121;
    $NOTES["RB"]["EASY"]["FILL"]["Y"] = 122;
    $NOTES["RB"]["EASY"]["FILL"]["B"] = 123;
    $NOTES["RB"]["EASY"]["FILL"]["O"] = 124;
    
    $NOTES["RB"]["MEDIUM"]["FILL"] = $NOTES["RB"]["EASY"]["FILL"];
    $NOTES["RB"]["HARD"]["FILL"] = $NOTES["RB"]["EASY"]["FILL"];
    $NOTES["RB"]["EXPERT"]["FILL"] = $NOTES["RB"]["EASY"]["FILL"];

    $NOTES["RB"]["EASY"]["SOLO"] = $NOTES["RB"]["EXPERT"]["SOLO"];
    $NOTES["RB"]["MEDIUM"]["SOLO"] = $NOTES["RB"]["EXPERT"]["SOLO"];
    $NOTES["RB"]["HARD"]["SOLO"] = $NOTES["RB"]["EXPERT"]["SOLO"];

    
    // notes 120-124 denote the free-fill sections (GRYBO -- they always seem identical to me)
    
?>
