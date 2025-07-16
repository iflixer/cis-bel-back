<style>

    .table {
        width: 100%;
        margin-bottom: 5px;
        margin-top: 5px;
        border: 1px solid #dddddd;
        border-collapse: collapse; 
    }
    .table th {
        font-weight: bold;
        padding: 8px;
        background: #efefef;
        border: 1px solid #dddddd;
        text-align: left;
    }
    .table td {
        border: 1px solid #dddddd;
        padding: 8px;
    }

    .html-syntax {
        padding: 8px !important;
    }

    code {
        font-size: 14px !important;
    }

</style>

<h3>Параметры</h3>
<table class="table">
    <thead>
        <tr>
            <th>Название</th>
            <th>Описание</th>
            <th>Тип</th>
            <th>Доступные значения</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>title</td>
            <td>Поиск по заголовку</td>
            <td>Строка</td>
            <td>Любое значение</code></td>
        </tr>
        <tr>
            <td>kinopoisk_id</td>
            <td>Кинопоиск ID</td>
            <td>Число</td>
            <td><code>1</code> - <code>999999999</code></td>
        </tr>
        <tr>
            <td>imdb_id</td>
            <td>Imdb ID</td>
            <td>Строка</td>
            <td><code>tt1</code> - <code>tt999999999</code></td>
        </tr>
        <tr>
            <td>offset</td>
            <td>Позиция получения данных с нашей базы</td>
            <td>Число</td>
            <td><code>0</code> - <code>999999999</code></td>
        </tr>
        <tr>
            <td>limit</td>
            <td>Ограничение кол-ва результатов в одном запросе</td>
            <td>Число</td>
            <td><code>1</code> - <code>200</code></td>
        </tr>
    </tbody>
</table>

<br>

<h3>Стуктура объекта</h3>
<table class="table">
    <thead>
        <tr>
            <th>Название</th>
            <th>Описание</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>id</td>
            <td>Уникальный идентификатор материала</td>
        </tr>
        <tr>
            <td>title_rus</td>
            <td>Название на Русском</td>
        </tr>
        <tr>
            <td>title_orig</td>
            <td>Оригинальное название</td>
        </tr>
        <tr>
            <td>iframe_url</td>
            <td>Ссылка для вывода плеера</td>
        </tr>
        <tr>
            <td>year</td>
            <td>Год выпуска</td>
        </tr>
        <tr>
            <td>kinopoisk_id</td>
            <td>Кинопоиск ID</td>
        </tr>
        <tr>
            <td>imdb_id</td>
            <td>Imdb ID</td>
        </tr>
        <tr>
            <td>type</td>
            <td>Тип материала</td>
        </tr>
        <tr>
            <td>quality</td>
            <td>Качество видео</td>
        </tr>
        <tr>
            <td>translations</td>
            <td>Объект содержащий <code>id</code> перевода, <code>title</code> (название), <code>season</code> (номер последнего сезона для данного перевода), <code>episode</code> (номер последней серии для данного перевода)</td>
        </tr>
        <tr>
            <td>season</td>
            <td>Номер последнего сезона</td>
        </tr>

        <tr>
            <td>episode</td>
            <td>Номер последней серии</td>
        </tr>
        <tr>
            <td>created_at</td>
            <td>Дата создания материала</td>
        </tr>
        <tr>
            <td>duration</td>
            <td>Продолжительность видео</td>
        </tr>
        <tr>
            <td>description</td>
            <td>Описание</td>
        </tr>
        <tr>
            <td>slogan</td>
            <td>Слоган</td>
        </tr>
        <tr>
            <td>countries</td>
            <td>Объект содержащий список стран</td>
        </tr>
        <tr>
            <td>genres</td>
            <td>Объект содержащий список жанров</td>
        </tr>
        <tr>
            <td>age</td>
            <td>Возрастное ограничение</td>
        </tr>
        <tr>
            <td>poster</td>
            <td>Ссылка на постер</td>
        </tr>
    </tbody>
</table>

<br>

