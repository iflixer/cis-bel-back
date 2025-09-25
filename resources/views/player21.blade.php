<!doctype html>
<html lang="ru" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <meta name="referrer" content="always">
    <meta name="viewport" content="user-scalable=0, initial-scale=1.0, maximum-scale=1.0, width=device-width">
    <title>player</title>
    <link rel="stylesheet" href="/player/css/player.css">
    <script src="/player/js/jquery.min.js"></script>
    <script src="/player/js/jquery.nice-select.min.js"></script>
    <script src="/player/js/player21.js?v={{ hash_file('md5', public_path('player/js/player21.js')) }}"></script>
    <style>
        #nomedia-message {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.8);
            color: #fff;
            padding: 1em 2em;
            border-radius: 5px;
            font-size: 16px;
            opacity: 1;
            transition: opacity 0.5s ease;
            z-index: 9999;
        }
        .small-loader {
            display: inline-block;
            margin-left: 10px;
            width: 10px;
            height: 10px;
            border: 3px solid #fff;
            border-top: 3px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
    </style>

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-ECHML7LBXL"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        // gtag('set', 'transport_url', 'https://<?php echo $_SERVER['HTTP_HOST'];?>');
        gtag('js', new Date());
        gtag('config', 'G-ECHML7LBXL');
        // gtag('config', 'G-ECHML7LBXL', {
        //     // debug_mode: true,
        //     user_id: 'user42',
        //     // user_properties: { plan: 'pro' },
        //     // consent: { analytics_storage: 'granted' },
        //     send_page_view: true
        // });

    </script>
    {{-- <script src="/player/js/gaproxy.js?v={{ hash_file('md5', public_path('player/js/gaproxy.js')) }}"></script> --}}
    {{-- <script>
        gtag('event', 'page_view', {
            page_location: location.href,
            page_title: document.title,
            page_referrer: document.referrer
        });
    </script> --}}
</head>
<body>

@php
    if (isset($_GET['debug_data']) && $_GET['debug_data'] == '1') {
        echo '<style>body { background: #000; color: #fff; overflow:auto}</style>';
        dd($__data);
    }
  
@endphp

<div id="selectors" class="video_selectors" style="display: flex;">


    @if ($type === 'serial')

        <span<?php echo (isset($_GET['no_controls']) || isset($_GET['no_control_seasons']) || isset($_GET['no_control_episodes'])) ? ' style="display:none;"' : ' style="display: inline-block;"'; ?>>
				<select name="season" id="season-number" data-select="1">
					@foreach ($seasons as $_season)
                        <option value="{{ $_season }}" @if ($season && $season == $_season) selected="selected"
                                readonly="readonly"@endif>Сезон {{ $_season }}</option>
                    @endforeach
				</select>
			</span>

        <span<?php echo (isset($_GET['no_controls']) || isset($_GET['no_control_episodes'])) ? ' style="display:none;"' : ' style="display: inline-block;"'; ?>>
				<select name="episode" id="episode-number" data-select="1">
					@foreach ($episodes as $_episode)
                        <option value="{{ $_episode }}" @if ($episode && $episode == $_episode) selected="selected"
                                readonly="readonly"@endif>Серия {{ $_episode }}</option>
                    @endforeach
				</select>
			</span>

    @endif

    @if ($translations)
        <span<?php echo (isset($_GET['no_controls']) || isset($_GET['no_control_translations'])) ? ' style="display:none;"' : ' style="display: inline-block;"'; ?>>
			<select name="translator" id="translator-name" data-select="1">
				@foreach ($translations as $translation)
                    <option value="{{ $translation['id'] }}"
                            @if ($translate && $translate == $translation['id']) selected="selected"
                            readonly="readonly"@endif>{{ $translation['title'] }}</option>
                @endforeach
			</select>
		</span>
    @endif

</div>

<div id="player" class="player"></div>

<script>

    <?php if (strpos($_SERVER['REQUEST_URI'], '/show2/') === false) { ?>
        // var referrer = document.referrer;
        if (document.referrer) {
            const parentOrigin = new URL(document.referrer).origin;
            if (parentOrigin && window.self !== window.top) {
                window.parent.postMessage('khL', parentOrigin);
            }
        }
    <?php } ?>

    var tgc = '{{ $tgc }}';

    var cdn = cdn || {};

    window.abc = false;

    iframeReferer = '<?php

                     if (isset($_GET['domain']) && $_GET['domain'] && preg_match("#^[a-z0-9-_.]+$#i", $_GET['domain'])) {
                         echo $_GET['domain'];
                     } elseif (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']) {
                         $url = parse_url($_SERVER['HTTP_REFERER']);
                         if ($url['host'] && preg_match("#^[a-z0-9-_.]+$#i", $url['host'])) {
                             echo $url['host'];
                         } else {
                             echo 'futemaxlive.com';
                         }
                     }

                     ?>';

    cdn.player = (function () {
        var pub = {};

        var CDNplayer = null,
            CDNautoplay = {{ $autoplay }},
            CDNstart = {{ $start }},
            CDNquality = null,
            // durationVideo = 3254,
            currentTime = 0,
            durationTime = null,
            iframeHostname = window.location.hostname;

        pub.iframeVastKey = '';
        pub.iframeVastValue = {'p': 0, 'm': 0};

        window.getCDNplayerCUID = function () {
            return 'kh{{ $id }}';
        }

        pub.controlSelectors = function (event) {
            var is_hidden = 0;

            if (is_hidden == 1)
                return;

            if (event == 'show') {
                $('#selectors').stop(true, true).fadeIn(100);
            } else {
                $('#selectors').stop(true, true).fadeOut(100);
            }
        }

        pub.is_touch = function () {
            return 'ontouchstart' in document.documentElement;
        }

        pub.getIframeReferer = function () {
            return iframeReferer;
        }

        pub.getIframeHostname = function () {
            return iframeHostname;
        }

        window.setSave = function (currentTime, durationTime) {
            var _key = "save-" + getCDNplayerCUID(),
                _value = {
                    p: {{ $id }},
                    t: {{ $translate }},
                    d: 0,
                    tn: '{{ $translateTitle }}',
                    s: {{ $season ?: 'null' }},
                    e: {{ $episode ?: 'null' }},
                    time: Math.floor(currentTime),
                    duration: durationTime
                };

            try {
                localStorage.setItem(_key, JSON.stringify(_value));

                return true;
            } catch (e) {
            }
            ;

            return false;
        }

        window.removeSave = function () {
            var _key = "save-" + getCDNplayerCUID();

            try {
                return localStorage.removeItem(_key);
            } catch (e) {
            }
            ;

            return true;
        }

        window.getSave = function () {
            var is_hidden = 0;

            if (is_hidden == 1)
                return;

            var _key = "save-" + getCDNplayerCUID();

            try {
                return localStorage.getItem(_key);
            } catch (e) {
            }
            ;

            return null;
        }

        window.setItem = function (k, v) {
            try {
                localStorage.setItem(k, v);

                return true;
            } catch (e) {
            }
            ;

            return false;
        }

        window.getItem = function (k) {
            try {
                return localStorage.getItem(k);
            } catch (e) {
            }
            ;

            return null;
        }

        var toFormattedTime = function (input, withHours, roundSeconds) {
            if (roundSeconds) {
                input = Math.ceil(input);
            }

            var hoursString = '00';
            var minutesString = '00';
            var secondsString = '00';
            var hours = 0;
            var minutes = 0;
            var seconds = 0;

            hours = Math.floor(input / (60 * 60));
            input = input % (60 * 60);

            minutes = Math.floor(input / 60);
            input = input % 60;

            seconds = input;

            hoursString = (hours >= 10) ? hours.toString() : '0' + hours.toString();
            minutesString = (minutes >= 10) ? minutes.toString() : '0' + minutes.toString();
            secondsString = (seconds >= 10) ? seconds.toString() : '0' + seconds.toString();

            return ((withHours) ? hoursString + ':' : '') + minutesString + ':' + secondsString;
        }

        pub.setVBR = function (v) {
            setItem('vbr', v);
        }

        pub.getVBR = function () {
            var _vbr = getItem('vbr');

            return ((_vbr !== null) ? _vbr : iframeReferer);
        }

        pub.buildCDNplayer = function () {
            pub.setVBR(iframeReferer);

            /*if (document.referrer) {
                var referrer = document.referrer;
                var matches = referrer.match(/^https?\:\/\/(?:www\.)?([^\/?#]+)(?:[\/?#]|$)/i);
                var domain = matches && matches[1];

                if (domain !== null && domain != 'api.kholobok.biz') {
                    iframeReferer = domain;

                    pub.setVBR(iframeReferer);
                }
            }*/

            try {
                if (localStorage.getItem('pljsvolume_updated') === null) {
                    localStorage.setItem('pljsvolume', 1);
                    localStorage.setItem('pljsvolume_updated', 1);
                }
            } catch (e) {
            }
            ;

            if (CDNplayer === null) {
                // console.log(CDNplayerConfig);
                CDNplayer = new Playerjs(CDNplayerConfig);
                window.CDNplayer = CDNplayer;
            }
        }

        var lns = [];

        PlayerReady = function () {
            if (CDNautoplay == 0) {
                var _save = getSave();

                if (_save) {
                    try {
                        _save = JSON.parse(_save);

                        if (_save.t !== 0 && _save.tn !== null && $('#translator-name option[value="' + _save.t + '"]').length < 1) {
                            return false;
                        }

                        var _allowed = 1,
                            _cmod = '';

                        switch (_cmod) {
                            case 'translator':

                                if (_save.t != '20') {
                                    _allowed = 0;
                                }

                                break;

                            case 'season':

                                if (_save.s != 1) {
                                    _allowed = 0;
                                }

                                break;

                            case 'episode':

                                if (_save.s != 1 || _save.e != 1) {
                                    _allowed = 0;
                                }

                            case 'single':

                                if (_save.t != '20' || _save.s != 1 || _save.e != 1) {
                                    _allowed = 0;
                                }

                                break;
                        }

                        var _cstop = 0;

                        if (_cstop > 0 && durationVideo > 0 && Math.ceil(_save.time * 100 / durationVideo) >= _cstop) {
                            _allowed = 0;
                        }

                        if (_allowed == 1) {
                            var _url_params = [];

                             _url_params.push('domain=' + pub.getVBR());

                            if (_save.t != null) {
                                _url_params.push('translation=' + _save.t);
                            }

                            if (_save.s != null) {
                                _url_params.push('season=' + _save.s);
                            }

                            if (_save.e != null) {
                                _url_params.push('episode=' + _save.e);
                            }

                            <?php if (isset($_GET['no_controls'])): ?>
                            _url_params.push('no_controls=1');
                            <?php endif; ?>
                            <?php if (isset($_GET['no_control_translations'])): ?>
                            _url_params.push('no_control_translations=1');
                            <?php endif; ?>
                            <?php if (isset($_GET['no_control_seasons'])): ?>
                            _url_params.push('no_control_seasons=1');
                            <?php endif; ?>
                            <?php if (isset($_GET['no_control_episodes'])): ?>
                            _url_params.push('no_control_episodes=1');
                            <?php endif; ?>

                            if (tgc) {
                                _url_params.push('tgc=' + tgc);
                            }

                            if (_save.t === 0 && _save.tn === null) {
                                if (_save.time)
                                    _url_params.push('start=' + _save.time);

                                _url_params = '/show/' + _save.p + ((_url_params.length > 0) ? '?' + _url_params.join('&') : '');
                            } else {
                                _url_params.push('start=' + _save.time);
                                _url_params.push('autoplay=1');

                                _url_params = '/show/' + _save.p + ((_url_params.length > 0) ? '?' + _url_params.join('&') : '');
                            }

                            var _html = '<div id="save-holder" class="save_holder" style="display: none;"><a id="continue-play" href="javascript:void(0)" data-url="' + _url_params + '">продолжить просмотр с ' + toFormattedTime(_save.time, true, true) + '</a>' + ((_save.e != null) ? '<div class="save_holder_sting">' + ((_save.s != null) ? 'сезон ' + _save.s + ' ' : '') + ((_save.e != null) ? 'серия ' + _save.e : '') + '</div>' : '') + ((_save.tn != null) ? '<div class="save_holder_sting"><b>' + _save.tn + ((_save.d == 1) ? ' (расшир. версия)' : '') + '</b></div>' : '') + '</div>';

                            $(_html).appendTo('body');

                            $('#save-holder').css({
                                'margin-left': -1 * $('#save-holder').width() / 2,
                                'left': '50%'
                            }).show();
                        }
                    } catch (e) {
                    }
                    ;
                }
            }


        }

        /* player config */
        CDNquality = getItem('pljsquality');
        if(!CDNquality) {
            CDNquality = 360;
        }

        var CDNplayerConfig = {
            'id': 'player',
            'cuid': getCDNplayerCUID(),
          //  'poster': null,
            'file': '{{ $file }}',
            'default_quality':CDNquality,
            'debug': 0,
            'ready': PlayerReady(),
            'autoplay': CDNautoplay,
            'start': CDNstart,
            'preload': 'metadata',
            'hlsconfig': {
                // Сколько видео держать впереди (VOD/Live)
                maxBufferLength: 15,           // сек (по умолчанию может быть больше)
                maxMaxBufferLength: 30,        // жёсткий потолок
                backBufferLength: 30,          // не держать слишком длинный «хвост»
                // Live-синхронизация (если поток живой)
                liveSyncDurationCount: 3,      // держаться ~3 сегментов от live-edge
                liveMaxLatencyDurationCount: 6,
                lowLatencyMode: false,         // если не LL-HLS
                // Временные лимиты и поведение при дырках в буфере
                fragLoadingTimeOut: 10000,
                manifestLoadingTimeOut: 5000,
                maxBufferHole: 0.5,
                maxStarvationDelay: 4,
                // ABR можно чуть «успокоить»
                abrEwmaDefaultEstimate: 5_000_000, // стартовая оценка (бит/с), подстрой под свою сеть
                // Параллелизм/ретраи (по ситуации)
                fragLoadingMaxRetry: 5, // def.6
                levelLoadingMaxRetry: 3, // def.4-6
                // Иногда помогает отключить worker, если есть странные зависания в браузерах
                enableWorker: true
            }
         // 'subtitle': false,
        }

        return pub;
    }());

    $(function () {
        if (!cdn.player.is_touch()) {
            $('#selectors select[data-select="1"]').niceSelect();

            setTimeout(function () {
                $('.nice-select ul').each(function () {
                    var _dropdown = $(this),
                        _selected = null,
                        _pos = 0;

                    // _dropdown.find('li[data-value="0"]').hide();

                    _selected = _dropdown.find('.selected');
                    _pos = _selected.position().top;

                    if (_pos > 0) {
                        _dropdown.animate({scrollTop: _selected.position().top - _selected.height() / 2}, 0);
                    }
                });
            }, 0);
        }

        $('#translator-name option[value="0"]').hide();

        cdn.player.controlSelectors('show');

        cdn.player.buildCDNplayer();

        $('#continue-play').on('click', function (e) {
            e.preventDefault();

            window.location.href = $(this).data('url');
        });

        @if ($type === 'movie')

        // movie

        var p_id = {{ $id }},
            type = 'movie',
            m_s = 'auto',
            m_s_set = 1;

        $('#translator-name').change(function () {
            var t = $(this).find(':selected').attr('value');
            window.location.href = '/show/' + p_id + '?domain=' + iframeReferer + '&translation=' + t + (tgc ? '&tgc=' + tgc : '');
            // window.location.href = '/show/' + p_id + '?translation=' + t + (tgc ? '&tgc=' + tgc : '');
        });

        @elseif ($type === 'serial')

        // serial

        var p_id = {{ $id }},
            type = 'serial',
            m_s = 'manual',
            m_s_set = 0;

        $('#translator-name').change(function () {
            var _translator_id = $(this).val(),
                _season = $('#season-number').val(),
                _episode = $('#episode-number').val();

            m_s = 'manual';

            var _seasons_select = $('select#season-number'),
                _episodes_select = $('select#episode-number'),
                _selected = '';

            _seasons_select.empty();
            _episodes_select.empty();

            $.each(translations_episodes[_translator_id], function (season, episodes) {
                _selected = (parseInt(season) == parseInt(_season)) ? ' selected="selected"' : '';
                _seasons_select.append('<option value="' + season + '"' + _selected + '>Сезон ' + season + '</option>');

                if (parseInt(season) == parseInt(_season)) {
                    // console.log(episodes);
                    $.each(episodes, function (key, episode) {
                        _selected = (parseInt(episode) == parseInt(_episode)) ? ' selected="selected"' : '';
                        _episodes_select.append('<option value="' + episode + '"' + _selected + '>Серия ' + episode + '</option>');
                    });
                }
            });

            _episodes_select.change();
        });

        var translations_episodes = <?php echo json_encode($translations_episodes); ?>;
        var seasons_episodes = <?php echo json_encode($seasons_episodes); ?>;

        $('#season-number').change(function () {
            var _season = $(this).val(),
                _episodes_select = $('#episode-number');

            _episodes_select.empty();

            var _len = seasons_episodes[_season].length,
                _selected = '';

            for (var i = 0; i < _len; i++) {
                _selected = (i == 0) ? ' selected="selected"' : '';

                _episodes_select.append('<option value="' + seasons_episodes[_season][i] + '"' + _selected + '>Серия ' + seasons_episodes[_season][i] + '</option>');
            }

            _episodes_select.change();
        })

        $('#episode-number').change(function () {
            var _episode = $(this).val(),
                _season = $('#season-number').val(),
                _translate = $('#translator-name').find(':selected').attr('value');

            var _url_params = [];

            _url_params.push('domain=' + iframeReferer);

                <?php if (isset($_GET['no_controls'])): ?>
            _url_params.push('no_controls=1');
            <?php endif; ?>
                <?php if (isset($_GET['no_control_translations'])): ?>
            _url_params.push('no_control_translations=1');
            <?php endif; ?>
                <?php if (isset($_GET['no_control_seasons'])): ?>
            _url_params.push('no_control_seasons=1');
            <?php endif; ?>
                <?php if (isset($_GET['no_control_episodes'])): ?>
            _url_params.push('no_control_episodes=1');
            <?php endif; ?>

            if (tgc) {
                _url_params.push('tgc=' + tgc);
            }

            if (m_s == 'auto') {
                window.location.href = '?season=' + _season + '&episode=' + _episode + ((_url_params.length > 0) ? '&' + _url_params.join('&') : '');
            } else if (m_s_set == 1) {
                window.location.href = '/show/' + p_id + '?translation=' + _translate + ((_url_params.length > 0) ? '&' + _url_params.join('&') : '');
            } else {
                window.location.href = '/show/' + p_id + '?translation=' + _translate + '&season=' + _season + (_episode ? '&episode=' + _episode : '') + ((_url_params.length > 0) ? '&' + _url_params.join('&') : '');
            }

        });
        @endif
    });

    // ADD CHANNEL1 TO VAST request
    const injectUrl = iframeReferer;
    const originalOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function(method, url, async, user, password) {
        if (url.includes('vast=')) {
            const separator = url.includes('?') ? '&' : '?';
            url += separator + 'channel1='+injectUrl;
        }
        return originalOpen.call(this, method, url, async, user, password);
    };


    window.PlayerjsEvents = function (event, id, info) {
        // console.log('PlayerjsEvents', event, id, info);
        if (typeof CDNplayer == "undefined") {
            return;
        }
        try {
            if(event=="prehls"){
                //
            }

            if(event=="vast_Impression"){
                // console.log('PlayerjsEvents', event, info);
                let infoobj = JSON.parse(info);
                let iswas = infoobj["is"];

                if (typeof gtag !== 'undefined') {
                    gtag('event', 'VAST_impression: '+iswas, {'event_category': 'Videos'});
                }
                if (window.self !== window.top) {
                    window.parent.postMessage({
                        type: "CDN_PLAYER_EVENT",
                        action: 'VAST_impression: '+iswas,
                    }, "*");
                }

                // ADD SAVE TO LOCAL STATISTIC
            }


            if (event == "vast_complete" || event == "vast_skip") { // NOW WORKS in PJS21
                // console.log('PlayerjsEvents', event, info);
                if (typeof cdn.player.iframeVastValue[cdn.player.iframeVastKey] != 'undefined') {
                    var matches = $.parseJSON(info).url.match(/khtag=([0-9]+)/i);
                    var ad_id = matches[1];
                    $.ajax({
                        type: 'get',
                        url: '/apishow/shows.showsAd',
                        data: 'domain=' + cdn.player.getVBR() + ad_id + '&file_id={{ $id }}' +  (tgc ? '&tgc=' + tgc : ''),
                        dataType: "html",
                        cache: false,
                        success: function (response) {
                        }
                    });
                }
            }

            if (event == "init") {
                // console.log('PlayerjsEvents', event, info);
                if (CDNplayer.api('adblock')) {
                    window.abc = true;
                }
            }

            if (event == 'subtitle') {
                // console.log('PlayerjsEvents', event, info);
                var cc = document.getElementById('player_control_cc_icon0'),
                    cl = 'none',
                    arr,
                    ln = ((lns[info] !== undefined) ? lns[info] : "");

                if (ln == '') {
                    arr = cc.className.split(" ");

                    if (arr.indexOf(cl) == -1) {
                        cc.className += ' ' + cl;
                    }
                } else {
                    cc.className = cc.className.replace(/\bnone\b/g, "");
                }

                cc.setAttribute('data-content', ln.replace(/\-\d+/i, ''));
            }

            if (event == "play" || event == "start" || event == "vast_init") {
                // console.log('PlayerjsEvents', event, info);
                cdn.player.controlSelectors('hide');
                $('#save-holder').remove();
            }

            if (event == "start") {
                // console.log('PlayerjsEvents', event, info);
                $.ajax({
                    type: 'get',
                    url: '/apishow/shows.show',
                    data: 'domain=' + cdn.player.getVBR() + '&file_id={{ $id }}' + (tgc ? '&tgc=' + tgc : ''),
                    dataType: "html",
                    cache: false,
                    success: function (response) {
                    }
                });

            }

            if (event == "pause" || event == "end") {
                // console.log('PlayerjsEvents', event, info);
                cdn.player.controlSelectors('show');
            }

            if (event == "new") {
                //
            }

            if (event == "time") {
                //console.log('PlayerjsEvents', event, info);
                if (info > 0 && CDNplayer.api('duration') > 0) {
                    currentTime = info;
                    durationTime = CDNplayer.api('duration');
                    setSave(currentTime, durationTime);
                }
            }

            if (event == "reload") {
                //
            }

            if (event == "vast_load") {
                // console.log('PlayerjsEvents', event, info);
                if (info == "preroll") {
                    cdn.player.iframeVastKey = 'p';
                } else if (info == "midroll") {
                    cdn.player.iframeVastKey = 'm';
                }
                if (typeof cdn.player.iframeVastValue[cdn.player.iframeVastKey] != 'undefined') {
                    cdn.player.iframeVastValue[cdn.player.iframeVastKey]++;
                }
            }



            // GA AND CROSSFRAME EVENTS

            if (event == "error") {
                if(info === 'Failed to open media'){
                    showPopupAndChangeTranslation();
                }
            }

            if (event == "loaderror") {
                // console.log('PlayerjsEvents', event, info);
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'Video load error', {'event_category': 'Videos'});
                }
                if (window.self !== window.top) {
                    window.parent.postMessage({
                        type: "CDN_PLAYER_EVENT",
                        action: "Load error",
                    }, "*");
                }
            }

            if (event == "quartile") {
                //console.log('PlayerjsEvents', event, info);
                const p = ({'50%':'p50','75%':'p75','100%':'p100'}[info]) || 'p25';
                $.ajax({
                    type: 'get',
                    url: '/apishow/shows.percent',
                    data: 'percent='+p+'&domain=' + cdn.player.getVBR() + '&file_id={{ $id }}' + (tgc ? '&tgc=' + tgc : ''),
                    dataType: "html",
                    cache: false,
                    success: function (response) {
                    }
                });
                if (typeof gtag !== 'undefined') {
                    gtag('event', info + ' of timeline completed', {'event_category': 'Videos'});
                }
                if (window.self !== window.top) {
                    window.parent.postMessage({
                        type: "CDN_PLAYER_EVENT",
                        action: info + ' of timeline completed',
                    }, "*");
                }

            }

            if (event == "userplay") {
                // console.log('PlayerjsEvents', event, info);
                $('#selectors').fadeOut('slow');
                $('#save-holder').remove();
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'Video start play', {'event_category': 'Videos'});
                }
                if (window.self !== window.top) {
                    window.parent.postMessage({
                        type: "CDN_PLAYER_EVENT",
                        action: "Video start play",
                    }, "*");
                }

            }

            if (event == "userpause") {
                // console.log('PlayerjsEvents', event, info);
                $('#selectors').fadeIn('slow');
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'Video paused', {'event_category': 'Videos'});
                }
                if (window.self !== window.top) {
                    window.parent.postMessage({
                        type: "CDN_PLAYER_EVENT",
                        action: "Video paused",
                    }, "*");

                }
            }

            if (event == "line") {
                // console.log('PlayerjsEvents', event, info);
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'Video rewind(fwd/rwd)', {'event_category': 'Videos'});
                }
                if (window.self !== window.top) {
                    window.parent.postMessage({
                        type: "CDN_PLAYER_EVENT",
                        action: "Video rewind(fwd/rwd)",
                    }, "*");
                }

            }

            if (event == "finish") {
                // console.log('PlayerjsEvents', event, info);
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'Video ENDED', {'event_category': 'Videos'});
                }
                if (window.self !== window.top) {
                    window.parent.postMessage({
                        type: "CDN_PLAYER_EVENT",
                        action: "Video ENDED",
                    }, "*");

                }
            }
        } catch (e) {
            console.error('Error handling player event:', e);
        }
    }
    let hideTimeout;
    function showSelectors() {
        if (!$('#selectors').is(':visible')) {
            $('#selectors').fadeIn('fast');
        }
        clearTimeout(hideTimeout); // prevent hiding if quickly moving between elements
    }
    function hideSelectors() {
        hideTimeout = setTimeout(function () {
            if (!$('#player').is(':hover') && !$('#selectors').is(':hover')) {
                $('#selectors').fadeOut('fast');
            }
        }, 200); // 200ms delay to allow moving between elements
    }
    // Bind events to both #player and #selectors
    $('#player, #selectors').on('mouseenter', showSelectors);
    $('#player, #selectors').on('mouseleave', hideSelectors);
