<?php
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
    $context = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 60, 'header' => "User-Agent: App\r\n"]]);
    try {
        $r = file_get_contents($url, false, $context);
        $i = 'var playlist = {';
        $s = strpos($r, $i) + strlen($i);
        $e = strrpos($r, '</script><h1>');
        $data = substr($r, $s, $e - $s);
        $data = preg_replace('/}\s*,\s*]\s*}/', '}]}', $data);
        header('Content-Type: application/json');
        echo '{'.$data;
        die();
    } catch (\Exception $ex) {
        echo $ex->getMessage();
        die('0');
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Radio Playlist</title>
    <link rel="stylesheet" href="//stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <style>
        .card img { width: 100%; height: auto; }
        .background {
            background-size: cover;
            width: 100%;
            height: 200px; /* Фиксированная высота */
            filter: blur(1px);
            display: block;
            overflow: hidden;
            position: relative;
        }
        .card:hover .background {
            filter: blur(5px);
            opacity: 0.3;
        }
        .cover-layer {
            bottom: 0;
            left: 0;
            position: absolute;
            right: 0;
            top: 0;
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        .card-text {
            max-height: 175px;
            overflow: auto;
        }
        audio {
            width: 90%;
        }
        .hidden {
            display: none;
        }
        .card-body {
            padding: 10px;
        }
        #controls {
            margin-top: 10px;
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
    <div id="audio-player" class="fixed-bottom bg-dark p-2 hidden">
        <div id="controls">
            <label for="speed-slider">Скорость:</label>
            <input type="range" id="speed-slider" min="0.5" max="2" step="0.1" value="1">
            <label for="volume-slider">Громкость:</label>
            <input type="range" id="volume-slider" min="0" max="1" step="0.1" value="1">
        </div>
    </div>
    <script src="//code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/howler/2.2.3/howler.core.min.js"></script>
    <script>
        $(document).ready(function() {
            let currentTrack = null;
            let sound = null;

            $('#update-playlist').click(function() {
                $.getJSON('index.php?get_playlist', function(data) {
                    let html = '';
                    data.items.forEach(item => {
                        html += `
                            <div class="col-md-6 col-sm-12 mb-4">
                                <div class="card">
                                    <img src="${item.img}" alt="${item.title}" class="card-img-top background">
                                    <div class="card-body cover-layer">
                                        <h6 class="card-title">${item.title} <small>${convertSecondsToHHMMSS(item.duration)} | ${item.date}</small></h6>
                                        <p class="card-text">${item.text}</p>
                                        <button class="play btn btn-info" data-src="${item.mp3}">▶️</button>
                                        <a href="${item.url}" class="btn btn-primary">🔗</a>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    $('#playlist').html(html);

                    $('.play').off('click').on('click', function() {
                        const trackUrl = $(this).data('src');

                        // Остановить предыдущий трек, если он воспроизводится
                        if (sound && !sound.isPaused()) {
                            sound.stop();
                        }

                        // Установить новый трек
                        sound = new Howl({
                            src: [trackUrl],
                            volume: parseFloat($('#volume-slider').val()),
                            rate: parseFloat($('#speed-slider').val())
                        });

                        // Воспроизвести трек
                        sound.play();

                        // Обновить текст кнопки
                        $('.play').text('▶️');
                        $(this).text('⏸️');
                        $('#audio-player').removeClass('hidden');
                    });
                });
            });

            // Обработка слайдеров для скорости и громкости
            $('#speed-slider').on('input', function() {
                const speed = parseFloat($(this).val());
                if (sound) {
                    sound.rate(speed);
                }
            });

            $('#volume-slider').on('input', function() {
                const volume = parseFloat($(this).val());
                if (sound) {
                    sound.volume(volume);
                }
            });

            // Обработчик окончания воспроизведения
            if (sound) {
                sound.on('end', function() {
                    $('.play').text('▶️');
                    currentTrack = null;
                    $('#audio-player').addClass('hidden');
                });
            }
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
