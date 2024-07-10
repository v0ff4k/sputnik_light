<?php

if (isset($_GET['get_demo_playlist'])) {
    $a = '{"items": [
                    {
                        "id": "68862011",
                        "title": "Прямой эфир",
                        "text": "Прямой эфир",
                        "img": "",
                        "mp3": "https://nfw.ria.ru/flv/audio.aspx?ID=68862011&type=mp3",
                        "duration": "",
                        "date": "",
                        "url": "",
                        "articleid": ""
                    },
                    
        {
            "id": "48809494",
            "title": "Главные темы часа. 15:00",
            "text": "Минобороны России показало видео разгрома украинской ДРГ, которая пыталась прорвать границу в районе села Сподарюшино Белгородской области. Средства ПВО уничтожили украинский беспилотник над Орловской областью. Об этом сообщили в Минобороны РФ. Один человек погиб в результате крушения военного самолёта в турецкой Конье. Об этом заявило министерство национальной обороны. По меньшей мере 60 человек погибли и 34 пострадали в Афганистане из-за непогоды за последние 23 дня. Об этом сообщает телеканал Tolo News со ссылкой на министерство по борьбе со стихийными бедствиями во временном правительстве Афганистана. Прокуратура Нидерландов рассчитывает на экстрадицию футболиста московского \"Спартака\" Квинси Промеса из ОАЭ в ближайшее время. Однако точные сроки процедуры назвать не может. Об этом заявили РИА Новости в пресс-службе отдела прокуратуры королевства по апелляциям. Сербская рок-группа \"Джепови\" выпустила клип в поддержку президента РФ Владимира Путина, снятый при содействии Российского центра науки и культуры \"Русский дом\" в Белграде. Об этом сообщило представительство Россотрудничества в Сербии.",
            "img": "https://cdnn21.img.ria.ru/images/07e8/03/0e/1932970262_0:3:1036:586_600x0_80_0_0_52065e2cb4aa3ffd72797a9c930b416c.jpg.webp",
            "mp3": "https://nfw.ria.ru/flv/file.aspx?type=mp3hi&ID=48809494",
            "duration": "216",
            "date": "15:00",
            "url": "https://radiosputnik.ru/20240314/1932970372.html",
            "articleid": "1932970372"
        },
    
        {
            "id": "8594669",
            "title": "Главные темы часа. 00:00",
            "text": "Грайворонский городской округ Белгородской области попал под обстрел, информирует Telegram-канал мэрии Белгорода. ПВО в Ростовской области не работала, сообщил замминистра региональной политики и массовых коммуникаций региона Сергей Тюрин, комментируя информацию о громких звуках в Волгодонске. Гендиректор международной медиагруппы \"Россия сегодня\", ведущий программы \"Вести недели\" Дмитрий Киселев проанонсировал большое интервью российского президента Владимира Путина телеканалу \"Россия 1\" и РИА Новости, где будут даны оценки новым угрозам Запада и расставлены точки над i Новых американских военных поставок Украине хватит на несколько недель, а удовлетворение долгосрочных потребностей по-прежнему требует решения конгресса о выделении дополнительных бюджетных средств. Об этом заявил официальный представитель Пентагона Патрик Райдер. Варшаве нужно \"больше Америки\", заявил польский президент Анджей Дуда во время встречи с президентом США Джо Байденом в Вашингтоне. Президент Турции Реджеп Тайип Эрдоган, комментируя конфликт на Украине, призвал избегать любых шагов, которые приведут к эскалации конфликтов в регионе и их распространению на НАТО. Мужчина захватил в заложники 18 пассажиров автобуса на автовокзале в Рио-де-Жанейро, в том числе детей, два человека пострадали, сообщил бразильский портал.",
            "img": "https://cdnn21.img.ria.ru/images/07e8/03/0d/1932616752_0:3:1036:586_600x0_80_0_0_cf480227a2ecfe0d79a3c710aafe5e43.jpg.webp",
            "mp3": "https://nfw.ria.ru/flv/file.aspx?type=mp3hi&ID=8594669",
            "duration": "215",
            "date": "00:00",
            "url": "https://radiosputnik.ru/20240313/1932616862.html",
            "articleid": "1932616862"
        },
    
                ]}';

    header('Content-Type: application/json');
    echo ''.$a;
    die();
}

