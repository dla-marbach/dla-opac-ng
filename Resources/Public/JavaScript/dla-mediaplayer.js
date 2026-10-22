/**
 * Initialisiert die Video.js-Player fuer Audio, Video und Audio-Playlisten (M3U)
 * eines digitalen Objekts (siehe Resources/Private/Partials/MediaAccess/Player.html).
 *
 * - Video: passt sich der Breite und dem Seitenverhaeltnis des Videos an (fluid),
 *   damit keine schwarzen Balken oberhalb/unterhalb entstehen.
 * - Audio und Playlist: kompakte Steuerleiste ohne Videoflaeche (audioOnlyMode).
 * - Playlist: Klick bzw. Tippen auf einen Titel spielt ihn ab, am Ende eines
 *   Titels folgt automatisch der naechste.
 * - Das Kontextmenue des Browsers ist auf dem Player deaktiviert.
 */
(function () {
    'use strict';

    function initPlaylist(container, player) {
        var tracks = container.querySelectorAll('.dla-mediaplayer-track');
        if (tracks.length === 0) {
            return;
        }
        var currentIndex = 0;

        function select(index) {
            currentIndex = index;
            for (var i = 0; i < tracks.length; i++) {
                var isActive = i === index;
                tracks[i].classList.toggle('active', isActive);
                tracks[i].querySelector('button').setAttribute('aria-current', isActive ? 'true' : 'false');
            }
            player.src({ src: tracks[index].getAttribute('data-src') });
        }

        function play(index) {
            select(index);
            player.play();
        }

        for (var t = 0; t < tracks.length; t++) {
            (function (index) {
                // ganze Zeile anklickbar (Touch), der Button sorgt fuer Tastaturbedienung
                tracks[index].addEventListener('click', function () {
                    play(index);
                });
            })(t);
        }

        player.on('ended', function () {
            if (currentIndex + 1 < tracks.length) {
                play(currentIndex + 1);
            }
        });

        select(0);
    }

    function initMediaplayer(container) {
        var el = container.querySelector('video, audio');
        if (!el || typeof videojs === 'undefined') {
            return;
        }
        var type = container.getAttribute('data-mediaplayer-type');
        var options = { responsive: true };
        if (type === 'video') {
            options.fluid = true;
        } else {
            options.audioOnlyMode = true;
        }
        var player = videojs(el, options);

        player.on('contextmenu', function (e) {
            e.preventDefault();
        });

        if (type === 'playlist') {
            initPlaylist(container, player);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var containers = document.querySelectorAll('div.dla-mediaplayer[data-mediaplayer-type]');
        for (var i = 0; i < containers.length; i++) {
            initMediaplayer(containers[i]);
        }
    });
})();
