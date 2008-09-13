<?php

    define("CHARTVERSION", 4);
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
<li><b>Rock Band 2 charts!</b> Charts at release are good, yes? Thankfully this required very little code changes on my part -- even drum solos worked, but required tweaking for the proper number of notes in the solo since drum has a different definition of "note".</li>
<li>Handles time signatures not x/4 properly. Many, many thanks to Kawigi for help with figuring out what was going on. Note that the beat lines are only drawn for quarter notes, as is done in the game. I believe there may be issues if there are ever vocal or guitar part notes going into or out of such a measure, but I was unable to find such a case in my (admittingly limited) testing.</li>
<li>Tempo is out to more decimal places on the wider charts. This is due to storing it internally as a floating-point, which makes things MUCH better for several other things, including the delay time above drum fills.</li>
<li>Every image has been re-created from the source .mid file. This hasn't happened for a few months; the two changes listed above affect too many charts to get away with not fixing all of them, and there were a few minor things that could stand for being corrected anyway.</li>
</ul></p>
<p>These charts are blank. They have not been verified against the game and may be faulty. If you see something horribly wrong please <a href="http://rockband.scorehero.com/forum/privmsg.php?mode=post&u=52545">send me a message</a> on ScoreHero. Relevant discussion threads for <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=4773">drums/drums+vocals</a>, <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=5062">guitar/bass/guitar+bass</a>, <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=7625">vocals</a>, <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=7626">vocaltar</a>, and <a href="http://rockband.scorehero.com/forum/viewtopic.php?t=7627">full band</a>.</p>
<p>They are in alphabetical order by .mid file name (this normally doesn't mean anything, but "the" is often left out). Probably easier to find a song this way anyway.</p>
<p>Solo note counts and estimated upperbound Big Rock Ending bonuses listed above where the solo or ending ends. To the bottom right of each measure are numbers relating to that measure. Black is the measure score (no multiplier taken into account). Red is the cumulative score to that point (with multipliers) without solo bonuses. Green (on guitar parts only) is cumulative score to that point counting solo bonuses. Blue is the number of whammy beats (no early whammy taken into account) in that measure. Between groups of lines on multiple-instrument parts is the band measure score. This score currently does not take vocals into account, and assumes that every instrument is always at the maximum multiplier. I intend on making this better at some point, but even just having this makes pathing easier.</p>
<p>Above every drum fill is the amount of clock time since the last OD note. On Xbox 360 and Playstation 3, there must be at least approximately 2.43 seconds between the last OD note gaining one half of a bar and the beginning of a drum fill in order for the fill to show up. This value is approximately 5.39 seconds on Playstation 2. I do not know which value applies to Wii, but it is probably the latter. You can, of course, use early and late hitting of that OD note to influence this delay. The times listed are assuming the note is hit exactly on time. These used to be rather wrong on some songs, but the change for dealing with BPM may have fixed it -- I haven't checked. (Source: 360: ajanata, PS2: Kawigi and Shvegait.)</p>
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