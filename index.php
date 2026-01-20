<?php

$dorkFile = __DIR__ . "/dork.txt";
$UserAgent   = __DIR__ . "/ua.txt";

$dorks = file_exists($dorkFile)
    ? array_filter(array_map("trim", file($dorkFile)))
    : [];

$UA_LIST = file_exists($UserAgent)
    ? array_filter(array_map("trim", file($UserAgent)))
    : [];

if (!$UA_LIST) {
    $UA_LIST = [
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0",
        "Mozilla/5.0 (X11; Linux x86_64) Firefox/118.0",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Safari/605.1.15",
    ];
}

$results = [];
$delay = 3;

function randUA($UA_LIST) {
    return $UA_LIST[array_rand($UA_LIST)];}

function fetch($url, $UA_LIST) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => randUA($UA_LIST),
    ]);
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

function extractUrls($html) {
    $urls = [];
    if (!$html) return $urls;

    preg_match_all('/<a href="(https?:\/\/[^"]+)"/i', $html, $m);
    foreach ($m[1] as $u) {
        if (strpos($u, 'bing.com') !== false) continue;
        $urls[] = preg_replace('/&.*$/', '', $u);
    }
    return array_unique($urls);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $single = trim($_POST["single"] ?? "");
    $mass   = trim($_POST["mass"] ?? "");

    $targets = [];

    if ($single) $targets[] = $single;
    if ($mass) {
        foreach (explode("\n", $mass) as $l) {
            $l = trim($l);
            if ($l) $targets[] = $l;
        }
    }

    $targets = array_unique($targets);

    foreach ($targets as $t) {
        $t = preg_replace('#^https?://#', '', $t);
        $t = preg_replace('#/.*$#', '', $t);

        foreach ($dorks as $d) {
            $q = urlencode("site:$t $d");
            $html = fetch("https://www.bing.com/search?q=", $UA_LIST);
            foreach (extractUrls($html) as $u) {
                $results[$u] = true;
            }
            sleep($delay);
        }
    }
}
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Dorking Tools Made by AnotherSecurity</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light text-dark">

<div class="container py-4">

    <h3 class="text-center mb-4 fw-bold">Dorking Tools Made by AnotherSecurity</h3>

    <form method="post" class="card shadow-sm mb-4">
        <div class="card-body">

            <div class="mb-3">
                <label class="form-label">Single Target</label>
                <input type="text" name="single" class="form-control" placeholder="example.com">
            </div>

            <div class="mb-3">
                <label class="form-label">Mass Target</label>
                <textarea name="mass" class="form-control" rows="4"></textarea>
            </div>

            <button class="btn btn-dark w-100 fw-bold">
                RUN DORKING
            </button>

        </div>
    </form>

    <?php if ($results): ?>
    <div class="card shadow-sm">
        <div class="card-header fw-bold">
            Log Output (<?= count($results) ?> URLs)
        </div>
        <div class="card-body">
            <pre class="small mb-0"><?php
                foreach (array_keys($results) as $u) {
                    echo htmlspecialchars($u) . "\n";
                }
            ?></pre>
        </div>
    </div>
    <?php endif; ?>

</div>

</body>
</html>
