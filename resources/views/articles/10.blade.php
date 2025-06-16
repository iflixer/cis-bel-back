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

    .code-link {
        background-image: url('../images/external-ltr.svg');
        background-position: center right;
        background-repeat: no-repeat;
        padding-right: 15px;
    }

</style>

<h3>Стуктура ответа</h3>
<table class="table">
    <thead>
        <tr>
            <th>Название</th>
            <th>Описание</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>movies</td>
            <td>Список обновлений фильмов</td>
        </tr>
        <tr>
            <td>serials</td>
            <td>Список обновлений сериалов</td>
        </tr>
    </tbody>
</table>

<br>

<h3>Пример структуры ответа</h3>
<blockquote style="background-color:#f8f9fa;padding:5px 10px">
    <pre class="html-syntax">
{
    "result": {
        "movies": [
            …
        ],
        "serials": [
            …
        ],
    }
}
</pre>
</blockquote>

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
            <td>update_id</td>
            <td>Уникальный идентификатор обновления</td>
        </tr>
        <tr>
            <td>translation</td>
            <td>Объект содержащий <code>id</code> перевода обновления, <code>title</code> (название)</td>
        </tr>
        <tr>
            <td>season</td>
            <td>Номер сезона обновления</td>
        </tr>
        <tr>
            <td>episode</td>
            <td>Номер серии обновления</td>
        </tr>
        <tr>
            <td>content</td>
            <td>Объект содержащий структуру ответа из метода <code><a href="/articles/8" class="code-link" title="Документация метода &laquo;search&raquo;">search</a></code></td>
        </tr>
        <tr>
            <td>created</td>
            <td>Дата добавления обновления</td>
        </tr>
        <tr>
            <td>type</td>
            <td>Тип обновления <code>movie</code> или <code>episode</code></td>
        </tr>
    </tbody>
</table>

<br>

<h3>Примеры использования</h3>
<blockquote style="background-color:#f8f9fa;padding:5px 10px">
    <code style="display:inline-block;"><a href="https://api.cdnhubstream.pro/api/updates?token={!! $token !!}" target="_blank">https://api.cdnhubstream.pro/api/updates?token={!! $token !!}</a></code>
    <pre class="html-syntax">
{
    "result": {
        "movies": [
            {
                "update_id": 481702,
                "created": "2021-12-22 08:34:27",
                "translation": {
                    "id": "516",
                    "title": "Полное дублирование"
                },
                "type": "movie",
                "content": {
                    "id": 1033,
                    "title_orig": "Avatar",
                    "title_rus": "Аватар",
                    "year": "2009",
                    "description": "Джейк Салли - бывший морской пехотинец, прикованный к инвалидному креслу. Несмотря на немощное тело, Джейк в душе по-прежнему остается воином. Он получает задание совершить путешествие в несколько световых лет к базе землян на планете Пандора, где корпорации добывают редкий минерал, имеющий огромное значение для выхода Земли из энергетического кризиса.",
                    "poster": "https://kinopoiskapiunofficial.tech/images/posters/kp/251733.jpg",
                    "duration": "2:42",
                    "slogan": "Это новый мир",
                    "age": "12",
                    "kinopoisk_id": "251733",
                    "imdb_id": "tt0499549",
                    "quality": "bd",
                    "iframe_url": "https://cdn0.cdnhubstream.pro/show/1033",
                    "genres": [
                        "боевик",
                        "драма",
                        "фантастика"
                    ],
                    "countries": [
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
                        }
                    ]
                }
            },
            …
        ],
        "serials": [
            {
                "update_id": 481696,
                "created": "2021-12-22 08:34:16",
                "translation": {
                    "id": "261",
                    "title": "RG.Paravozik (любительский многоголосый)"
                },
                "type": "episode",
                "season": "1",
                "episode": "2",
                "content": {
                    "id": 64058,
                    "type": "serial",
                    "title_orig": "Station Eleven",
                    "title_rus": "Станция одиннадцать",
                    "year": "2021",
                    "description": "Человечество не справилось с пандемией вируса: 20 лет спустя после гибельной эпидемии труппа странствующих актеров колесит по постапокалиптической Америке, ставит пьесы Шекспира и пополняет припасы в заброшенным домах. Актерам предстоит столкновение с последователями зловещего религиозного культа и их лидером по прозвищу Пророк, захватившими власть в небольшом городке в районе Великих озер.",
                    "poster": "https://kinopoiskapiunofficial.tech/images/posters/kp/1282706.jpg",
                    "duration": null,
                    "slogan": "The End is the Beginning",
                    "age": null,
                    "kinopoisk_id": "1282706",
                    "imdb_id": "tt10579916",
                    "quality": "webdl",
                    "iframe_url": "https://cdn0.cdnhubstream.pro/show/64058",
                    "genres": [
                        "детектив",
                        "драма",
                        "триллер",
                        "фантастика"
                    ],
                    "countries": [
                        "США"
                    ],
                    "translations": [
                        {
                            "id": 749,
                            "title": "BaibaKo (многоголосый)",
                            "season": "1",
                            "episode": "3"
                        },
                        {
                            "id": 791,
                            "title": "HDrezka Studio (многоголосый)",
                            "season": "1",
                            "episode": "3"
                        },
                        {
                            "id": 225,
                            "title": "ColdFilm (любительский многоголосый)",
                            "season": "1",
                            "episode": "3"
                        },
                        {
                            "id": 271,
                            "title": "Ultradox (любительский многоголосый)",
                            "season": "1",
                            "episode": "3"
                        },
                        {
                            "id": 261,
                            "title": "RG.Paravozik (любительский многоголосый)",
                            "season": "1",
                            "episode": "2"
                        }
                    ],
                    "season": "1",
                    "episode": "3"
                }
            },
            …
        ],
    }
}
</pre>
</blockquote>