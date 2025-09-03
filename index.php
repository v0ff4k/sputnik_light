<?php

$ua = 'Mozilla/5.0 (compatible; RS-Proxy/1.0)';
if (isset($_GET['get_demo_playlist'])) {
    $a = '{"items": [
        {"id": "68862011", "title": "Прямой эфир", "text": "Прямой эфир", "img": "", "mp3": "https://nfw.ria.ru/flv/audio.aspx?ID=68862011&type=mp3", "duration": "", "date": "", "url": "", "articleid": ""},
        {"id": "48809494", "title": "Главные темы часа. 15:00", "text": "Минобороны...", "img": "https://cdnn21.img.ria.ru/images/07e8/03/0e/1932970262_0:3:1036:586_600x0_80_0_0_52065e2cb4aa3ffd72797a9c930b416c.jpg.webp", "mp3": "https://nfw.ria.ru/flv/file.aspx?type=mp3hi&ID=48809494", "duration": "216", "date": "15:00", "url": "https://radiosputnik.ru/20240314/1932970372.html", "articleid": "1932970372"},
        {"id": "8594669", "title": "Главные темы часа. 00:00", "text": "Грайворонский...", "img": "https://cdnn21.img.ria.ru/images/07e8/03/0d/1932616752_0:3:1036:586_600x0_80_0_0_cf480227a2ecfe0d79a3c710aafe5e43.jpg.webp", "mp3": "https://nfw.ria.ru/flv/file.aspx?type=mp3hi&ID=8594669", "duration": "215", "date": "00:00", "url": "https://radiosputnik.ru/20240313/1932616862.html", "articleid": "1932616862"}
    ]}';
    header('Content-Type: application/json');
    echo $a;
    die();
}
if (isset($_GET['get_playlist'])) {
    $url = 'https://radiosputnik.ru/broadcasts/live/';
    $context = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 60, 'header' => "User-Agent: ".$ua."\r\n"]]);
    try {
        $r = file_get_contents($url, false, $context);
        $i = 'var playlist = {';
        $s = strpos($r, $i) + strlen($i);
        $e = strrpos($r, '</script><h1>');
        $data = substr($r, $s, $e - $s);
        $data = preg_replace('/}\s*,\s*]\s*}/', '}]}', $data);
        $data = str_replace('\ ', ' ', $data);// some bad stuff.
        $data = str_replace('1\\', '1\\\\', $data);// some bad stuff.
        header('Content-Type: application/json');
        echo '{'.$data;
        die();
    } catch (\Exception $ex) {
        echo $ex->getMessage();
        die('0');
    }
}
// Stream proxy with Range support to bypass mobile/cors/referrer restrictions
if (isset($_GET['stream']) || isset($_GET['s'])) {
    $sourceUrl = isset($_GET['s']) ? $_GET['s'] : $_GET['stream'];
    if (isset($_GET['s'])) {
        // base64url decode
        $b64 = strtr($sourceUrl, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad) { $b64 .= str_repeat('=', 4 - $pad); }
        $decodedUrl = base64_decode($b64, true);
    } else {
        $decodedUrl = urldecode($sourceUrl);
    }
    $parts = parse_url($decodedUrl);

    // Basic SSRF protection and allowlist
    $allowedHosts = ['nfw.ria.ru'];
    if (!$parts || !isset($parts['scheme']) || !in_array($parts['scheme'], ['http', 'https'], true)) {
        http_response_code(400);
        die('Bad URL');
    }
    if (!isset($parts['host']) || !in_array($parts['host'], $allowedHosts, true)) {
        http_response_code(403);
        die('Forbidden host');
    }

    $rangeHeader = isset($_SERVER['HTTP_RANGE']) ? $_SERVER['HTTP_RANGE'] : null;

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $decodedUrl);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, $ua);

        $forwardHeaders = [
            'Accept: */*',
        ];
        if ($rangeHeader) {
            $forwardHeaders[] = 'Range: ' . $rangeHeader;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $forwardHeaders);

        $headersSent = false;
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $headerLine) use (&$headersSent) {
            $len = strlen($headerLine);
            $line = trim($headerLine);
            if ($line === '') { return $len; }
            $lower = strtolower($line);
            if (strpos($lower, 'http/') === 0) {
                if (preg_match('~\s(\d{3})\s~', $line, $m)) {
                    http_response_code((int)$m[1]);
                }
                // defaults
                header('Content-Type: audio/mpeg');
                header('Accept-Ranges: bytes');
            } elseif (
                strpos($lower, 'content-type:') === 0 ||
                strpos($lower, 'content-length:') === 0 ||
                strpos($lower, 'content-range:') === 0 ||
                strpos($lower, 'accept-ranges:') === 0 ||
                strpos($lower, 'cache-control:') === 0 ||
                strpos($lower, 'expires:') === 0 ||
                strpos($lower, 'etag:') === 0
            ) {
                header($line, true);
            }
            return $len;
        });

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($curl, $data) use (&$headersSent) {
            echo $data;
            flush();
            return strlen($data);
        });

        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_exec($ch);
        curl_close($ch);
        die();
    }

    // Fallback without cURL: stream via fopen with Range
    $headers = [
        'http' => [
            'method' => 'GET',
            'timeout' => 60,
            'header' => "User-Agent: RS-Proxy/1.0\r\nAccept: */*\r\n" . ($rangeHeader ? "Range: $rangeHeader\r\n" : ''),
        ],
    ];
    $ctx = stream_context_create($headers);
    $fp = @fopen($decodedUrl, 'rb', false, $ctx);
    if (!$fp) {
        http_response_code(502);
        die('Bad Gateway');
    }
    // Try to read response headers from $http_response_header
    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $hline) {
            $lower = strtolower($hline);
            if (strpos($lower, 'http/') === 0) {
                if (preg_match('~\s(\d{3})\s~', $hline, $m)) {
                    http_response_code((int)$m[1]);
                }
            }
            if (
                strpos($lower, 'content-type:') === 0 ||
                strpos($lower, 'content-length:') === 0 ||
                strpos($lower, 'content-range:') === 0 ||
                strpos($lower, 'accept-ranges:') === 0 ||
                strpos($lower, 'cache-control:') === 0 ||
                strpos($lower, 'expires:') === 0 ||
                strpos($lower, 'etag:') === 0
            ) {
                header($hline, true);
            }
        }
    }
    header('Content-Type: audio/mpeg');
    header('Accept-Ranges: bytes');
    while (!feof($fp)) {
        $buf = fread($fp, 8192);
        echo $buf;
        flush();
    }
    fclose($fp);
    die();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>radio Playlist</title>
    <link rel="stylesheet" href="//stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(to bottom, #c5d8b3 0%,#b0c49f 100%);
            background-repeat: no-repeat;
            background-size: cover; /* This will stretch the gradient to fill the entire screen */
        }
        #playlist * {
            -webkit-transition: all .3s ease;
            -moz-transition: all .3s ease;
            -ms-transition: all .3s ease;
            -o-transition: all .3s ease;
            transition: all .3s ease;
        }
        .background {
            background-size: cover;
            width: 100%;
            height: 200px; /* Фиксированная высота */
            -webkit-filter: blur(1px);
            -moz-filter: blur(1px);
            -o-filter: blur(1px);
            -ms-filter: blur(1px);
            filter: blur(1px);
            display: block;
            overflow: hidden;
            position: relative;
        }
        .card {
            background-color: #ccc;
            border-radius: 0 .25rem 0 0.3rem;
        }
        .card:hover .background {
            filter: blur(5px);
            opacity: 0.3;
        }
        .card img { width: 100%; height: auto; }
        .card-title { margin: -11px 0 2rem 0;
            background: linear-gradient(to bottom, rgba(255, 255, 255, 0.5) 0%,rgba(255,255,255,0.3) 80%,rgba(255,255,255,0) 100%);
            padding: 0 5px 2px;
            border-top: 1px solid silver;
            border-radius: 0 0 5px 10px;
        }
        .card-title small { float: right; clear: both; text-shadow: none !important; }
        .time_duration,
        .time_start { font-family: Georgia, Times, "Times New Roman", serif}
        .time_start { font-weight: bold; }
        .cover-layer {
            bottom: 0;
            left: 0;
            position: absolute;
            right: 0;
            top: 0;
            /*color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);*/
        }
        .card-text {
            min-height: 175px;
            /*max-height: 180px;*/
            overflow: auto;
            clear: both;
            color: rgba(1, 1, 1, 0.3);
        }
        .card:hover .card-text {
            color: #333;
        }
        .upper-footer {
            min-height: 35px;
            max-height: 35px;
            overflow: hidden;
            clear: both;
        }
        @media (min-width: 520px) {
            .upper-footer {
                margin-top: auto !important;
                padding: 5px 10px;
            }
        }
        @media (min-width: 450px) {
            .upper-footer {
                margin-top: -20px !important;
                padding: 3px 10px;
            }
        }
        @media (min-width: 470px) {
            .upper-footer {
                margin-top: -16px !important;
                padding: 3px 10px;
            }
        }
        audio {
            width: 90%;
        }
        div#audio-player {
            background:  linear-gradient(to bottom, #959595 0%,#0d0d0d 46%,#010101 50%,#0a0a0a 53%,#4e4e4e 76%,#383838 87%,#1b1b1b 100%);
            color: #eee;
            -webkit-transition: all .9s ease;
            -moz-transition: all .9s ease;
            -ms-transition: all .9s ease;
            -o-transition: all .9s ease;
            transition: all .9s ease;
            opacity: 1;
            border-radius: 0;
        }
        div#audio-player #controls {
            transition: all .3s ease;
            color: #333;
            height: 20px;
            margin-bottom: -30px;
            /*margin-bottom: -9rem;*/
        }
        div#audio-player #controls > * {
            opacity: 0;
        }
        div#audio-player:hover{
            border-radius: 10px 10px 0 0;
        }
        div#audio-player:hover #controls > * {
            opacity: 1;
        }
        div#audio-player:hover #controls{
            color: #eee;
            height: auto;
            margin-bottom: 0;
            opacity: 0.9;
        }
        div#audio-player audio {
            width: 100%;
        }
        .hidden {
            display: none;
        }

        .card-body {
            padding: 10px;
        }
        .card-body .btn {
            font-size: 1.2rem;
            padding: .1rem .4rem;
        }
        .card-body .btn.play {
            font-size: 1.5rem;
        }
        #controls {
            margin-top: 10px;
            color: #eee;
        }
        #speed-slider, #volume-slider {
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container">
        <button id="update-playlist" class="btn btn-primary mb-4">🔄</button>
        <div id="playlist" class="row"></div>
    </div>
    <div id="audio-player" class="fixed-bottom bg-dark-grad p-2 hidden container">
        <div id="controls">
            <label for="speed-slider">Скорость:</label>
            <input type="range" id="speed-slider" min="0.5" max="2" step="0.1" value="1">
            <label for="volume-slider">Громкость:</label>
            <input type="range" id="volume-slider" min="0" max="1.3" step="0.1" value="0.3">
        </div>
        <audio id="global-audio" src="" controls preload="none" playsinline webkit-playsinline x5-playsinline></audio>
    </div>
    <script src="//code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        $(document).ready(function() {
            let currentTrack = null;
            let playbackRate = 1;
            let volume = 0.3;

            // инициализация громкости только для конкретного элемента
            const audioEl = document.getElementById('global-audio');
            audioEl.volume = volume;
            audioEl.preload = 'none';

            $('#update-playlist').click(function() {
                $.getJSON('index.php?get_playlist', function(data) {
                    let html = '';
                    function b64url(u){
                        return btoa(u).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');
                    }
                    data.items.forEach(item => {
                        const src = `index.php?s=${b64url(item.mp3)}`;
                        html += `
                            <div class="col-xs-12 col-md-6  col-md-12 col-lg-6 col-xl-6 mb-4">
                                <div class="card">
                                    <img src="${item.img}" alt="${item.title}" class="card-img-top background">
                                    <div class="card-body cover-layer d-flex flex-column">
                                        <h6 class="card-title">${item.title} <small><span class="time_duration">длительность ${convertSecondsToHHMMSS(item.duration)}</span> | <span class="time_start">время ${item.date}</span></small></h6>
                                        <p class="card-text">${item.text}</p>
                                        <div class="card-footer upper-footer">
                                        <button class="play btn btn-info" data-src="${src}">▶️</button>
                                        <a href="${item.url}" class="btn btn-primary">🔗</a>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        `;
                    });
                    $('#playlist').html(html);

                    $('.play').off('click').on('click', function() {
                        const trackUrl = $(this).data('src');

                        // Остановить предыдущий трек, если он воспроизводится
                        if ($('#global-audio')[0].paused === false) {
                            $('#global-audio')[0].pause();
                            $('#global-audio')[0].currentTime = 0;
                            //$('#global-audio')[0].playbackRate = playbackRate;
                            //$('#global-audio')[0].volume = volume;
                        }

                        // Установить новый трек
                        audioEl.src = trackUrl;
                        audioEl.load();
                        // В мобильных браузерах автоплей возможен только после явного user gesture
                        audioEl.playbackRate = playbackRate;
                        audioEl.volume = volume;
                        const playPromise = audioEl.play();
                        if (playPromise !== undefined) {
                            playPromise.catch(() => {/* ignore, UI already requires gesture */});
                        }


                        // Обновить текст кнопки
                        $('.play').text('▶️');
                        $(this).text('⏸️');
                        $('#audio-player').removeClass('hidden');
                    });
                });
            });

            audioEl.addEventListener('ended', function() {
                $('.play').text('▶️');
                currentTrack = null;
                $('#audio-player').addClass('hidden');

                // Сохраняем скорость и громкость текущего трека перед переключением
                playbackRate = audioEl.playbackRate;
                volume = audioEl.volume;
            });

            audioEl.addEventListener('error', function(event) {
                alert('Ошибка при воспроизведении аудиофайла.');
            });

            // Обработка слайдеров для скорости и громкости
            $('#speed-slider').on('input', function() {
                playbackRate = parseFloat($(this).val());
                audioEl.playbackRate = playbackRate;
            });

            $('#volume-slider').on('input', function() {
                volume = parseFloat($(this).val());
                audioEl.volume = volume;
            });
        });

        function convertSecondsToHHMMSS(seconds) {
            let hours = Math.floor(seconds / 3600);
            let minutes = Math.floor((seconds % 3600) / 60);
            let remainingSeconds = seconds % 60;
            hours = hours < 10 ? `0${hours}` : hours;
            minutes = minutes < 10 ? `0${minutes}` : minutes;
            remainingSeconds = remainingSeconds < 10 ? `0${remainingSeconds}` : remainingSeconds;
            return `${hours}:${minutes}:${remainingSeconds}`;
        }
    </script>
</body>
</html>