if (isset($_GET['get_playlist'])) {
    $url = 'https://radiosputnik.ru/broadcasts/live/';
    // $url = 'http://127.0.0.1/rs/test.html';

    $context = stream_context_create(
        [
            'http' => [
                'method' => 'GET',
                'timeout' => 60,
                'header' => "User-Agent: App\r\n",
            ],
        ]
    );

    try {
        $r = file_get_contents($url, false, $context);

        $i = 'var playlist = {';
        $s = strpos($r, $i) + strlen($i);
        $e = strrpos($r, '</script><h1>');
        $data = substr($r, $s, $e - $s);
        $data = preg_replace('/}\s*\n*,\s*]\s*\n*}/', '}]}', $data);

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
    <title>radio Playlist</title>
<!--    <script src="//code.jquery.com/jquery-3.6.0.min.js"></script>-->
    <link rel="stylesheet" href="//stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <style>
        #playlist *{
            -webkit-transition: all .3s ease;
            -moz-transition: all .3s ease;
            -ms-transition: all .3s ease;
            -o-transition: all .3s ease;
            transition: all .3s ease;
        }
        .background{
            /*background-image: url("xyz.jpg");*/
            /*background-size: 0 0;*/
            background-size:cover;

            width: 100%;
            height: auto;

            -webkit-filter: blur(1px);
            -moz-filter: blur(1px);
            -o-filter: blur(1px);
            -ms-filter: blur(1px);
            filter: blur(1px);

            display: block;
            overflow: hidden;
            position: relative;
        }
        .card:hover .background{
            -webkit-filter: blur(5px);
            -moz-filter: blur(5px);
            -o-filter: blur(5px);
            -ms-filter: blur(5px);
            filter: blur(5px);
            opacity: 0.3;
        }
        .cover-layer{
            bottom: 0;
            left: 0;
            position: absolute;
            right: 0;
            top: 0;
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

    </style>
</head>
<body>
    <div class="container">
        <button id="update-playlist">Update playlist</button>
        <div id="playlist" class="row mt-4"></div>
    </div>

    <div id="audio-player" class="fixed-bottom bg-dark p-2 hidden">
        <audio id="audio-player-audio" src="" controls preload="none"></audio>
    </div>

    <script src="//code.jquery.com/jquery-3.5.1.min.js"></script>
<!--    <script src="playlist.php"></script>-->
    <script>
        $(document).ready(function() {
            $('#update-playlist').click(function() {
                $.getJSON('index.php?get_playlist', function(data) {
                    var html = '';
                    for (var i = 0; i < data.items.length; i++) {
                        var item = data.items[i];
                        html += '<div class="col-md-6 mb-4">';
                        html += '<div class="card">';
                        html += '<img src="' + item.img + '" alt="' + item.title + '" class="card-bottom background">';
                        html += '<div class="card-body cover-layer">';
                        html += '<h6 class="card-title">' + item.title + ' <small> длительность: ' + convertSecondsToHHMMSS(item.duration) + ' | время: ' + item.date + '</small></h6>';
                        html += '<p class="card-text">' + item.text + '</p>';

                        // html += '<span data-src="' + item.mp3 + '" class="play btn btn-info">[play]</span>&nbsp;';
                        // old play per player
                         html += '<audio controls preload="none" class="audio-pLayer"><source src="' + item.mp3 + '" type="audio/mpeg">Not support the audio tag.</audio>';
                        // html += '<video preload="none" controls="" style="height: 40px; width: 66%;"><source src="' + item.mp3 + '" type="audio/mpeg"></video>';

                        html += '<a href="' + item.url + '" class="btn btn-primary">&gt;&gt;</a>';
                        html += '</div>';
                        html += '</div>';
                        html += '</div>';
                    }
                    $('#playlist').html(html);

                    // $(".audio-pLayer").volume(0.5);
                    // $(".audio-pLayer").each(function(){
                    //     $(this).volume = 0.3;
                    // });

                    var a = document.getElementsByTagName("audio");
                    for (var t = 0; t < a.length; t++) {
                        a[t].volume = 0.3;
                    }
                });
            });


            // Loop through each sub div element in the playlist  div#playlist div div.card div span.play
            $('.play').each(function() {
                var audioSource = $(this).attr('data-src');

                // Set the source for the audio player
                $('#audio-player-audio').attr('src', audioSource);

                // Set the speed for the audio source and remember it
                $(this).find('data-src').each(function() {
                    var speed = localStorage.getItem('speed_' + audioSource);

                    if (speed) {
                        $(this).attr('data-speed', speed);
                    }
                });

                // Play the audio when the sub div is clicked
                $(this).on('click', function() {
                    $('#playlist span.play').text('[play]');
                    $(this).text('playing...');

                    var audioSpeed = $(this).attr('data-speed');

                    // Set the speed for the audio player
                    $('#audio-player-audio').attr('speed', audioSpeed);

                    // Play the audio
                    $('#audio-player-audio')[0].play();
                });
            });
/*
            // Add a change event listener to the audio player speed slider
            $('#audio-player audio').on('change', function() {
                var audioSource = $(this).attr('src');
                var audioSpeed = $(this).attr('speed');

                // Save the speed for the audio source in localStorage
                localStorage.setItem('speed_' + audioSource, audioSpeed);
            });
*/

        });

        function convertSecondsToHHMMSS(seconds) {
            let hours = Math.floor(seconds / 3600);
            let minutes = Math.floor((seconds % 3600) / 60);
            let remainingSeconds = seconds % 60;

            // Leading zeros if needed
            hours = hours < 10 ? `0${hours}` : hours;
            minutes = minutes < 10 ? `0${minutes}` : minutes;
            remainingSeconds = remainingSeconds < 10 ? `0${remainingSeconds}` : remainingSeconds;

            return `${hours}:${minutes}:${remainingSeconds}`;
        }

    </script>
</body>
</html>
