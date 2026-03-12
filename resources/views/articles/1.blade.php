<style>
    :root {
        --bg: #f7faf8;
        --bg-soft: #f2f8f5;
        --panel: #ffffff;
        --panel-2: #f6fbf9;
        --text: #0f172b;
        --muted: #808fa4;
        --accent: #00bc7d;
        --accent-soft: #e7faf3;
        --accent-soft-2: #d8f6ec;
        --border: #e4edf1;
        --border-strong: #d7e4e8;
        --ok: #00a56e;
        --warn: #f59e0b;
        --shadow: 0 12px 36px rgba(15, 23, 43, 0.07);
        --shadow-soft: 0 6px 20px rgba(15, 23, 43, 0.05);
        --code: #f6fafc;
        --lavender: #f4f1ff;
        --peach: #fff4eb;
        --sky: #eef8ff;
        --mint: #edfdf7;
    }

    .section__content {
        background: none;
        padding: 0;
        border: 0;
        box-shadow: none;
        -webkit-box-shadow: none;
    }

    .apicontent * {
        box-sizing: border-box;
    }

    .apicontent {
        padding: 0 0 56px;
        width: 100%;
        margin: 0 auto;
        flex-direction: column;
    }

    .apicontent a {
        color: var(--accent);
        text-decoration: none;
    }

    .apicontent a:hover {
        text-decoration: underline;
    }

    .apicontent code {
        font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
    }

    .prosmotr .apicontent code,
    .prosmotr .apicontent pre {
        background-color: unset;
        border-radius: 0;
    }

    .apicontent .hero {
        background: linear-gradient(135deg, #ffffff 0%, #f6fbf8 55%, #eefcf6 100%);
        border: 1px solid var(--border);
        border-radius: 28px;
        padding: 30px;
        box-shadow: var(--shadow);
        margin-bottom: 26px;
        position: relative;
        overflow: hidden;
    }

    .apicontent .hero::after {
        content: "";
        position: absolute;
        inset: auto -60px -60px auto;
        width: 220px;
        height: 220px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(0,188,125,.12) 0%, rgba(0,188,125,0) 70%);
        pointer-events: none;
    }

    .apicontent .stats {
        display: grid;
        grid-template-columns: repeat(1, minmax(0,1fr));
        gap: 14px;
        margin-top: 22px;
    }

    .apicontent .stat {
        background: rgba(255,255,255,.82);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 16px 18px;
        box-shadow: var(--shadow-soft);
    }

    .apicontent .stat strong {
        display: block;
        font-size: 20px;
        margin-bottom: 6px;
        color: var(--text);
        word-break: break-word;
    }

    .apicontent .stat span {
        color: var(--muted);
        font-size: 14px;
    }

    .apicontent .doc-section {
        margin-top: 30px;
    }

    .apicontent .section-head {
        margin-bottom: 16px;
        padding: 0 2px;
    }

    .apicontent .section-head h2 {
        font-size: 28px;
        margin: 0 0 8px;
        color: var(--text);
    }

    .apicontent .section-head p {
        color: var(--muted);
        margin: 0;
        font-size: 15px;
    }

    .apicontent .endpoint-card {
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: 24px;
        padding: 24px;
        margin: 18px 0;
        box-shadow: var(--shadow);
    }

    .apicontent .endpoint-card > summary {
        margin: -24px;
        padding: 24px;
        border-radius: 24px;
    }

    .apicontent .endpoint-card[open] > summary {
        margin-bottom: 14px;
    }

    .apicontent .endpoint-top {
        display: flex;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
        cursor: pointer;
        user-select: none;
        list-style: none;
    }

    .apicontent .endpoint-top::-webkit-details-marker {
        display: none;
    }

    .apicontent .endpoint-top::marker {
        display: none;
        content: "";
    }

    .apicontent .endpoint-top::after {
        content: "▾";
        margin-left: auto;
        color: var(--accent);
        font-size: 18px;
        line-height: 1;
        transition: transform .2s ease;
    }

    .apicontent .endpoint-card[open] .endpoint-top::after {
        transform: rotate(180deg);
    }

    .apicontent .endpoint-top h3 {
        margin: 0;
        font-size: 18px;
        color: var(--text);
    }

    .apicontent .method {
        background: var(--accent-soft);
        color: var(--ok);
        border: 1px solid rgba(0,188,125,.18);
        padding: 7px 12px;
        border-radius: 999px;
        font-weight: 800;
        letter-spacing: .04em;
        font-size: 12px;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,.4);
    }

    .apicontent .urlbox {
        background: var(--code);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 13px 15px;
        overflow: auto;
        margin: 14px 0;
        color: var(--text);
    }

    .apicontent h4 {
        font-size: 16px;
        margin: 20px 0 10px;
        color: var(--text);
    }

    .apicontent table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        overflow: hidden;
        border-radius: 16px;
        border: 1px solid var(--border);
        background: #fff;
        box-shadow: var(--shadow-soft);
    }

    .apicontent th,
    .apicontent td {
        text-align: left;
        padding: 12px 13px;
        border-bottom: 1px solid var(--border);
        vertical-align: top;
    }

    .apicontent th {
        background: linear-gradient(180deg, #fbfefd 0%, #f3faf7 100%);
        font-size: 14px;
        color: var(--text);
    }

    .apicontent td {
        color: var(--muted);
        font-size: 14px;
        background: #fff;
    }

    .apicontent tr:last-child td {
        border-bottom: none;
    }

    .apicontent pre {
        margin: 0;
        white-space: pre-wrap;
        word-break: break-word;
        background: var(--code);
        padding: 16px;
        border-radius: 16px;
        border: 1px solid var(--border);
        overflow: auto;
        color: var(--text);
    }

    .apicontent .response-card {
        margin-top: 12px;
        padding: 16px;
        border: 1px solid var(--border);
        border-radius: 18px;
        background: linear-gradient(180deg, #ffffff 0%, #fbfffd 100%);
    }

    .apicontent .response-head {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        margin-bottom: 10px;
        flex-wrap: wrap;
    }

    .apicontent .status {
        color: var(--warn);
        font-size: 13px;
        background: #fff7e8;
        border: 1px solid #fde7bd;
        padding: 6px 10px;
        border-radius: 999px;
    }

    .apicontent .muted {
        color: var(--muted);
    }

    .apicontent .hero .actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 18px;
    }

    .apicontent .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 11px 16px;
        border-radius: 999px;
        background: var(--accent);
        color: #fff;
        border: 1px solid var(--accent);
        font-weight: 700;
        box-shadow: 0 10px 24px rgba(0,188,125,.18);
    }

    .apicontent .btn.secondary {
        background: #fff;
        color: var(--accent);
        border-color: rgba(0,188,125,.22);
        box-shadow: none;
    }

    @media (max-width: 980px) {
        .apicontent .content {
            padding: 18px 14px 40px;
        }

        .apicontent .stats {
            grid-template-columns: 1fr;
        }

        .apicontent .hero {
            padding: 24px 18px;
        }

        .apicontent .endpoint-card {
            padding: 18px;
        }

        .apicontent .endpoint-card > summary {
            margin: -18px;
            padding: 18px;
            border-radius: 24px;
        }

        .apicontent .endpoint-card[open] > summary {
            margin-bottom: 14px;
        }

        .apicontent .endpoint-top h3 {
            font-size: 20px;
        }
    }
