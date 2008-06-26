<?php

    define("CHARTVERSION", 1);
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
    
?>