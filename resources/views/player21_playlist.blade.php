<!doctype html>
<html lang="ru" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <meta name="referrer" content="always">
    <meta name="viewport" content="user-scalable=0, initial-scale=1.0, maximum-scale=1.0, width=device-width">
    <title>player playlist</title>
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


{{-- Sharing block --}}
@php
if (!isset($_GET['no_sharing']) || $_GET['no_sharing'] != '1'):
@endphp
   <style>
       #shareBlock{position:absolute;top:3px;right:-4px;z-index:999;width:auto;height:28px;}
       .share-container {
           position: relative;
           display: flex;
           align-items: center;
           transition: all 0.3s ease;
           cursor: pointer;
           width:28px;
           flex-direction: column-reverse;
           justify-content: flex-end;
           background-color: transparent;
           padding:4px;
       }
       .share-container.expanded {
           width:auto;
           background-color: #000;
         }
       .share-icon-wrapper {
           display: flex;
           flex-direction: row-reverse;
           align-items: center;
           height: 28px;
           min-width:30px;
       }

       .share-icon,#mainShareIcon {
           width: 20px;
           height: 20px;
           padding: 0;
           margin:4px 6px;
           box-sizing: border-box;
           fill: #fff;
           cursor: pointer;
           display: none;
       }
       .expanded .share-icon, #mainShareIcon  {
           display: block;
       }
       .share-icon:hover path {
           fill: #999 !important;
       }
   </style>
   <div class="share-container" id="shareBlock">
       <div class="share-icon-wrapper">
           <!-- Share icon -->
           <svg id="mainShareIcon" width='30' height='30'><g fill-rule='nonzero' fill='#000000' transform='translate(1, 0)'><path d='M5.77016889,12.3256919 C5.18437308,12.8975888 4.38334157,13.25 3.5,13.25 C1.70507456,13.25 0.25,11.7949254 0.25,10 C0.25,8.20507456 1.70507456,6.75 3.5,6.75 C4.37133761,6.75 5.16258703,7.09289831 5.7461684,7.65111507 L11.3872231,4.01000937 L12.200684,5.27027971 L6.56253075,8.90951263 C6.68391195,9.25037613 6.75,9.61747022 6.75,10 C6.75,10.3707721 6.68791215,10.7270429 6.57356624,11.0589825 L12.2487283,14.6601773 L11.4450473,15.9267068 L5.77016889,12.3256919 Z M13.5,6.75 C11.7050746,6.75 10.25,5.29492544 10.25,3.5 C10.25,1.70507456 11.7050746,0.25 13.5,0.25 C15.2949254,0.25 16.75,1.70507456 16.75,3.5 C16.75,5.29492544 15.2949254,6.75 13.5,6.75 Z M3.5,11.75 C4.46649831,11.75 5.25,10.9664983 5.25,10 C5.25,9.03350169 4.46649831,8.25 3.5,8.25 C2.53350169,8.25 1.75,9.03350169 1.75,10 C1.75,10.9664983 2.53350169,11.75 3.5,11.75 Z M13.5,5.25 C14.4664983,5.25 15.25,4.46649831 15.25,3.5 C15.25,2.53350169 14.4664983,1.75 13.5,1.75 C12.5335017,1.75 11.75,2.53350169 11.75,3.5 C11.75,4.46649831 12.5335017,5.25 13.5,5.25 Z M13.5,19.75 C11.7050746,19.75 10.25,18.2949254 10.25,16.5 C10.25,14.7050746 11.7050746,13.25 13.5,13.25 C15.2949254,13.25 16.75,14.7050746 16.75,16.5 C16.75,18.2949254 15.2949254,19.75 13.5,19.75 Z M13.5,18.25 C14.4664983,18.25 15.25,17.4664983 15.25,16.5 C15.25,15.5335017 14.4664983,14.75 13.5,14.75 C12.5335017,14.75 11.75,15.5335017 11.75,16.5 C11.75,17.4664983 12.5335017,18.25 13.5,18.25 Z' fill='#ffffff'/></g></svg>
           <!-- Facebook -->
           <svg class="share-icon" data-network="facebook"  title="Facebook" width="20" height="20"><g fill-rule="nonzero" fill="#ffffff" transform="translate(5, 0)"><path d="M9.0046164,6.96547571 L5.93796049,6.96547571 L5.93796049,4.9710242 C5.93796049,4.222011 6.43857342,4.0473871 6.79116873,4.0473871 L8.95527714,4.0473871 L8.95527714,0.754593019 L5.97486463,0.743057503 C2.66632596,0.743057503 1.91340075,3.19892978 1.91340075,4.77054483 L1.91340075,6.96547571 L0,6.96547571 L0,10.3585094 L1.91340075,10.3585094 L1.91340075,19.9596414 L5.93796049,19.9596414 L5.93796049,10.3585094 L8.65362566,10.3585094 L9.0046164,6.96547571 Z" fill="#ffffff"></path></g></svg>
           <!-- Twitter -->
           <svg class="share-icon" data-network="twitter"  title="Twitter" width="20" height="20"><g fill-rule="nonzero" fill="#ffffff" transform="translate(1, 3)"><path d="M5.65924068,14.5502468 C12.4537183,14.5502468 16.170066,8.95126214 16.170066,4.09918302 C16.170066,3.94193972 16.170066,3.78469641 16.1587701,3.62183727 C16.881707,3.09956489 17.5029809,2.45936001 18,1.71807016 C17.3278945,2.01570926 16.6106056,2.21226339 15.8763729,2.29650087 C16.6501412,1.83600263 17.2262316,1.11717611 17.5029809,0.26356962 C16.780044,0.69037286 15.9836837,0.993627802 15.1590839,1.15648694 C13.7583936,-0.320476935 11.4201444,-0.393482754 9.93473493,0.999243643 C8.97458432,1.89777678 8.56793226,3.23434485 8.86727334,4.50913877 C5.89645437,4.36312713 3.12896141,2.96478491 1.25384375,0.673525371 C0.271101355,2.35265919 0.773768433,4.49790711 2.3947286,5.57614688 C1.80734234,5.55929939 1.23125198,5.40205609 0.717288991,5.11564864 L0.717288991,5.1605753 C0.717288991,6.90709912 1.95983684,8.41214219 3.68246,8.76032384 C3.1402573,8.90633543 2.56981488,8.92879866 2.01631629,8.82209782 C2.50203955,10.3159092 3.88578602,11.3436065 5.46721055,11.3716857 C4.15688737,12.3937672 2.54157515,12.9497346 0.881079381,12.9497346 C0.587386264,12.9497346 0.293693132,12.9328871 0,12.899192 C1.68308754,13.9774318 3.64857233,14.5502468 5.65924068,14.5502468" fill="#ffffff"></path></g></svg>
           <!-- Telegram -->
           <svg class="share-icon" data-network="telegram" title="Telegram" width="20" height="20"><g fill-rule="nonzero" fill="#ffffff" transform="translate(1, 2)"><path d="M17.7638875,0.170942228 C17.5104093,-0.0435393255 17.1009445,-0.0630376486 16.3990049,0.151443905 L16.3990049,0.151443905 C15.9115468,0.30743049 11.5829191,1.94528963 7.76124776,3.5246538 C4.3295429,4.94803138 1.5412827,6.19592406 1.20981121,6.35191064 C0.839343069,6.46890058 0.0399118236,6.8198704 0.000915177477,7.36582344 C-0.0185831456,7.71679326 0.2738917,8.02876643 0.839343069,8.28224463 C1.44379108,8.59421779 4.11506135,9.49114066 4.68051271,9.66662556 C4.87549594,10.3295685 6.02589701,14.2097348 6.08439198,14.4437147 C6.16238527,14.7946845 6.39636514,14.9896678 6.51335508,15.0676611 C6.53285341,15.0871594 6.57185005,15.126156 6.6108467,15.1456543 C6.66934167,15.184651 6.74733496,15.2041493 6.84482657,15.2041493 C6.98131484,15.2041493 7.13730142,15.1456543 7.27378968,15.0481627 C7.99522764,14.463213 9.24312031,13.1568254 9.59409013,12.7863573 C11.1344577,13.9952533 12.8113134,15.3406376 12.9673,15.4966242 L12.9867983,15.5161225 C13.3572665,15.8280957 13.7472329,16.0035806 14.0982028,16.0035806 C14.2151927,16.0035806 14.3321826,15.9840822 14.4491726,15.9450856 C14.8586374,15.8085973 15.1511122,15.4186309 15.2486038,14.8921761 C15.2486038,14.8726778 15.2681021,14.7946845 15.3070988,14.6581963 C15.9700418,11.7724445 16.4964965,9.23766246 16.9254596,7.11234524 C17.3349244,5.02602467 17.6663959,2.97870075 17.8613791,1.88679466 C17.9003758,1.61381814 17.9393724,1.39933658 17.9588707,1.26284832 C18.0173657,0.872881859 18.0563623,0.424420428 17.7638875,0.170942228 Z M5.0899775,9.60813059 L14.3906776,3.4466605 C14.4101759,3.42716218 14.4491726,3.40766386 14.4686709,3.38816553 L14.4686709,3.38816553 C14.4881692,3.38816553 14.4881692,3.36866721 14.5076675,3.36866721 C14.5271659,3.36866721 14.5271659,3.36866721 14.5466642,3.34916889 C14.5271659,3.36866721 14.5076675,3.42716218 14.4686709,3.46615883 L12.1093738,5.66946933 C10.4715147,7.17084021 8.32669913,9.14017084 6.90332154,10.4270602 C6.90332154,10.4270602 6.90332154,10.4270602 6.90332154,10.4465585 L6.88382322,10.4660568 C6.88382322,10.4660568 6.88382322,10.4855551 6.8643249,10.4855551 C6.8643249,10.5050535 6.8643249,10.5050535 6.84482657,10.5245518 L6.84482657,10.5440501 C6.84482657,10.5440501 6.84482657,10.5440501 6.84482657,10.5635484 C6.74733496,11.6554545 6.57185005,13.5272935 6.49385676,14.3657214 C6.49385676,14.3657214 6.49385676,14.3657214 6.49385676,14.3462231 C6.41586347,14.1122432 5.32395738,10.3880635 5.0899775,9.60813059 Z" fill="#ffffff"></path></g></svg>
           <!-- VK -->
           <svg class="share-icon" data-network="vkontakte"  title="VKontakte" width="20" height="20"><g fill-rule="nonzero" fill="#ffffff" transform="translate(1, 5)"><path d="M18.0502246,9.29303666 C17.6194273,8.41681361 16.8996544,7.67759996 16.1500243,7.05840232 L16.1372284,7.05840232 C15.7416198,6.67817461 15.47717,6.41477664 15.3716033,6.28413974 C14.2252983,4.84394763 18.6996197,2.41601314 18.1035411,0.737913177 L18.0502246,0.645511471 C17.5746414,0.0156929429 14.7968513,0.422472869 14.1314612,0.501067424 C14.0536192,0.536116347 13.9960374,0.582848245 13.9331239,0.645511471 C12.9073142,2.01666782 12.6919155,3.72663045 11.3462069,4.89067953 C11.2406402,4.96927408 11.1617318,5.00857136 11.0966856,4.9958263 C11.0433692,4.98308123 10.9900526,4.96927408 10.9388688,4.95652902 C9.91199281,4.37662865 10.8812869,1.44845043 10.516602,0.475577298 C10.4771478,0.384237681 10.4110353,0.304581037 10.3182646,0.252538696 C10.2393561,0.200496356 10.1327232,0.161199078 10.0015645,0.120839712 C8.82646871,-0.0703362322 7.65243918,-0.0342252204 6.47840968,0.225986481 C6.34725107,0.290773886 6.22782208,0.383175592 6.12225539,0.502129513 C6.00389275,0.632766408 5.99109678,0.711360963 6.07000522,0.725168114 C6.45175142,0.778272543 6.7162013,0.922716588 6.87401816,1.13301013 C7.20777951,2.14730473 7.46263241,3.76061727 6.91347238,4.74623548 C6.8612222,4.8651894 6.82176799,4.92997681 6.79510973,4.95652902 C6.78231377,4.98308123 6.75565551,4.9958263 6.74285955,5.00857136 C6.54238948,5.10097306 6.35364904,5.07442085 6.17557191,4.92997681 C6.04334697,4.83863719 5.92498432,4.71968326 5.77889708,4.57523922 C4.93649625,3.56625507 4.32975438,2.15261517 3.82644655,0.948206718 C3.77313004,0.816507733 3.69422161,0.711360963 3.60251721,0.645511471 C3.47775658,0.567979005 3.33806733,0.50425369 3.19304642,0.475577298 L0.607195774,0.488322361 C0.342745893,0.488322361 0.158270774,0.554171853 0.0665663802,0.672063685 L0.0271121636,0.725168114 C-0.0166073732,0.856867096 -0.00701040172,0.98856608 0.0527040878,1.11920297 C1.4847855,4.41273967 3.59398657,8.99565191 7.48076003,10.0821685 C8.2015992,10.280779 9.16236271,10.2701582 9.93438572,10.2393576 C10.1593814,10.2255504 10.3171983,10.1469559 10.4366273,10.0290641 L10.4760815,9.97595968 C10.5027397,9.93666235 10.529398,9.88462003 10.542194,9.80602545 C10.7010772,9.32277521 10.5656532,8.80978641 10.8055775,8.33290858 C10.87169,8.22776182 10.9378024,8.13536014 11.0039149,8.05676556 C11.0668284,7.97392268 11.1457368,7.92188034 11.2417065,7.89957648 C12.0798421,7.60006749 13.4116884,9.93135189 14.3297987,10.2393576 C14.8661628,10.4177885 18.7518699,10.7194217 18.0502246,9.29303666 C18.0235664,9.2399323 18.0758166,9.3461411 18.0502246,9.29303666 Z" fill="#ffffff"></path></g></svg>
       </div>
   </div>
   <script>
    const shareBlock = document.getElementById('shareBlock');
    const mainShareIcon = document.getElementById('mainShareIcon');
    const shareIcons = document.querySelectorAll('.share-icon');
    mainShareIcon.addEventListener('click', () => {
        shareBlock.classList.toggle('expanded');
    });
    const shareUrls = {
        facebook: (url) => `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`,
        twitter: (url) => `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}`,
        telegram: (url) => `https://t.me/share/url?url=${encodeURIComponent(url)}`,
        vkontakte: (url) => `https://vk.com/share.php?url=${encodeURIComponent(url)}`
    };
    shareIcons.forEach(icon => {
        icon.addEventListener('click', (e) => {
            e.stopPropagation();
            const network = icon.getAttribute('data-network');
            let parentUrl;
            try {
                // Try accessing parent URL
                parentUrl = window.parent.location.href;
            } catch (err) {
                // Cross-origin error fallback
                parentUrl = document.referrer || window.location.href;
            }
            const shareLink = shareUrls[network](parentUrl);
            window.open(shareLink, '_blank', 'width=600,height=400');
        });
    });
