<!doctype html>
<html lang="ru" dir="ltr">
<head>
	<meta charset="utf-8">
	<meta name="robots" content="noindex">
	<meta name="referrer" content="always">
	<meta name="viewport" content="user-scalable=0, initial-scale=1.0, maximum-scale=1.0, width=device-width">
	<title>player 1.0</title>
	<link rel="stylesheet" href="/player/css/player.css">
	<script src="/player/js/jquery.min.js"></script>
	<script src="/player/js/jquery.nice-select.min.js"></script>
	<script src="/player/js/player.js"></script>
	<script src="/player/js/hls.js"></script>

	<!-- Google tag (gtag.js) -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=G-QG08LXZ7MT"></script>
	<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){dataLayer.push(arguments);}
	gtag('js', new Date());
	gtag('config', 'G-QG08LXZ7MT');
	</script>
</head>
<body>

	<div id="selectors" class="video_selectors" style="display: block;">
	
		@if ($type === 'serial')

			<span<?php echo (isset($_GET['no_controls']) || isset($_GET['no_control_seasons']) || isset($_GET['no_control_episodes'])) ? ' style="display:none;"' : ' style="display: inline-block;"'; ?>>
				<select name="season" id="season-number" data-select="1">
					@foreach ($seasons as $_season)
					<option value="{{ $_season }}"@if ($season && $season == $_season) selected="selected" readonly="readonly"@endif>Сезон {{ $_season }}</option>
					@endforeach
				</select>
			</span>

			<span<?php echo (isset($_GET['no_controls']) || isset($_GET['no_control_episodes'])) ? ' style="display:none;"' : ' style="display: inline-block;"'; ?>>
				<select name="episode" id="episode-number" data-select="1">
					@foreach ($episodes as $_episode)
					<option value="{{ $_episode }}"@if ($episode && $episode == $_episode) selected="selected" readonly="readonly"@endif>Серия {{ $_episode }}</option>
					@endforeach
				</select>
			</span>

		@endif

		@if ($translations)
		<span<?php echo (isset($_GET['no_controls']) || isset($_GET['no_control_translations'])) ? ' style="display:none;"' : ' style="display: inline-block;"'; ?>>
			<select name="translator" id="translator-name" data-select="1">
				@foreach ($translations as $translation)
				<option value="{{ $translation['id'] }}"@if ($translate && $translate == $translation['id']) selected="selected" readonly="readonly"@endif>{{ $translation['title'] }}</option>
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
			iframeHostname = window.location.hostname,
			iframeVastKey = '',
			iframeVastValue = {'p': 0, 'm': 0};

			var getCDNplayerCUID = function () {
				return 'kh{{ $id }}';
			}

			pub.controlSelectors = function (event) {
				var is_hidden = 0;

				if (is_hidden == 1)
					return;

				if (event == 'show') {
					$('#selectors').stop(true,true).fadeIn(100);
				} else {
					$('#selectors').stop(true,true).fadeOut(100);
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

			var setSave = function () {
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
				} catch(e) {};

				return false;
			}

			var removeSave = function () {
				var _key = "save-" + getCDNplayerCUID();

				try {
					return localStorage.removeItem(_key);
				} catch(e) {};

				return true;
			}

			var getSave = function () {
				var is_hidden = 0;

				if (is_hidden == 1)
					return;

				var _key = "save-" + getCDNplayerCUID();

				try {
					return localStorage.getItem(_key);
				} catch(e) {};

				return null;
			}

			var setItem = function (k, v) {
				try {
					localStorage.setItem(k, v);

					return true;
				} catch(e) {};

				return false;
			}

			var getItem = function (k) {
				try {
					return localStorage.getItem(k);
				} catch(e) {};

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
				} catch (e) {};

				if (CDNplayer === null) {
					// console.log(CDNplayerConfig);
					CDNplayer = new Playerjs(CDNplayerConfig);
				}
			}

			var lns = [];

			PlayerjsEvents = function (event, id, info) {
				console.log(event, id, info);

				if (event == "init") {
					if (CDNplayer.api('adblock')) {
						window.abc = true;
					}
				}

				if (event == 'subtitle') {
					var cc = document.getElementById('player_control_cc_icon0'),
					cl = 'none',
					arr,
					ln = ((lns[info] !== undefined) ? lns[info] : "");

					if (ln == '') {
						arr = cc.className.split(" ");

						if (arr.indexOf(cl) == -1) {
							cc.className += ' '+ cl;
						}
					} else {
						cc.className = cc.className.replace(/\bnone\b/g, "");
					}

					cc.setAttribute('data-content', ln.replace(/\-\d+/i, ''));
				}

				if (event == "play") {
					//
				}

				if (event == "play" || event == "start" || event == "vast_init") {
					pub.controlSelectors('hide');

					$('#save-holder').remove();
				}

				if (event == "start") {
					$.ajax({
						type: 'get',
						url: '/apishow/shows.show',
						data: 'domain=' + pub.getVBR() + (tgc ? '&tgc=' + tgc : ''),
						dataType: "html",
						cache: false,
						success: function (response) {}
					});
				}

				if (event == "pause" || event == "end") {
					pub.controlSelectors('show');
				}

				if (event == "end") {
					$.ajax({
						type: 'get',
						url: '/apishow/shows.fullshow',
						data: 'domain=' + pub.getVBR() + (tgc ? '&tgc=' + tgc : ''),
						dataType: "html",
						cache: false,
						success: function (response) {}
					});
				}

				if (event == "new") {
					//
				}

				if (event == "time") {
					if (info > 0 && CDNplayer.api('duration') > 0) {
						currentTime = info;
						durationTime = CDNplayer.api('duration');

						setSave();
					}
				}

				if (event == "reload") {
					//
				}

				if (event == "vast_load") {
					if (info == "preroll") {
						iframeVastKey = 'p';
					} else if (info == "midroll") {
						iframeVastKey = 'm';
					}

					if (typeof iframeVastValue[iframeVastKey] != 'undefined') {
						iframeVastValue[iframeVastKey]++;
					}
				}

				if (event == "vast_complete" || event == "vast_skip") {
					if (typeof iframeVastValue[iframeVastKey] != 'undefined') {
						var matches = $.parseJSON(info).url.match(/khtag=([0-9]+)/i);
						var ad_id = matches[1];

						$.ajax({
							type: 'get',
							url: '/apishow/shows.showsAd',
							data: 'domain='+ pub.getVBR() + '&id=' + ad_id + (tgc ? '&tgc=' + tgc : ''),
							dataType: "html",
							cache: false,
							success: function (response) {}
						});
					}
				}
			}

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

							if (_cstop > 0 && durationVideo > 0 && Math.ceil(_save.time*100/durationVideo) >= _cstop) {
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

								$('#save-holder').css({'margin-left': -1 * $('#save-holder').width() / 2, 'left': '50%'}).show();
							}
						} catch (e) {};
					}
				}
			}

			/* player config */
			CDNquality = getItem('pljsquality');

			var CDNplayerConfig = {
				'id': 'player',
				'cuid': getCDNplayerCUID(),
				'poster': null,
				'file': '{{ $file }}',
				'default_quality': ((CDNquality !== null) ? CDNquality : '480p'),
				'subtitle': false,

				@if ($preroll)
				<?php // 'preroll': 'https://franecki.net/assets/vendor/bd01f9fd2f98c883c2c1656c64eaa488.xml?v=3.0&amp;external_subid=(host)', ?>
				'preroll': '<?php echo $preroll; ?>',
				@endif

				@if ($midroll)
				'midroll': <?php echo json_encode($midroll); ?>,
				@endif

				'hlsconfig': {
					// 'maxBufferLength': 60, // 180
					// 'maxBufferSize': 33554432000,
					// 'enableSoftwareAES': true,
					// 'progressive': true,
					'startFragPrefetch': true,
			    'enableWorker': true,
			    'fragLoadingRetryDelay': 500,
			    'maxBufferHole': 1,
			    'maxBufferLength': 30,
			    // Может помочь при ошибках seek:
			    'maxFragLookUpTolerance': 0.1
				},
				'hlsdebug': 0,
				'debug': 0,
				'ready': PlayerReady(),
				'autoplay': CDNautoplay,
				'start': CDNstart
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
							console.log(episodes);
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

		function PlayerjsEvents(event,id,info){
			if(event=="vast_Impression"){
				// VAST AD VIDEO start
				console.log(info);
			}
			if(event=="vast_finish"){
				// VAST AD VIDEO finished
				console.log(info);
			}
		}

		if (typeof gtag !== 'undefined') {
			function PlayerjsEvents(event,id,info){

				if (event == "loaderror") {
					// console.log(event,id,info);
					gtag('event', 'Video load error', {'event_category': 'Videos'});
				}
				if (event == "play") {
					// console.log(event,id,info);
					gtag('event', 'Video start play', {'event_category': 'Videos'});
				}
				if (event == "pause") {
					// console.log(event,id,info);
					gtag('event', 'Video paused', {'event_category': 'Videos'});
				}
				if (event == "quartile") {
					// console.log(event,id,info);
					gtag('event', info + ' of timeline completed', {'event_category': 'Videos'});

				}
				if (event == "line") {
					// console.log(event,id,info);
					gtag('event', 'Video rewind(fwd/rwd)', {'event_category': 'Videos'});
				}
			}
		}

	</script>

	<!-- Yandex.Metrika counter -->
	<script type="text/javascript" >
		var yaParams = { ip: "<?php echo $_SERVER['HTTP_X_FORWARDED_FOR']; ?>" };

		(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
		m[i].l=1*new Date();k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
		(window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

		ym(70538995, "init", {
			clickmap:true,
			trackLinks:true,
			accurateTrackBounce:true,
			webvisor:true,

			params: window.yaParams
		});
	</script>
	<noscript><div><img src="https://mc.yandex.ru/watch/70538995" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
	<!-- /Yandex.Metrika counter -->
	<script src="/player/js/cdnhubevents.js"></script>	
</body>
</html>