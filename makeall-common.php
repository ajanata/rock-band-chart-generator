<?php

    define("CHARTVERSION", 3);
    define("DRUMSVERMOD", 1);
    define("RB_CACHE", "_rockband_chartgen.cache");
    
    
    function loadCache($file) {
        // if the file doesn't exist, return an empty array (i.e., no cache data)
        if (!file_exists($file)) {
            echo "Cache file not found! Starting with empty cache.\n";
            return array();
        }
        
        echo "Loading cache...\n";

        $cache = fopen($file, 'r');
        $stat = fstat($cache);
        $serialized = fread($cache, $stat["size"]);
        $unserialized = unserialize($serialized);
        return $unserialized;
    }
    
    function saveCache($file, $array) {
        echo "Saving cache...\n";
        $cache = fopen($file, 'w');
        if ($cache) {
            fwrite($cache, serialize($array));
        }
    }
    
    function index_header($fhand, $title) {
        fwrite($fhand, "<html>\n<head>\n<title>Blank Charts for Rock Band $title</title>\n</head>\n");
        fwrite($fhand, <<<EOT
<body>
<p><a href="#skip">Skip to the charts!</a> Last update: 
EOT
);
        fwrite($fhand, date("r"));
        fwrite($fhand, <<<EOT
</p>
<p>Significant changes recently:
<ul>
<li>Clock time since last OD note before drum activation fill. This is listed at the end of the file, in the same place solo bonuses and BRE estimates are. The window for a fill showing up is around 2.43 seconds. There is a glitched time in Learn to Fly but I have no idea what's causing it presently.</li>
<li><b>Band per-measure scores</b>, more or less. This is currently done "stupidly", and does not include vocals. It is "stupid" because it takes each instrument's per-measure score and multiplies it by the instrument's maximum multiplier, regardless of whether such a multiplier is possible yet at that point. Vocals is on the to-do list and maybe a smarter way of doing it.</li>
</ul></p>
<p>These charts are blank. They have not been verified against the game and may be faulty. If you see something horribly wrong please <a href="http://rockband.scorehero.com/forum/privmsg.php?mode=post&u=52545">send me a message</a> on ScoreHero. Relevant discussion threads for <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=4773">drums</a>, <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=5062">guitar/bass/guitar+bass</a>, <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=7625">vocals</a>, <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=7626">vocaltar</a>, and <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=7627">full band</a>.</p>
<p>They are in alphabetical order by .mid file name (this normally doesn't mean anything, but "the" is often left out). Probably easier to find a song this way anyway.</p>
<p>Solo note counts and estimated upperbound Big Rock Ending bonuses listed above where the solo or ending ends. To the bottom right of each measure are numbers relating to that measure. Black is the measure score (no multiplier taken into account). Red is the cumulative score to that point (with multipliers) without solo bonuses. Green (on guitar parts only) is cumulative score to that point counting solo bonuses. Blue is the number of whammy beats (no early whammy taken into account) in that measure.</p>
<p>Vocal activation zones are not stored in the .mid as they are with drums. This leads me to believe that any gap larger than a certain amount of time (be it clock time or number of beats, I'm not sure) is an activation zone. At some point in the not-too-distant future I intend to do more research on this.</p>
<p>Overdrive phrase backgrounds extend the exact range specified in the .mid file. Sometimes this is significantly shorter than the length of a sustained note (see third note in <a href="/charts/rb/guitar/foreplaylongtime_guitar_expert_blank.png">Foreplay/Long Time</a> for example).</p>
<p>The .mid BEAT track is displayed on every chart. The game uses this to determine how long Overdrive lasts. A full bar of Overdrive always lasts for exactly 32 BEAT track beats. Most of the time this is 16, 32, or 64 noteboard beats, depending on tempo. Sometimes, it isn't (see the first break in Foreplay/Long Time for an example). I don't see the two events in the BEAT track doing different things in the gameplay (perhaps different stage lighting or something but nothing that matters for pathing), so I've drawn them all in the same color. If it isn't obvious, you want to look at the small red lines above every set of lines (this also makes a nice seperator for multi-instrument parts). <u>Note that this <b>DOES NOT</b> affect whammy rate, only usage rate.</u> Whammy is always based on noteboard beats.</p>
<a name="skip" />
EOT
);
    }


    function do_help() {
        // TODO
        exit;
    }

    
    function do_version() {
        // TODO
        exit;
    }

    
?>