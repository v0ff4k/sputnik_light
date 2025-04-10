<?php
// Sputnik Light Player - All-in-one PHP file
// Based on https://github.com/v0ff4k/sputnik_light/blob/main/index.php

// Keep the original PHP logic for fetching and parsing the playlist
$url = "https://sputnikradio.com/export/playlist/playlist_sputnik.html";
$html = @file_get_contents($url);

// Initialize playlist array
$playlist = [];

// Use the original string-based parsing approach
if ($html) {
    // Find the table content
    $start = strpos($html, '<table');
    $end = strpos($html, '</table>', $start);
    
    if ($start !== false && $end !== false) {
        $tableContent = substr($html, $start, $end - $start + 8);
        
        // Extract rows
        $rowStart = 0;
        $index = 1;
        
        while (($rowStart = strpos($tableContent, '<tr', $rowStart)) !== false) {
            $rowEnd = strpos($tableContent, '</tr>', $rowStart);
            if ($rowEnd === false) break;
            
            $row = substr($tableContent, $rowStart, $rowEnd - $rowStart + 5);
            $rowStart = $rowEnd + 5;
            
            // Skip header row
            if ($index == 1) {
                $index++;
                continue;
            }
            
            // Extract cells
            $cells = [];
            $cellStart = 0;
            
            while (($cellStart = strpos($row, '<td', $cellStart)) !== false) {
                $cellEnd = strpos($row, '</td>', $cellStart);
                if ($cellEnd === false) break;
                
                $cell = substr($row, $cellStart, $cellEnd - $cellStart + 5);
                $cellStart = $cellEnd + 5;
                
                // Extract content between tags
                $content = preg_replace('/<[^>]+>/', '', $cell);
                $cells[] = trim($content);
            }
            
            // Extract URL from the last cell if it contains an anchor
            $url = '';
            if (preg_match('/<a[^>]+href=["\'](.*?)["\'][^>]*>/i', $row, $matches)) {
                $url = $matches[1];
            }
            
            // Add to playlist if we have enough cells
            if (count($cells) >= 3) {
                $playlist[] = [
                    'id' => $index,
                    'title' => $cells[0],
                    'description' => $cells[1],
                    'duration' => $cells[2],
                    'url' => $url,
                    // Add HLS version of the stream URL if available
                    'hls_url' => str_replace('icecast-rian.cdnvideo.ru', 'icecast-rian.cdnvideo.ru/hls', $url)
                ];
            }
            
            $index++;
        }
    }
}

// If no items were found, create a sample playlist
if (empty($playlist)) {
    $playlist = [
        [
            'id' => 1,
            'title' => "Sputnik News",
            'description' => "Latest news from around the world",
            'duration' => "24:00",
            'url' => "https://icecast-rian.cdnvideo.ru/voiceeng",
            'hls_url' => "https://icecast-rian.cdnvideo.ru/hls/voiceeng/playlist.m3u8"
        ],
        [
            'id' => 2,
            'title' => "Sputnik Music",
            'description' => "Best music selection",
            'duration' => "60:00",
            'url' => "https://icecast-rian.cdnvideo.ru/voicerus",
            'hls_url' => "https://icecast-rian.cdnvideo.ru/hls/voicerus/playlist.m3u8"
        ],
        [
            'id' => 3,
            'title' => "Sputnik Talk",
            'description' => "Interesting discussions and interviews",
            'duration' => "45:00",
            'url' => "https://icecast-rian.cdnvideo.ru/cultrus",
            'hls_url' => "https://icecast-rian.cdnvideo.ru/hls/cultrus/playlist.m3u8"
        ]
    ];
}

