<?php

function getRepoOrigin($repo) {
    $output = $repo->run('remote -v');
    $lines = explode("\n",$output);
    foreach($lines as $line) {
        $matches = array();            preg_match( '/^origin\s+([^ ]*)\s+\(fetch\)$/', $line, $matches);
        if ( count($matches) < 2 ) continue;
        $origin = trim($matches[1]);
        if ( strrpos($origin, '.git') == strlen($origin)-4) return $origin;
        return $origin . '.git';
    }
    return false;
}

// Notes
// git reset --hard 5979437e27bd47637c4b562b33e861ce32b6468b

