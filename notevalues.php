<?php

    define('EPS', 1.0e-10);

    // notes closer than 30 pulses are a chord
    define('CHORD', 30);


    // notes longer than 160 pulses are sustains
    define('SUSTAIN', 160);
    
    
    $CONFIG["GH1"]["multi"][] = 10;
    $CONFIG["GH1"]["multi"][] = 20;
    $CONFIG["GH1"]["multi"][] = 30;
    // if chord sustains are worth # notes times as much
    $CONFIG["GH1"]["chord_sustain_bonus"] = true;
    $CONFIG["GH1"]["gem_score"] = 50;
    $CONFIG["GH1"]["ticks_per_beat"] = 25;
    
    $CONFIG["GH2"]["multi"][] = 9;
    $CONFIG["GH2"]["multi"][] = 19;
    $CONFIG["GH2"]["multi"][] = 29;
    $CONFIG["GH2"]["chord_sustain_bonus"] = true;
    $CONFIG["GH2"]["gem_score"] = 50;
    $CONFIG["GH2"]["ticks_per_beat"] = 25;
    
    $CONFIG["GH3"]["multi"][] = 9;
    $CONFIG["GH3"]["multi"][] = 19;
    $CONFIG["GH3"]["multi"][] = 29;
    $CONFIG["GH3"]["chord_sustain_bonus"] = false;
    $CONFIG["GH3"]["gem_score"] = 50;
    $CONFIG["GH3"]["ticks_per_beat"] = 25;
    
    $CONFIG["RB"]["multi"][] = 9;
    $CONFIG["RB"]["multi"][] = 19;
    $CONFIG["RB"]["multi"][] = 29;
    $CONFIG["RB"]["chord_sustain_bonus"] = true;
    $CONFIG["RB"]["gem_score"] = 25;
    $CONFIG["RB"]["ticks_per_beat"] = 25;
  
    
    


    $NOTES['EASY']['G'] = 60;
    $NOTES['EASY']['R'] = 61;
    $NOTES['EASY']['Y'] = 62;
    $NOTES['EASY']['B'] = 63;
    $NOTES['EASY']['O'] = 64;
    $NOTES['EASY']['STAR'] = 67;
    $NOTES['EASY']['P1'] = 69;
    $NOTES['EASY']['P2'] = 70;
    
    $NOTES['MEDIUM']['G'] = 72;
    $NOTES['MEDIUM']['R'] = 73;
    $NOTES['MEDIUM']['Y'] = 74;
    $NOTES['MEDIUM']['B'] = 75;
    $NOTES['MEDIUM']['O'] = 76;
    $NOTES['MEDIUM']['STAR'] = 79;
    $NOTES['MEDIUM']['P1'] = 81;
    $NOTES['MEDIUM']['P2'] = 82;
    
    $NOTES['HARD']['G'] = 84;
    $NOTES['HARD']['R'] = 85;
    $NOTES['HARD']['Y'] = 86;
    $NOTES['HARD']['B'] = 87;
    $NOTES['HARD']['O'] = 88;
    $NOTES['HARD']['STAR'] = 91;
    $NOTES['HARD']['P1'] = 93;
    $NOTES['HARD']['P2'] = 94;
    
    $NOTES['EXPERT']['G'] = 96;
    $NOTES['EXPERT']['R'] = 97;
    $NOTES['EXPERT']['Y'] = 98;
    $NOTES['EXPERT']['B'] = 99;
    $NOTES['EXPERT']['O'] = 100;
    $NOTES['EXPERT']['STAR'] = 103;
    $NOTES['EXPERT']['P1'] = 105;
    $NOTES['EXPERT']['P2'] = 106;
    






?>