</script>


<div id="nomedia-message">Озвучка недоступна → поиск доступной <div class="small-loader"></div></div>
<script>
    function showPopupAndChangeTranslation() {
        const popup = document.getElementById('nomedia-message');
        const select = document.querySelector('#translator-name');
        const currentIndex = select.selectedIndex;
        const lastIndex = select.options.length - 1;
        if (currentIndex === lastIndex) {
            popup.textContent = "Извините, файл временно недоступен.";
            return;
        }
        popup.style.display = 'block';
        popup.style.opacity = '1';
        setTimeout(() => {
            popup.style.opacity = '0';
            setTimeout(() => {
                popup.style.display = 'none';
                const select = document.querySelector('#translator-name');
                const niceSelect = $(select).next('.nice-select');
                const nextIndex = (currentIndex + 1) % select.options.length;
                select.selectedIndex = nextIndex;
                niceSelect.find('span').text(select.options[nextIndex].text);
                $(select).trigger('change');
            }, 500);
        }, 2500);
    }
</script>

<!-- Yandex.Metrika counter -->
<script type="text/javascript">
    (function(m,e,t,r,i,k,a){
        m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
    })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=104007594', 'ym');

    ym(104007594, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", accurateTrackBounce:true, trackLinks:true});
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/104007594" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
<!-- /Yandex.Metrika counter -->


</body>
</html>