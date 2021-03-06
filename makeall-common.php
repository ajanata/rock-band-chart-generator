<?php

    define("CHARTVERSION", 5);
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
        fclose($cache);
        return $unserialized;
    }
    
    function saveCache($file, $array) {
        echo "Saving cache...\n";
        $cache = fopen($file, 'w');
        if ($cache) {
            fwrite($cache, serialize($array));
            fclose($cache);
        }
    }
    
    function index_header($fhand, $title) {
    	date_default_timezone_set('America/New_York');
    	
        fwrite($fhand, "<html>\n<head>\n<title>Blank Charts for Rock Band and Rock Band 2 $title</title>\n</head>\n");
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
<li>Keys charts aren't going to happen until I do an entire rewrite. This may or may not ever happen. :)</li>
<li>I'm not indicating unison bonuses on the solo charts. If you're pathing for Rock Band 3, use a co-op chart so you know when unison bonuses are. Again, this is unlikely to be addressed without an entire rewrite.</li>
<li><b>Rock Band 3</b> charts. At this time, only in instrument combinations I have been doing for the DLC. <strike>I may be able to add keys support relatively soon.</strike> I am likely <b>not</b> going to be doing any Pro support (on any instrument) in the near future. Neither am I likely to fix harmonies.</li>
<li>I may stop updating these charts at some point in the future. DarylZero has a <a href="http://site.tmullin.net:81/ipather/">nice chart program</a> that he's actively maintaining and adding RB3-specific features to. Already it handles pro drums, keys, and unison bonuses. Go check it out, it's really quite nice.</li>
<ul>
<li>I will update these at least through the end of March.</li>
<li>I have removed the donate link because of this. Thank you to those of you who have sent me money for this (about $150 overall), it has kept me motivated to actually do this, and it would force me to get caught up to date as soon as humanly possible. :)</li>
</ul>
</ul></p>
<p><a href="http://charts.ajanata.com/rb/">Rock Band 1/2/3/LEGO/Green Day and DLC charts</a> <a href="http://charts.ajanata.com/tbrb/">The Beatles: Rock Band and DLC charts</a></p>
<p>These charts are blank. They have not been verified against the game and may be faulty. If you see something horribly wrong please <a href="http://rockband.scorehero.com/forum/privmsg.php?mode=post&u=52545">send me a message</a> on ScoreHero. Relevant discussion threads for <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=4773">drums/drums+vocals</a>, <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=5062">guitar/bass/guitar+bass</a>, <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=7625">vocals</a>, <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=7626">vocaltar</a>, and <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=7627">full band</a>.</p>
<p>They are in alphabetical order by .mid file name (this normally doesn't mean anything, but "the" is often left out). Probably easier to find a song this way anyway.</p>
<p>Solo note counts and estimated upperbound Big Rock Ending bonuses listed above where the solo or ending ends. To the bottom right of each measure are numbers relating to that measure. Black is the measure score (no multiplier taken into account). Red is the cumulative score to that point (with multipliers) without solo bonuses. Green (on guitar parts only) is cumulative score to that point counting solo bonuses. Blue is the number of whammy beats (no early whammy taken into account) in that measure. Between groups of lines on multiple-instrument parts is the band measure score. This score currently does not take vocals into account, and assumes that every instrument is always at the maximum multiplier. I intend on making this better at some point, but even just having this makes pathing easier.</p>
<p>Above every drum fill is the amount of clock time since the last OD note. On Rock Band 1 for Xbox 360 and Playstation 3, there must be at least 2.4 seconds between the last OD note gaining one half of a bar and the beginning of a drum fill in order for the fill to show up. This value is approximately 5.39 seconds on Playstation 2. I do not know which value applies to Wii, but it is probably the latter. You can, of course, use early and late hitting of that OD note to influence this delay. The times listed are assuming the note is hit exactly on time. (Source: 360: ajanata, PS2: Kawigi and Shvegait.)</p>
<p>For Rock Band 2 (at least on 360/PS3), the delay time depends on the <b>current</b> scroll speed of the note chart. On 360, on expert, without Breakneck Speed, this is roughly 1.2 seconds. With Breakneck Speed, this is roughly 0.8 seconds. I do not know what the values are on the other difficulties. (Source: ajanata, elx, Kawigi. Somebody with the values for easy/medium/hard, please contact me so I may list them here.)</p>
<p><b><u>Lag settings affect this window.</u></b> If you have any lag setting other than 0/0, you may find that your window is considerably larger (but it will never be smaller).</p>
<p>Vocal activation zones have finally been figured out. Any gap between phrases which is at least 600 ms note to note has an activation zone. However, the activation zone is only visible from the end of the last note to the beginning of the next phrase marker, so you may have considerably less than 600 ms to activate in. Also, upon further examination of the game's configuration files, the 100 ms on either end of the activation zone may not be able to be used, further restricting the time available to activate in.</p>
<p>Overdrive phrase backgrounds extend the exact range specified in the .mid file. Sometimes this is significantly shorter than the length of a sustained note (see third note in <a href="/images/notecharts/contrib/ajanata/guitar/foreplaylongtime_guitar_expert_blank.png">Foreplay/Long Time</a> for example). Unison bonuses occur when at least two (or does it require all?) of guitar/bass/drums have a phrase that both begins and ends at the exact same time in the .mid</p>
<p>The .mid BEAT track is displayed on every chart. The game uses this to determine how long Overdrive lasts. A full bar of Overdrive always lasts for exactly 32 BEAT track beats. Most of the time this is 16, 32, or 64 noteboard beats, depending on tempo. Sometimes, it isn't (see the first break in Foreplay/Long Time for an example). I don't see the two events in the BEAT track doing different things in the gameplay (perhaps different stage lighting or something but nothing that matters for pathing), so I've drawn them all in the same color. If it isn't obvious, you want to look at the small red lines above every set of lines (this also makes a nice seperator for multi-instrument parts). <u>Note that this <b>DOES NOT</b> affect whammy rate, only usage rate.</u> Whammy is always based on noteboard beats.</p>
<a name="skip"></a>
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
