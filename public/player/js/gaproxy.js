// GA proxy
(function () {
    const PROXY_ENDPOINT = '/apishow/shows.gaproxy'; 
    const LS_CID_KEY = 'ga4_cid';
    const COOKIE_CID = '_ga4cid';
    const PAGE_PROPS = () => ({
        page_location: location.href,
        page_title: document.title,
        page_referrer: document.referrer || undefined
    });

    // ---- CID helpers ----
    function readCookie(name) {
        const m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([$?*|{}\]\\\/\+\^])/g,'\\$1') + '=([^;]*)'));
        return m ? decodeURIComponent(m[1]) : null;
    }
    function writeCookie(name, val, days=365) {
        const exp = new Date(Date.now() + days*864e5).toUTCString();
        document.cookie = `${name}=${encodeURIComponent(val)}; Expires=${exp}; Path=/; SameSite=Lax`;
    }
    function genCid() {
        return 'cid.' + Math.random().toString(36).slice(2) + '.' + Date.now();
    }
    function getClientId() {
        let cid = localStorage.getItem(LS_CID_KEY) || readCookie(COOKIE_CID);
        if (!cid) {
        cid = genCid();
        try { localStorage.setItem(LS_CID_KEY, cid); } catch(e) {}
        try { writeCookie(COOKIE_CID, cid); } catch(e) {}
        }
        return cid;
    }

    // ---- State (имитация gtag config) ----
    const state = {
        measurementId: null,
        user_id: null,
        session_id: null,
        user_properties: {},   // { key: {value: '...'} }
        consent: { ad_user_data: 'granted', ad_personalization: 'granted', ad_storage: 'granted', analytics_storage: 'granted' },
    };

    // ---- Отправка на прокси ----
    function sendToProxy(payload) {
        const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
        if (navigator.sendBeacon) {
        navigator.sendBeacon(PROXY_ENDPOINT, blob);
        return;
        }
        fetch(PROXY_ENDPOINT, { method: 'POST', body: JSON.stringify(payload), headers: {'Content-Type':'application/json'} })
        .catch(()=>{});
    }

    // ---- Маппер gtag('event') → MP ----
    function emitEvent(name, params = {}) {
        // уважим консент: если analytics_storage=denied — просто не шлём
        if (state.consent && state.consent.analytics_storage === 'denied') return;

        const client_id = getClientId();

        // user_properties: из state + из params.user_properties (если передали)
        const user_properties = Object.assign({}, state.user_properties);
        if (params.user_properties && typeof params.user_properties === 'object') {
        Object.keys(params.user_properties).forEach(k => {
            const v = params.user_properties[k];
            user_properties[k] = (v && typeof v === 'object' && 'value' in v) ? v : { value: v };
        });
        delete params.user_properties;
        }

        // Добавим page_* для page_view, если их не передали
        if (name === 'page_view') {
        const pp = PAGE_PROPS();
        params.page_location = params.page_location || pp.page_location;
        params.page_title    = params.page_title    || pp.page_title;
        if (!params.page_referrer && pp.page_referrer) params.page_referrer = pp.page_referrer;
        }

        // user_id/session_id из state, если не пришли в params
        const _user_id = params.user_id || state.user_id || undefined;
        const _session_id = params.session_id || state.session_id || undefined;
        if (_user_id) delete params.user_id;
        if (_session_id) delete params.session_id;

        const mp = {
        client_id,
        // не обязательно, но полезно:
        user_id: _user_id,
        user_properties: Object.keys(user_properties).length ? user_properties : undefined,
        events: [{
            name,
            params: Object.assign({}, params, _session_id ? { session_id: String(_session_id) } : null)
        }]
        };

        // Желательно батчить — здесь для простоты единичные запросы
        sendToProxy(mp);
    }

    // ---- Полифил gtag ----
    const originalGtag = window.gtag;
    const queue = [];

    function process(cmd, a, b) {
        try {
        switch (cmd) {
            case 'js': // gtag('js', new Date());
            return;
            case 'config': {
            // gtag('config', 'G-XXXX', { user_id, session_id, user_properties, consent })
            const mid = a;
            state.measurementId = mid;
            const opts = b || {};
            if (opts.user_id) state.user_id = opts.user_id;
            if (opts.session_id) state.session_id = opts.session_id;
            if (opts.user_properties && typeof opts.user_properties === 'object') {
                Object.keys(opts.user_properties).forEach(k => {
                const v = opts.user_properties[k];
                state.user_properties[k] = (v && typeof v === 'object' && 'value' in v) ? v : { value: v };
                });
            }
            if (opts.consent && typeof opts.consent === 'object') {
                state.consent = Object.assign({}, state.consent, opts.consent);
            }
            // Часто при config хотят отправить page_view:
            if (opts.send_page_view !== false) {
                emitEvent('page_view', {  });
            }
            return;
            }
            case 'consent': {
            // gtag('consent','update',{ analytics_storage: 'granted'|'denied', ... })
            if (a === 'update' && b && typeof b === 'object') {
                state.consent = Object.assign({}, state.consent, b);
            }
            return;
            }
            case 'set': {
            // gtag('set', { user_id, session_id, user_properties })
            if (a && typeof a === 'object') {
                if (a.user_id) state.user_id = a.user_id;
                if (a.session_id) state.session_id = a.session_id;
                if (a.user_properties && typeof a.user_properties === 'object') {
                Object.keys(a.user_properties).forEach(k => {
                    const v = a.user_properties[k];
                    state.user_properties[k] = (v && typeof v === 'object' && 'value' in v) ? v : { value: v };
                });
                }
            }
            return;
            }
            case 'event': {
            // gtag('event','name', {params})
            emitEvent(a, b || {});
            return;
            }
            default:
            // игнорируем прочее
            return;
        }
        } catch(e) {
        // мягко промолчим, чтобы не ломать страницу
        // console.warn('gtag proxy error', e);
        }
    }

    function gtagShim() {
        const args = Array.prototype.slice.call(arguments);
        if (!args.length) return;
        const [cmd, a, b] = args;
        process(cmd, a, b);
        // При желании можно дублировать в оригинальный gtag (если он нужен):
        // if (typeof originalGtag === 'function') originalGtag.apply(null, args);
    }

    // Если до нас уже успел накинуться window.dataLayer → протащим накопленные вызовы
    window.dataLayer = window.dataLayer || [];
    if (typeof originalGtag === 'function' && originalGtag.hasOwnProperty('q')) {
        // gtag snippet mode
        (originalGtag.q || []).forEach(args => queue.push(args));
    }
    window.gtag = gtagShim;
    queue.forEach(args => gtagShim.apply(null, args));

    // Если раньше вы делали window.gtag=window.gtag||function(){dataLayer.push(arguments)}
    // — просто подключите этот файл ПОСЛЕ такого снпиппета, мы «перехватим» вызовы.
})();