<?php
// file input
$dorkFile = __DIR__ . '/dork.txt';
$uaFile   = __DIR__ . '/ua.txt';

$dorks = [];
if (file_exists($dorkFile)) {
    $dorks = array_filter(array_map('trim', file($dorkFile)));
}

// load file UA
$uaList = [];
if (file_exists($uaFile)) {
    $uaList = array_filter(array_map('trim', file($uaFile)));
}

// fallback
if (!$uaList) {
    $uaList = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        'Mozilla/5.0 (X11; Linux x86_64)',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X)',
    ];
}

$results = [];
$delay   = 3;

// random UA 
function randUA($list) {
    return $list[array_rand($list)];
}

// curl fetch 
function fetch($url, $uaList) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, randUA($uaList));

    $out = curl_exec($ch);
    curl_close($ch);

    return $out;
}

function extractUrls($html) {
    if (!$html) return [];

    preg_match_all('/<a href="(https?:\/\/[^"]+)"/i', $html, $m);
    $out = [];

    foreach ($m[1] as $u) {
        if (strpos($u, 'bing.com') !== false) continue;
        $out[] = preg_replace('/&.*$/', '', $u);
    }

    return array_unique($out);
}

// handle post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $targets = [];

    if (!empty($_POST['single'])) {
        $targets[] = trim($_POST['single']);
    }

    if (!empty($_POST['mass'])) {
        foreach (explode("\n", $_POST['mass']) as $l) {
            $l = trim($l);
            if ($l) $targets[] = $l;
        }
    }

    $targets = array_unique($targets);

    foreach ($targets as $t) {

        // normalize target
        $t = preg_replace('#^https?://#', '', $t);
        $t = preg_replace('#/.*$#', '', $t);

        foreach ($dorks as $d) {

            $q   = urlencode("site:$t $d");
            $url = "https://www.bing.com/search?q={$q}";

            
            $html = fetch($url, $uaList);

            foreach (extractUrls($html) as $u) {
                $results[$u] = 1;
            }

            sleep($delay);
        }
    }
}
?>