// Convert playlist to JSON for JavaScript
$playlistJson = json_encode($playlist);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sputnik Light Player</title>
    <!-- Add HLS.js for better mobile compatibility -->
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <style>
        /* Modern, lightweight CSS without Bootstrap or jQuery dependencies */
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --background-color: #f5f5f5;
            --card-background: #ffffff;
            --text-color: #333333;
            --border-color: #dddddd;
            --hover-color: #e3f2fd;
            --active-color: #bbdefb;
            --progress-color: #2196f3;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background-color: var(--card-background);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            margin-bottom: 15px;
            color: var(--primary-color);
        }

        .player-controls {
            margin-top: 15px;
        }

        #now-playing {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        #current-track-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        #current-track-title {
            font-size: 1.2rem;
            margin-bottom: 5px;
        }

        #current-track-description {
            font-size: 0.9rem;
            color: #666;
        }

        .audio-controls {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .control-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 15px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .control-btn:hover {
            background-color: var(--secondary-color);
        }

        .control-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .volume-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        #volume-slider {
            width: 100px;
        }

        .progress-container {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-grow: 1;
        }

        .progress-bar-container {
            flex-grow: 1;
            height: 8px;
            background-color: var(--border-color);
            border-radius: 4px;
            overflow: hidden;
            cursor: pointer;
        }

        #progress-bar {
            height: 100%;
            width: 0;
            background-color: var(--progress-color);
            border-radius: 4px;
        }

        main {
            background-color: var(--card-background);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .playlist-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .playlist-controls button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 15px;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .playlist-controls button:hover {
            background-color: var(--secondary-color);
        }

        .playlist-filter input {
            padding: 8px 15px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            width: 250px;
        }

        #playlist {
            list-style: none;
        }

        .playlist-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .playlist-item:hover {
            background-color: var(--hover-color);
        }

        .playlist-item.active {
            background-color: var(--active-color);
        }

        .playlist-item-info {
            flex-grow: 1;
        }

        .playlist-item-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .playlist-item-description {
            font-size: 0.9rem;
            color: #666;
        }

        .playlist-item-duration {
            font-size: 0.9rem;
            color: #666;
            margin-left: 15px;
        }

        footer {
            margin-top: 20px;
            text-align: center;
            color: #666;
            font-size: 0.9rem;
        }

        /* Status indicator */
        #player-status {
            margin-top: 10px;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9rem;
            display: none;
        }

        #player-status.error {
            background-color: #ffebee;
            color: #c62828;
            display: block;
        }

        #player-status.success {
            background-color: #e8f5e9;
            color: #2e7d32;
            display: block;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            #current-track-info {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .audio-controls {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .progress-container {
                width: 100%;
            }
            
            .playlist-controls {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .playlist-filter input {
                width: 100%;
            }
            
            .playlist-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .playlist-item-duration {
                margin-left: 0;
                margin-top: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Sputnik Light Player</h1>
            <div class="player-controls">
                <div id="now-playing">
                    <div id="current-track-info">
                        <div>
                            <h3 id="current-track-title">Select a track to play</h3>
                            <p id="current-track-description"></p>
                        </div>
                    </div>
                    <div class="audio-controls">
                        <button id="play-pause-btn" class="control-btn" disabled>Play</button>
                        <button id="stop-btn" class="control-btn" disabled>Stop</button>
                        <div class="volume-container">
                            <button id="mute-btn" class="control-btn" disabled>Mute</button>
                            <input type="range" id="volume-slider" min="0" max="1" step="0.1" value="0.7">
                        </div>
                        <div class="progress-container">
                            <span id="current-time">00:00</span>
                            <div class="progress-bar-container">
                                <div id="progress-bar"></div>
                            </div>
                            <span id="duration">00:00</span>
                        </div>
                    </div>
                    <div id="player-status"></div>
                </div>
            </div>
        </header>
        
        <main>
            <div class="playlist-controls">
                <button id="play-all-btn">Play All</button>
                <button id="refresh-btn">Refresh Playlist</button>
                <div class="playlist-filter">
                    <input type="text" id="search-input" placeholder="Search tracks...">
                </div>
            </div>
            
            <div id="playlist-container">
                <ul id="playlist"></ul>
            </div>
        </main>
        
        <footer>
            <p>Sputnik Light Player - Cross-browser and mobile ready</p>
        </footer>
    </div>
    
    <!-- Single audio element for playing one stream at a time -->
    <audio id="audio-player" preload="none"></audio>
    
    <script>
        // Get the playlist data from PHP-generated JSON
        const playlistData = <?php echo $playlistJson; ?>;
        
        document.addEventListener('DOMContentLoaded', () => {
            // DOM Elements
            const audioPlayer = document.getElementById('audio-player');
            const playPauseBtn = document.getElementById('play-pause-btn');
            const stopBtn = document.getElementById('stop-btn');
            const muteBtn = document.getElementById('mute-btn');
            const volumeSlider = document.getElementById('volume-slider');
            const progressBar = document.getElementById('progress-bar');
            const progressBarContainer = document.querySelector('.progress-bar-container');
            const currentTimeDisplay = document.getElementById('current-time');
            const durationDisplay = document.getElementById('duration');
            const playlistContainer = document.getElementById('playlist');
            const currentTrackTitle = document.getElementById('current-track-title');
            const currentTrackDescription = document.getElementById('current-track-description');
            const playAllBtn = document.getElementById('play-all-btn');
            const refreshBtn = document.getElementById('refresh-btn');
            const searchInput = document.getElementById('search-input');
            const playerStatus = document.getElementById('player-status');

            // Player state
            let playlist = playlistData;
            let currentTrackIndex = -1;
            let isPlayingAll = false;
            let hls = null;

            // Check if HLS.js is supported
            const isHlsSupported = Hls.isSupported();
            
            // Check if native HLS is supported
            const isNativeHlsSupported = audioPlayer.canPlayType('application/vnd.apple.mpegurl') !== '';
            
            // Initialize player
            function initPlayer() {
                // Set initial volume
                audioPlayer.volume = volumeSlider.value;
                
                // Add event listeners
                playPauseBtn.addEventListener('click', togglePlayPause);
                stopBtn.addEventListener('click', stopAudio);
                muteBtn.addEventListener('click', toggleMute);
                volumeSlider.addEventListener('input', setVolume);
                progressBarContainer.addEventListener('click', seekAudio);
                audioPlayer.addEventListener('timeupdate', updateProgress);
                audioPlayer.addEventListener('ended', handleTrackEnd);
                audioPlayer.addEventListener('error', handleAudioError);
                audioPlayer.addEventListener('playing', () => {
                    showStatus('Stream playing successfully', 'success');
                });
                playAllBtn.addEventListener('click', togglePlayAll);
                refreshBtn.addEventListener('click', () => window.location.reload());
                searchInput.addEventListener('input', filterPlaylist);
                
                // Render playlist
                renderPlaylist(playlist);
            }

            // Show status message
            function showStatus(message, type) {
                playerStatus.textContent = message;
                playerStatus.className = type;
                
                // Hide after 5 seconds
                setTimeout(() => {
                    playerStatus.style.display = 'none';
                }, 5000);
            }

            // Handle audio errors
            function handleAudioError(e) {
                console.error('Audio error:', e);
                
                // Try alternative stream format if available
                if (currentTrackIndex >= 0) {
                    const track = playlist[currentTrackIndex];
                    showStatus('Error playing stream. Trying alternative format...', 'error');
                    
                    // If we were using direct URL, try HLS
                    if (audioPlayer.src === track.url && track.hls_url) {
                        playHlsStream(track.hls_url);
                    } 
                    // If we were using HLS, try direct URL
                    else if (track.url) {
                        playDirectStream(track.url);
                    }
                    else {
                        showStatus('Could not play this stream. Please try another one.', 'error');
                    }
                }
            }

            // Play HLS stream
            function playHlsStream(url) {
                // Clean up existing HLS instance if any
                if (hls) {
                    hls.destroy();
                    hls = null;
                }
                
                if (isHlsSupported) {
                    hls = new Hls({
                        debug: false,
                        enableWorker: true
                    });
                    
                    hls.loadSource(url);
                    hls.attachMedia(audioPlayer);
                    
                    hls.on(Hls.Events.MANIFEST_PARSED, function() {
                        audioPlayer.play()
                            .then(() => {
                                playPauseBtn.textContent = 'Pause';
                            })
                            .catch(error => {
                                console.error('Error playing HLS stream:', error);
                                showStatus('Could not play HLS stream. Please try again.', 'error');
                            });
                    });
                    
                    hls.on(Hls.Events.ERROR, function(event, data) {
                        if (data.fatal) {
                            console.error('Fatal HLS error:', data);
                            showStatus('HLS stream error. Trying direct stream...', 'error');
                            
                            // Try direct stream as last resort
                            if (currentTrackIndex >= 0) {
                                playDirectStream(playlist[currentTrackIndex].url);
                            }
                        }
                    });
                } 
                else if (isNativeHlsSupported) {
                    // For Safari which has native HLS support
                    audioPlayer.src = url;
                    audioPlayer.load();
                    audioPlayer.play()
                        .then(() => {
                            playPauseBtn.textContent = 'Pause';
                        })
                        .catch(error => {
                            console.error('Error playing native HLS stream:', error);
                            showStatus('Could not play stream. Please try again.', 'error');
                        });
                } 
                else {
                    showStatus('HLS playback not supported in this browser. Trying direct stream...', 'error');
                    
                    // Fall back to direct stream
                    if (currentTrackIndex >= 0) {
                        playDirectStream(playlist[currentTrackIndex].url);
                    }
                }
            }

            // Play direct stream
            function playDirectStream(url) {
                // Clean up existing HLS instance if any
                if (hls) {
                    hls.destroy();
                    hls = null;
                }
                
                audioPlayer.src = url;
                audioPlayer.load();
                audioPlayer.play()
                    .then(() => {
                        playPauseBtn.textContent = 'Pause';
                    })
                    .catch(error => {
                        console.error('Error playing direct stream:', error);
                        showStatus('Could not play stream. Please try another one.', 'error');
                    });
            }

            // Render playlist items
            function renderPlaylist(items) {
                playlistContainer.innerHTML = '';
                
                items.forEach((item, index) => {
                    const listItem = document.createElement('li');
                    listItem.className = 'playlist-item';
                    listItem.dataset.index = index;
                    
                    listItem.innerHTML = `
                        <div class="playlist-item-info">
                            <div class="playlist-item-title">${item.title}</div>
                            <div class="playlist-item-description">${item.description || ''}</div>
                        </div>
                        <div class="playlist-item-duration">${item.duration || ''}</div>
                    `;
                    
                    listItem.addEventListener('click', () => {
                        playTrack(index);
                    });
                    
                    playlistContainer.appendChild(listItem);
                });
                
                // Enable controls if playlist has items
                if (items.length > 0) {
                    enableControls();
                }
            }

            // Filter playlist based on search input
            function filterPlaylist() {
                const searchTerm = searchInput.value.toLowerCase();
                
                if (searchTerm === '') {
                    renderPlaylist(playlist);
                    return;
                }
                
                const filteredItems = playlist.filter(item => 
                    item.title.toLowerCase().includes(searchTerm) || 
                    (item.description && item.description.toLowerCase().includes(searchTerm))
                );
                
                renderPlaylist(filteredItems);
            }

            // Play selected track
            function playTrack(index) {
                // Stop any currently playing audio
                stopAudio();
                
                // Update active track in playlist
                document.querySelectorAll('.playlist-item').forEach(item => {
                    item.classList.remove('active');
                });
                
                const activeItem = document.querySelector(`.playlist-item[data-index="${index}"]`);
                if (activeItem) {
                    activeItem.classList.add('active');
                    // Scroll to the active item
                    activeItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
                
                // Update current track index
                currentTrackIndex = index;
                
                // Update player with track info
                const track = playlist[index];
                currentTrackTitle.textContent = track.title;
                currentTrackDescription.textContent = track.description || '';
                
                // Try HLS stream first if available (better for mobile)
                if (track.hls_url && (isHlsSupported || isNativeHlsSupported)) {
                    playHlsStream(track.hls_url);
                } else {
                    playDirectStream(track.url);
                }
            }

            // Toggle play/pause
            function togglePlayPause() {
                if (currentTrackIndex === -1 && playlist.length > 0) {
                    // If no track is selected, play the first one
                    playTrack(0);
                    return;
                }
                
                if (audioPlayer.paused) {
                    audioPlayer.play()
                        .then(() => {
                            playPauseBtn.textContent = 'Pause';
                        })
                        .catch(error => {
                            console.error('Error playing audio:', error);
                            showStatus('Could not resume playback. Please try again.', 'error');
                        });
                } else {
                    audioPlayer.pause();
                    playPauseBtn.textContent = 'Play';
                }
            }

            // Stop audio playback
            function stopAudio() {
                audioPlayer.pause();
                audioPlayer.currentTime = 0;
                playPauseBtn.textContent = 'Play';
                
                // Clean up HLS if needed
                if (hls) {
                    hls.destroy();
                    hls = null;
                }
            }

            // Toggle mute
            function toggleMute() {
                audioPlayer.muted = !audioPlayer.muted;
                muteBtn.textContent = audioPlayer.muted ? 'Unmute' : 'Mute';
            }

            // Set volume
            function setVolume() {
                audioPlayer.volume = volumeSlider.value;
                if (audioPlayer.volume > 0) {
                    audioPlayer.muted = false;
                    muteBtn.textContent = 'Mute';
                }
            }

            // Seek audio to clicked position
            function seekAudio(event) {
                if (!audioPlayer.duration || isNaN(audioPlayer.duration)) {
                    showStatus('Seeking is not available for live streams', 'error');
                    return;
                }
                
                const rect = progressBarContainer.getBoundingClientRect();
                const clickPosition = (event.clientX - rect.left) / rect.width;
                audioPlayer.currentTime = clickPosition * audioPlayer.duration;
            }

            // Update progress bar and time displays
            function updateProgress() {
                // Update progress bar
                const progress = (audioPlayer.currentTime / audioPlayer.duration) * 100;
                progressBar.style.width = `${isNaN(progress) ? 0 : progress}%`;
                
                // Update time displays
                currentTimeDisplay.textContent = formatTime(audioPlayer.currentTime);
                durationDisplay.textContent = formatTime(audioPlayer.duration);
            }

            // Format time in MM:SS format
            function formatTime(seconds) {
                if (isNaN(seconds)) return '00:00';
                
                const minutes = Math.floor(seconds / 60);
                const remainingSeconds = Math.floor(seconds % 60);
                
                return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
            }

            // Handle track end
            function handleTrackEnd() {
                if (isPlayingAll && currentTrackIndex < playlist.length - 1) {
                    // Play next track if in "play all" mode
                    playTrack(currentTrackIndex + 1);
                } else {
                    // Reset player state
                    playPauseBtn.textContent = 'Play';
                }
            }

            // Toggle "play all" mode
            function togglePlayAll() {
                isPlayingAll = !isPlayingAll;
                playAllBtn.textContent = isPlayingAll ? 'Cancel Play All' : 'Play All';
                
                if (isPlayingAll && (currentTrackIndex === -1 || audioPlayer.paused)) {
                    // Start playing from the first track
                    playTrack(0);
                }
            }

            // Enable player controls
            function enableControls() {
                playPauseBtn.disabled = false;
                stopBtn.disabled = false;
                muteBtn.disabled = false;
            }

            // Initialize the player
            initPlayer();
        });
    </script>
</body>
</html>