</script>
@php
endif;
@endphp
{{-- END Sharing block --}}

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

    var onpercdone = false;
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
        @php
            if (isset($_GET['monq'])) {
               $monq =  $_GET['monq'];
               echo 'CDNquality = '.$monq.';';
        }
        @endphp

        var CDNplayerConfig = {
            'id': 'player',
            'cuid': getCDNplayerCUID(),
            'poster': '{{ $video->backdrop }}',
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

            _url_params.push('autoplay=1');

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

                $.ajax({
                    type: 'get',
                    url: '/apishow/shows.impression',
                    data: 'domain=' + cdn.player.getVBR() + '&file_id={{ $id }}' +  (tgc ? '&tgc=' + tgc : ''),
                    dataType: "html",
                    cache: false,
                    success: function (response) {
                    }
                });
            }


            if (event == "vast_complete" || event == "vast_skip") { // NOW WORKS in PJS21
                // console.log('PlayerjsEvents', event, info);
                if (typeof cdn.player.iframeVastValue[cdn.player.iframeVastKey] != 'undefined') {
                    var matches = $.parseJSON(info).url.match(/khtag=([0-9]+)/i);
                    var ad_id = matches[1];
                }
                $.ajax({
                    type: 'get',
                    url: '/apishow/shows.showsAd',
                    data: 'domain=' + cdn.player.getVBR() + '&file_id={{ $id }}' +  (tgc ? '&tgc=' + tgc : ''),
                    dataType: "html",
                    cache: false,
                    success: function (response) {
                    }
                });
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
                cdn.player.controlSelectors('show');
            }

            if (event == "end") {
                //console.log('PlayerjsEvents', event, info);
                @if ($type === 'serial')
                    //console.log('episode end!');
                    const select = document.getElementById("episode-number");
                    const nextIndex = (select.selectedIndex + 1) % select.options.length;
                    select.selectedIndex = nextIndex;
                    select.dispatchEvent(new Event("change"));
                @endif
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
                    // 1% done event
                    let oneperc = durationTime/100;
                    if(!onpercdone && currentTime > oneperc){
                        onpercdone = true;
                        if (typeof gtag !== 'undefined') {
                            gtag('event', '1% of timeline completed', {'event_category': 'Videos'});
                        }
                        $.ajax({
                            type: 'get',
                            url: '/apishow/shows.percent',
                            data: 'percent=p1&domain=' + cdn.player.getVBR() + '&file_id={{ $id }}' + (tgc ? '&tgc=' + tgc : ''),
                            dataType: "html",
                            cache: false,
                            success: function (response) {
                            }
                        });
                        if (window.self !== window.top) {
                            window.parent.postMessage({
                                type: "CDN_PLAYER_EVENT",
                                action: '1% of timeline completed',
                            }, "*");
                        }
                    }
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
            $('#shareBlock').fadeIn('fast');
        }
        clearTimeout(hideTimeout); // prevent hiding if quickly moving between elements
    }
    function hideSelectors() {
        hideTimeout = setTimeout(function () {
            if (!$('#player').is(':hover') && !$('#selectors').is(':hover')) {
                $('#selectors').fadeOut('fast');
                $('#shareBlock').fadeOut('fast');
            }
        }, 200); // 200ms delay to allow moving between elements
    }
    // Bind events to both #player and #selectors
    $('#player, #selectors, #shareBlock').on('mousemove', showSelectors);
    $('#player, #selectors, #shareBlock').on('mouseleave', hideSelectors);

// CURSOR INACTIVITY HIDE CONTROLS
    let inactivityTimer;
    function onInactivity() {
        $('#selectors').fadeOut('fast');
        $('#shareBlock').fadeOut('fast');
    }
    function resetInactivityTimer() {
        clearTimeout(inactivityTimer);
        inactivityTimer = setTimeout(onInactivity, 4000);
    }
    document.addEventListener("mousemove", resetInactivityTimer);
    resetInactivityTimer();

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