</style>

<div class="apicontent">

    <section class="hero" id="overview">
        <p>Эндпоинты /api/{method} требуют авторизации по API-ключу (token).<br><br>Эндпоинты /api/public/* работают без авторизации.</p>
        <div class="stats">
            <div class="stat"><strong>https://api0.flixcdn.biz/</strong><span>базовый URL</span></div>
            <div class="stat"><strong>{!! $token !!}</strong><span>Ваш API-токен</span></div>
        </div>
        <div class="actions">

        </div>
    </section>

    <section class="doc-section" id="section-1">
        <div class="section-head">
            <h2>Авторизованные эндпоинты</h2>
            <p>Эндпоинты, требующие API-ключ (account_key / token)</p>
        </div>

        <details class="endpoint-card" id="section-1-ep-1">
            <summary class="endpoint-top">
                <span class="method">GET</span>
                <h3>search — Поиск видео</h3>
            </summary>
            <div class="urlbox"><code>https://api0.flixcdn.biz/api/search?token={!! $token !!}&amp;kinopoisk_id=&amp;imdb_id=&amp;title=&amp;type=&amp;offset=0&amp;limit=50&amp;orderby=id&amp;orderby_direction=desc</code></div>
            <p>Поиск видео по Кинопоиск ID, IMDB ID или названию. Приоритет: kinopoisk_id &gt; imdb_id &gt; title. Возвращает обогащённые данные: жанры, страны, актёры, режиссёры, озвучки, скриншоты.</p>
            <h4>Параметры запроса</h4>
            <table>
                <thead>
                <tr><th>Параметр</th><th>Пример</th><th>Описание</th></tr>
                </thead>
                <tbody>
                <tr><td><code>token</code></td><td><code>{!! $token !!}</code></td><td>API-ключ</td></tr>
                <tr><td><code>kinopoisk_id</code></td><td><code></code></td><td>Кинопоиск ID (можно несколько через запятую)</td></tr>
                <tr><td><code>imdb_id</code></td><td><code></code></td><td>IMDB ID (можно несколько через запятую)</td></tr>
                <tr><td><code>title</code></td><td><code></code></td><td>Поиск по названию (русскому или оригинальному)</td></tr>
                <tr><td><code>type</code></td><td><code></code></td><td>Тип: movie, serial, anime, animeserial, showserial</td></tr>
                <tr><td><code>offset</code></td><td><code>0</code></td><td>Смещение (по умолчанию 0)</td></tr>
                <tr><td><code>limit</code></td><td><code>50</code></td><td>Количество результатов (по умолчанию 50, макс 10000)</td></tr>
                <tr><td><code>orderby</code></td><td><code>id</code></td><td>Поле сортировки: id, created_at, updated_at</td></tr>
                <tr><td><code>orderby_direction</code></td><td><code>desc</code></td><td>Направление: desc, asc</td></tr>
                </tbody>
            </table>
            <h4>Примеры ответов</h4>
            <div class="response-card">
                <div class="response-head">
                    <strong>Успешный ответ — поиск по kinopoisk_id</strong>
                    <span class="status">HTTP 200 OK</span>
                </div>
                <pre><code>{
  "request": {
    "type": null,
    "type_help": "one of movie,serial,anime,animeserial,showserial",
    "offset": 0,
    "limit": 50,
    "limit_help": "&lt;=10000",
    "orderby": "id",
    "orderby_help": "id,created_at,updated_at",
    "orderby_direction": "desc",
    "orderby_direction_help": "desc,asc",
    "kinopoisk_id": "1234567",
    "imdb_id": null,
    "title": null
  },
  "prev": null,
  "next": null,
  "result": [
    {
      "id": 100,
      "created_at": "2024-01-15 12:00:00",
      "updated_at": "2024-06-20 08:30:00",
      "type": "movie",
      "title_orig": "The Movie Title",
      "title_rus": "Название фильма",
      "quality": "WEB-DLRip",
      "year": "2024",
      "kinopoisk_id": "1234567",
      "imdb_id": "tt1234567",
      "description": "Описание фильма...",
      "poster": "https://img.cdnhubstream.pro/...",
      "backdrop": "https://img.cdnhubstream.pro/...",
      "tmdb_popularity": 45.6,
      "tmdb_vote_average": 7.2,
      "tmdb_vote_count": 1500,
      "duration": 120,
      "slogan": "Слоган фильма",
      "rating_kp": "7.5",
      "rating_kp_votes": "25000",
      "age": "16+",
      "iframe_url": "https://cdn0.cdnhubstream.pro/show/100",
      "genres": ["боевик", "фантастика"],
      "countries": ["США"],
      "actors": [
        {
          "name_ru": "Имя Актёра",
          "name_en": "Actor Name",
          "character_name": "Персонаж",
          "poster_url": "https://img.cdnhubstream.pro/..."
        }
      ],
      "directors": [
        {
          "name_ru": "Имя Режиссёра",
          "name_en": "Director Name",
          "poster_url": "https://img.cdnhubstream.pro/..."
        }
      ],
      "translations": [
        {
          "id": 1,
          "title": "Дубляж",
          "screens": []
        }
      ]
    }
  ]
}</code></pre>
            </div>
        </details>

        <details class="endpoint-card" id="section-1-ep-2">
            <summary class="endpoint-top">
                <span class="method">GET</span>
                <h3>getVideo — Получение списка видео</h3>
            </summary>
            <div class="urlbox"><code>https://api0.flixcdn.biz/api/getVideo?token={!! $token !!}&amp;search=&amp;genres=&amp;countries=&amp;years=&amp;type=&amp;kino_poisk=&amp;lock=&amp;offset=0&amp;limit=200</code></div>
            <p>Получение списка видео с фильтрацией по жанрам, странам, годам и типу. Возвращает полные данные с жанрами, странами, актёрами, режиссёрами и озвучками.</p>
            <h4>Параметры запроса</h4>
            <table>
                <thead>
                <tr><th>Параметр</th><th>Пример</th><th>Описание</th></tr>
                </thead>
                <tbody>
                <tr><td><code>token</code></td><td><code>{!! $token !!}</code></td><td>API-ключ</td></tr>
                <tr><td><code>search</code></td><td><code></code></td><td>Поиск по русскому названию или Кинопоиск ID</td></tr>
                <tr><td><code>genres</code></td><td><code></code></td><td>Фильтр по жанрам (через запятую, все должны совпасть)</td></tr>
                <tr><td><code>countries</code></td><td><code></code></td><td>Фильтр по странам (через запятую)</td></tr>
                <tr><td><code>years</code></td><td><code></code></td><td>Диапазон годов: 2020,2024</td></tr>
                <tr><td><code>type</code></td><td><code></code></td><td>Внутренний тип: movie, episode</td></tr>
                <tr><td><code>kino_poisk</code></td><td><code></code></td><td>Кинопоиск ID через запятую (до 10)</td></tr>
                <tr><td><code>lock</code></td><td><code></code></td><td>Фильтр блокировки: yes — только заблокированные</td></tr>
                <tr><td><code>offset</code></td><td><code>0</code></td><td>Смещение (по умолчанию 0)</td></tr>
                <tr><td><code>limit</code></td><td><code>200</code></td><td>Количество (по умолчанию 200, максимум 200)</td></tr>
                </tbody>
            </table>
            <h4>Примеры ответов</h4>
            <div class="response-card">
                <div class="response-head">
                    <strong>Успешный ответ</strong>
                    <span class="status">HTTP 200 OK</span>
                </div>
                <pre><code>{
  "data": {
    "method": "getVideo",
    "count": 1500,
    "items": [
      {
        "id": 100,
        "tupe": "movie",
        "name": "The Movie Title",
        "ru_name": "Название фильма",
        "quality": "WEB-DLRip",
        "year": "2024",
        "kinopoisk": "1234567",
        "imdb": "tt1234567",
        "description": "Описание фильма...",
        "img": "https://img.cdnhubstream.pro/...",
        "backdrop": "https://img.cdnhubstream.pro/...",
        "adress": "https://cdn0.cdnhubstream.pro/show/100",
        "genre": "боевик, фантастика",
        "country": "США",
        "translations": [
          {"id": 1, "title": "Дубляж", "tag": "dubbing"}
        ],
        "actors": [
          {"name_ru": "Имя Актёра", "name_en": "Actor Name", "character_name": "Персонаж", "poster_url": "https://img.cdnhubstream.pro/..."}
        ],
        "directors": [
          {"name_ru": "Имя Режиссёра", "name_en": "Director Name", "poster_url": "https://img.cdnhubstream.pro/..."}
        ]
      }
    ],
    "genres": [{"id": 1, "name": "боевик"}],
    "countries": [{"id": 1, "name": "США"}],
    "messages": []
  },
  "messages": []
}</code></pre>
            </div>
        </details>

        <details class="endpoint-card" id="section-1-ep-3">
            <summary class="endpoint-top">
                <span class="method">GET</span>
                <h3>translations — Список озвучек</h3>
            </summary>
            <div class="urlbox"><code>https://api0.flixcdn.biz/api/translations?token={!! $token !!}</code></div>
            <p>Получение полного списка доступных озвучек/переводов. Если у озвучки задан tag, он используется вместо title.</p>
            <h4>Параметры запроса</h4>
            <table>
                <thead>
                <tr><th>Параметр</th><th>Пример</th><th>Описание</th></tr>
                </thead>
                <tbody>
                <tr><td><code>token</code></td><td><code>{!! $token !!}</code></td><td>API-ключ</td></tr>
                </tbody>
            </table>
            <h4>Примеры ответов</h4>
            <div class="response-card">
                <div class="response-head">
                    <strong>Успешный ответ</strong>
                    <span class="status">HTTP 200 OK</span>
                </div>
                <pre><code>{
  "result": [
    {"id": 1, "title": "Дубляж"},
    {"id": 2, "title": "Оригинал (+субтитры)"},
    {"id": 3, "title": "Многоголосый"}
  ]
}</code></pre>
            </div>
        </details>

        <details class="endpoint-card" id="section-1-ep-4">
            <summary class="endpoint-top">
                <span class="method">GET</span>
                <h3>genres — Список жанров</h3>
            </summary>
            <div class="urlbox"><code>https://api0.flixcdn.biz/api/genres?token={!! $token !!}</code></div>
            <p>Получение полного списка жанров.</p>
            <h4>Параметры запроса</h4>
            <table>
                <thead>
                <tr><th>Параметр</th><th>Пример</th><th>Описание</th></tr>
                </thead>
                <tbody>
                <tr><td><code>token</code></td><td><code>{!! $token !!}</code></td><td>API-ключ</td></tr>
                </tbody>
            </table>
            <h4>Примеры ответов</h4>
            <div class="response-card">
                <div class="response-head">
                    <strong>Успешный ответ</strong>
                    <span class="status">HTTP 200 OK</span>
                </div>
                <pre><code>{
  "result": [
    {"id": 1, "name": "боевик"},
    {"id": 2, "name": "комедия"},
    {"id": 3, "name": "драма"}
  ]
}</code></pre>
            </div>
        </details>

        <details class="endpoint-card" id="section-1-ep-5">
            <summary class="endpoint-top">
                <span class="method">GET</span>
                <h3>updates — Последние обновления</h3>
            </summary>
            <div class="urlbox"><code>https://api0.flixcdn.biz/api/updates?token={!! $token !!}&amp;force_rebuild=</code></div>
            <p>Получение последних обновлений контента (новые фильмы и серии). Результат кешируется на 1 час.</p>
            <h4>Параметры запроса</h4>
            <table>
                <thead>
                <tr><th>Параметр</th><th>Пример</th><th>Описание</th></tr>
                </thead>
                <tbody>
                <tr><td><code>token</code></td><td><code>{!! $token !!}</code></td><td>API-ключ</td></tr>
                <tr><td><code>force_rebuild</code></td><td><code></code></td><td>Если указан — принудительно обновить кеш</td></tr>
                </tbody>
            </table>
            <h4>Примеры ответов</h4>
            <div class="response-card">
                <div class="response-head">
                    <strong>Успешный ответ</strong>
                    <span class="status">HTTP 200 OK</span>
                </div>
                <pre><code>{
  "result": {
    "movies": [
      {
        "update_id": 50000,
        "created": "2024-06-20 12:00:00",
        "translation": {"id": 1, "title": "Дубляж"},
        "content": {
          "id": 100,
          "type": "movie",
          "title_orig": "The Movie Title",
          "title_rus": "Название фильма",
          "year": "2024",
          "description": "Описание...",
          "poster": "https://img.cdnhubstream.pro/...",
          "backdrop": "https://img.cdnhubstream.pro/...",
          "quality": "WEB-DLRip",
          "iframe_url": "https://cdn0.cdnhubstream.pro/show/100",
          "genres": ["боевик"],
          "countries": ["США"],
          "translations": [{"id": 1, "title": "Дубляж"}]
        }
      }
    ],
    "serials": [
      {
        "update_id": 50001,
        "created": "2024-06-20 14:00:00",
        "translation": {"id": 3, "title": "Субтитры"},
        "season": 2,
        "episode": 5,
        "content": {
          "id": 200,
          "type": "serial",
          "title_orig": "The Series",
          "title_rus": "Сериал",
          "year": "2023",
          "quality": "WEB-DLRip",
          "iframe_url": "https://cdn0.cdnhubstream.pro/show/200",
          "genres": ["драма"],
          "translations": [{"id": 3, "title": "Субтитры", "season": 2, "episode": 5}],
          "season": 2,
          "episode": 5
        }
      }
    ]
  }
}</code></pre>
            </div>
        </details>

        <details class="endpoint-card" id="section-1-ep-6">
            <summary class="endpoint-top">
                <span class="method">GET</span>
                <h3>kpids — Список Кинопоиск ID</h3>
            </summary>
            <div class="urlbox"><code>https://api0.flixcdn.biz/api/kpids?token={!! $token !!}&amp;type=movies</code></div>
            <p>Получение массива всех уникальных Кинопоиск ID по типу контента.</p>
            <h4>Параметры запроса</h4>
            <table>
                <thead>
                <tr><th>Параметр</th><th>Пример</th><th>Описание</th></tr>
                </thead>
                <tbody>
                <tr><td><code>token</code></td><td><code>{!! $token !!}</code></td><td>API-ключ</td></tr>
                <tr><td><code>type</code></td><td><code>movies</code></td><td>Тип контента: movies или serials</td></tr>
                </tbody>
            </table>
            <h4>Примеры ответов</h4>
            <div class="response-card">
                <div class="response-head">
                    <strong>Успешный ответ — movies</strong>
                    <span class="status">HTTP 200 OK</span>
                </div>
                <pre><code>{
  "status": "success",
  "data": [1234567, 7654321, 9876543]
}</code></pre>
            </div>
            <div class="response-card">
                <div class="response-head">
                    <strong>Успешный ответ — serials</strong>
                    <span class="status">HTTP 200 OK</span>
                </div>
                <pre><code>{
  "status": "success",
  "data": [111222, 333444]
}</code></pre>
            </div>
        </details>
    </section>

    <section class="doc-section" id="section-2">
        <div class="section-head">
            <h2>Публичные эндпоинты (без авторизации)</h2>
            <p>Эндпоинты, не требующие API-ключ</p>
        </div>

        <details class="endpoint-card" id="section-2-ep-1">
            <summary class="endpoint-top">
                <span class="method">GET</span>
                <h3>actors — Список актёров</h3>
            </summary>
            <div class="urlbox"><code>https://api0.flixcdn.biz/api/public/actors?page=1&amp;limit=50</code></div>
            <p>Пагинированный список актёров с привязкой к видео. Авторизация не требуется.</p>
            <h4>Параметры запроса</h4>
            <table>
                <thead>
                <tr><th>Параметр</th><th>Пример</th><th>Описание</th></tr>
                </thead>
                <tbody>
                <tr><td><code>page</code></td><td><code>1</code></td><td>Номер страницы (по умолчанию 1)</td></tr>
                <tr><td><code>limit</code></td><td><code>50</code></td><td>Количество на странице (по умолчанию 50, максимум 500)</td></tr>
                </tbody>
            </table>
            <h4>Примеры ответов</h4>
            <div class="response-card">
                <div class="response-head">
                    <strong>Успешный ответ</strong>
                    <span class="status">HTTP 200 OK</span>
                </div>
                <pre><code>{
  "request": {"page": 1, "limit": 2},
  "prev": null,
  "next": {"page": 2, "limit": 2},
  "result": [
    {
      "id": 1,
      "kinopoisk_id": "12345",
      "name_ru": "Имя Актёра",
      "name_en": "Actor Name",
      "poster_url": "https://img.cdnhubstream.pro/...",
      "videos": [{"id": 100, "character_name": "Персонаж"}]
    }
  ]
}</code></pre>
            </div>
        </details>

        <details class="endpoint-card" id="section-2-ep-2">
            <summary class="endpoint-top">
                <span class="method">GET</span>
                <h3>directors — Список режиссёров</h3>
            </summary>
            <div class="urlbox"><code>https://api0.flixcdn.biz/api/public/directors?page=1&amp;limit=50</code></div>
            <p>Пагинированный список режиссёров с привязкой к видео (массив ID). Авторизация не требуется.</p>
            <h4>Параметры запроса</h4>
            <table>
                <thead>
                <tr><th>Параметр</th><th>Пример</th><th>Описание</th></tr>
                </thead>
                <tbody>
                <tr><td><code>page</code></td><td><code>1</code></td><td>Номер страницы (по умолчанию 1)</td></tr>
                <tr><td><code>limit</code></td><td><code>50</code></td><td>Количество на странице (по умолчанию 50, максимум 500)</td></tr>
                </tbody>
            </table>
            <h4>Примеры ответов</h4>
            <div class="response-card">
                <div class="response-head">
                    <strong>Успешный ответ</strong>
                    <span class="status">HTTP 200 OK</span>
                </div>
                <pre><code>{
  "request": {"page": 1, "limit": 2},
  "prev": null,
  "next": {"page": 2, "limit": 2},
  "result": [
    {
      "id": 1,
      "kinopoisk_id": "67890",
      "name_ru": "Имя Режиссёра",
      "name_en": "Director Name",
      "poster_url": "https://img.cdnhubstream.pro/...",
      "videos": [100, 200, 300]
    }
  ]
}</code></pre>
            </div>
        </details>
    </section>

    <section class="doc-section" id="section-3">
        <div class="section-head">
            <h2>Ошибки авторизации</h2>
            <p>Примеры ошибок при неправильной авторизации</p>
        </div>

        <details class="endpoint-card" id="section-3-ep-1">
            <summary class="endpoint-top">
                <span class="method">GET</span>
                <h3>Без токена</h3>
            </summary>
            <div class="urlbox"><code>https://api0.flixcdn.biz/api/search</code></div>
            <p>Запрос без API-ключа — вернёт ошибку.</p>
            <h4>Параметры запроса</h4>
            <p class="muted">Параметры не указаны.</p>
            <h4>Примеры ответов</h4>
            <div class="response-card">
                <div class="response-head">
                    <strong>Ошибка — нет токена</strong>
                    <span class="status">HTTP 200 OK</span>
                </div>
                <pre><code>{
  "messages": [
    {"tupe": "error", "message": "Не указанн токен доступа"}
  ]
}</code></pre>
            </div>
        </details>

        <details class="endpoint-card" id="section-3-ep-2">
            <summary class="endpoint-top">
                <span class="method">GET</span>
                <h3>Неверный токен</h3>
            </summary>
            <div class="urlbox"><code>https://api0.flixcdn.biz/api/search?token=invalid_key</code></div>
            <p>Запрос с невалидным API-ключом.</p>
            <h4>Параметры запроса</h4>
            <table>
                <thead>
                <tr><th>Параметр</th><th>Пример</th><th>Описание</th></tr>
                </thead>
                <tbody>
                <tr><td><code>token</code></td><td><code>invalid_key</code></td><td></td></tr>
                </tbody>
            </table>
            <h4>Примеры ответов</h4>
            <div class="response-card">
                <div class="response-head">
                    <strong>Ошибка — пользователь не найден</strong>
                    <span class="status">HTTP 200 OK</span>
                </div>
                <pre><code>{
  "messages": [
    {"tupe": "error", "message": "Юзер отсутствует"}
  ]
}</code></pre>
            </div>
        </details>
    </section>

    <br>
    <br>
</div>