<h3>Примеры использования</h3>
<blockquote style="margin-bottom:10px;background-color:#f8f9fa;padding:5px 10px">
    <code style="display:inline-block;"><a href="https://futmax.info/api/search?token={!! $token !!}" target="_blank">https://futmax.info/api/search?token={!! $token !!}</a></code>
    <pre class="html-syntax">
{
    "prev": null,
    "result": [
        {
            "id": 64186,
            "created_at": "2021-12-21 09:57:09",
            "type": "movie",
            "title_orig": "Grizzly II: The Predator",
            "title_rus": "Гризли 2: Хищник",
            "quality": "webdl",
            "year": "1983",
            "kinopoisk_id": "16935",
            "imdb_id": "tt0093119",
            "description": "Мир превращается в ад, когда гигантский Гризли, в ответ на убийство сородичей браконьерами, нападает на людей на рок-концерте в Национальном парке.",
            "poster": "https://kinopoiskapiunofficial.tech/images/posters/kp/16935.jpg",
            "duration": "01:14",
            "slogan": "Giant Killer Grizzly Attacks Massive Rock Concert.",
            "age": null,
            "iframe_url": "https://cdn0.cdnhubstream.pro/show/64186",
            "genres": [
                "музыка",
                "триллер",
                "ужасы"
            ],
            "countries": [
                "США"
            ],
            "translations": [
                {
                    "id": 1,
                    "title": "English (Оригинал)"
                }
            ]
        },
        {
            "id": 63888,
            "created_at": "2021-12-08 06:25:45",
            "type": "serial",
            "title_orig": "The Beatles: Get Back",
            "title_rus": "The Beatles: Вернись",
            "quality": "webdl",
            "year": "2021",
            "kinopoisk_id": "1355015",
            "imdb_id": "tt14873812",
            "description": "Фильм расскажет об атмосфере, царившей на студии во время записи знаменитого альбома «Let It Be», и завершится записью последнего живого концерта группы, который состоялся на крыше дома 3 на лондонской улице Сэвил-Роу 30 января 1969 года.",
            "poster": "https://kinopoiskapiunofficial.tech/images/posters/kp/1355015.jpg",
            "duration": null,
            "slogan": null,
            "age": null,
            "iframe_url": "https://cdn0.cdnhubstream.pro/show/63888",
            "genres": [
                "документальный",
                "музыка"
            ],
            "countries": [
                "Великобритания",
                "Новая Зеландия",
                "США"
            ],
            "translations": [
                {
                    "id": 796,
                    "title": "Jaskier (многоголосый)",
                    "season": "1",
                    "episode": "1"
                }
            ],
            "season": "1",
            "episode": "1"
        },
        …
    ],
    "next": {
        "offset": 50,
        "limit": 50
    }
}
</pre>
</blockquote>
<blockquote style="margin-bottom:10px;background-color:#f8f9fa;padding:5px 10px">
    <code style="display:inline-block;"><a href="https://futmax.info/api/search?token={!! $token !!}&title=терминатор" target="_blank">https://futmax.info/api/search?token={!! $token !!}&title=терминатор</a></code>
    <pre class="html-syntax">
{
    "prev": null,
    "result": [
        {
            "id": 54434,
            "created_at": "2021-02-26 14:45:20",
            "type": "movie",
            "title_orig": "Terminator 3: Rise of the Machines",
            "title_rus": "Терминатор 3: Восстание машин",
            "quality": "hdtv",
            "year": "2003",
            "kinopoisk_id": "319",
            "imdb_id": "tt0181852",
            "description": "Прошло десять лет с тех пор, как Джон Коннор помог предотвратить Судный День и спасти человечество от массового уничтожения. Теперь ему 25, Коннор не живет «как все» - у него нет дома, нет кредитных карт, нет сотового телефона и никакой работы. Его существование нигде не зарегистрировано. Он не может быть прослежен системой Skynet - высокоразвитой сетью машин, которые когда-то попробовали убить его и развязать войну против человечества. Пока из теней будущего не появляется T-X - Терминатрикс, самый сложный киборг-убийца Skynet. Посланная назад сквозь время, чтобы завершить работу, начатую её предшественником, T-1000, эта машина так же упорна, как прекрасен её человеческий облик. Теперь единственная надежда Коннору выжить - Терминатор, его таинственный прежний убийца. Вместе они должны одержать победу над новыми технологиями T-X и снова предотвратить Судный День...",
            "poster": "https://kinopoiskapiunofficial.tech/images/posters/kp/319.jpg",
            "duration": "1:49",
            "slogan": "The Machines Will Rise",
            "age": "16",
            "iframe_url": "https://cdn0.cdnhubstream.pro/show/54434",
            "genres": [
                "боевик",
                "фантастика"
            ],
            "countries": [
                "Великобритания",
                "Германия",
                "США"
            ],
            "translations": [
                {
                    "id": 516,
                    "title": "Полное дублирование"
                },
                {
                    "id": 1,
                    "title": "English (Оригинал)"
                },
                {
                    "id": 728,
                    "title": "Профессиональный многоголосый"
                },
                {
                    "id": 87,
                    "title": "Чадов Михаил (авторский одноголосый)"
                },
                {
                    "id": 29,
                    "title": "Гаврилов Андрей (авторский одноголосый)"
                },
                {
                    "id": 895,
                    "title": "Карусель (многоголосый)"
                }
            ]
        },
        {
            "id": 13601,
            "created_at": "2021-01-14 20:38:40",
            "type": "serial",
            "title_orig": "Terminator: The Sarah Connor Chronicles",
            "title_rus": "Терминатор: Битва за будущее",
            "quality": "bd",
            "year": "2008",
            "kinopoisk_id": "260995",
            "imdb_id": "tt1245068",
            "description": "Действие сериала разворачивается после событий в фильме «Терминатор 2: Судный день». Сара Коннор и ее сын Джон скрываются от правительства и вынашивают план уничтожения корпорации Skynet в надежде предотвратить Армагеддон.",
            "poster": "https://kinopoiskapiunofficial.tech/images/posters/kp/260995.jpg",
            "duration": "0:43",
            "slogan": "This season a mother will become a warrior, a son will become a hero, and their only ally will be a friend from the future.",
            "age": "16",
            "iframe_url": "https://cdn0.cdnhubstream.pro/show/13601",
            "genres": [
                "боевик",
                "триллер",
                "фантастика"
            ],
            "countries": [
                "США"
            ],
            "translations": [
                {
                    "id": 583,
                    "title": "РенТВ",
                    "season": "2",
                    "episode": "22"
                },
                {
                    "id": 806,
                    "title": "LostFilm (многоголосый)",
                    "season": "2",
                    "episode": "22"
                }
            ],
            "season": "2",
            "episode": "22"
        },
        …
    ],
    "next": null
}
</pre>
</blockquote>
<blockquote style="background-color:#f8f9fa;padding:5px 10px">
    <code style="display:inline-block;"><a href="https://futmax.info/api/search?token={!! $token !!}&kinopoisk_id=319" target="_blank">https://futmax.info/api/search?token={!! $token !!}&kinopoisk_id=319</a></code>
    <pre class="html-syntax">
{
    "prev": null,
    "result": [
        {
            "id": 54434,
            "created_at": "2021-02-26 14:45:20",
            "type": "movie",
            "title_orig": "Terminator 3: Rise of the Machines",
            "title_rus": "Терминатор 3: Восстание машин",
            "quality": "hdtv",
            "year": "2003",
            "kinopoisk_id": "319",
            "imdb_id": "tt0181852",
            "description": "Прошло десять лет с тех пор, как Джон Коннор помог предотвратить Судный День и спасти человечество от массового уничтожения. Теперь ему 25, Коннор не живет «как все» - у него нет дома, нет кредитных карт, нет сотового телефона и никакой работы. Его существование нигде не зарегистрировано. Он не может быть прослежен системой Skynet - высокоразвитой сетью машин, которые когда-то попробовали убить его и развязать войну против человечества. Пока из теней будущего не появляется T-X - Терминатрикс, самый сложный киборг-убийца Skynet. Посланная назад сквозь время, чтобы завершить работу, начатую её предшественником, T-1000, эта машина так же упорна, как прекрасен её человеческий облик. Теперь единственная надежда Коннору выжить - Терминатор, его таинственный прежний убийца. Вместе они должны одержать победу над новыми технологиями T-X и снова предотвратить Судный День...",
            "poster": "https://kinopoiskapiunofficial.tech/images/posters/kp/319.jpg",
            "duration": "1:49",
            "slogan": "The Machines Will Rise",
            "age": "16",
            "iframe_url": "https://cdn0.cdnhubstream.pro/show/54434",
            "genres": [
                "боевик",
                "фантастика"
            ],
            "countries": [
                "Великобритания",
                "Германия",
                "США"
            ],
            "translations": [
                {
                    "id": 516,
                    "title": "Полное дублирование"
                },
                {
                    "id": 1,
                    "title": "English (Оригинал)"
                },
                {
                    "id": 728,
                    "title": "Профессиональный многоголосый"
                },
                {
                    "id": 87,
                    "title": "Чадов Михаил (авторский одноголосый)"
                },
                {
                    "id": 29,
                    "title": "Гаврилов Андрей (авторский одноголосый)"
                },
                {
                    "id": 895,
                    "title": "Карусель (многоголосый)"
                }
            ]
        }
    ],
    "next": null
}
</pre>
</blockquote>