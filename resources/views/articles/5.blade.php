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

<h3>Модуль CDNHub для DLE</h3>
<p>Модуль позволяет производить поиск по базе CDNHub как при добавлении новости вручную, так и при массовом добавлении плееров через соотвествующий раздел. Так же есть возможность автоматизировать запуск модуля, позволяя ему самому обновлять плееры при выходе нового качества фильма или новой серии сериала. Есть возможность отслеживать новинки из админпанели модуля раздел &laquo;Мониторинг новинок&raquo;, а так же добавлять выбранные сразу себе на сайт (новости попадают на модерацию). Так же есть возможность вывести блок обновлений сериалов, шаблон блока поддерживает все теги из кратких новостей.</p>

<br>

<h3>Требования</h3>
<p><code>DLE v10.2+</code> <code>UTF-8</code></p>

<br>

<h3>Архив с модулем</h3>
<p><a href="https://futmax.info/dle-plugins/cdnhub-v3.2.zip">cdnhub-v3.2.zip</a> [1,85 МБ] &middot; <a href="/articles/15">История обновлений модуля</a></p>
<blockquote style="background-color:#f8f9fa;padding:5px 10px">
    <strong style="color: rgb(240, 102, 102);"><u>Важно!</u> Делайте бэкап базы данных перед установкой или обновлением модулей.</strong>
</blockquote>
<blockquote style="background-color:#f8f9fa;padding:5px 10px">
    <strong style="color:#d39e00"><u>Внимание!</u> Если не сохраняются настройки модуля, выставите права 666 на файл cdnhub/config.php</strong>
</blockquote>

<br>

<h3>Инструкции</h3>
<p><code><a href="/articles/11" class="code-link">Ручная установка модуля</a></code></p>
<p><code><a href="/articles/12" class="code-link">Автоматическая установка модуля</a></code></p>
<p><code><a href="/articles/13" class="code-link">Обновление модуля</a></code></p>

<br>

<h3>Основные возможности модуля</h3>
<blockquote style="margin-bottom:10px;background-color:#f8f9fa;padding:5px 10px">
    <code style="display:inline-block;margin-bottom:2px;padding-left:7px;padding-right:7px;font-size:16px !important;font-family: Montserrat-Medium !important">Ручной поиск и заполнение данных</code>
    <div style="margin-bottom:5px">
        Заполнение данных и ссылки на плеер из нашей базы. Доступно в добавлении и редактировании новости в админпанели сайта. Поиск возможен по Кинопоиск ID, Imdb ID и Названию видео. Так же если у вас стоит модуль Parser Kinopoisk возможен поиск по Кинопоиск ID указаному в этом модуле. С права в результатах поиска есть кнопока &laquo;Проставить данные&raquo;, при клике на которую модуль заполнит все поля новости указанные в настройках модуля.
    </div>
    <img src="/images/module/001.png">
</blockquote>
<blockquote style="margin-bottom:10px;background-color:#f8f9fa;padding:5px 10px">
    <code style="display:inline-block;margin-bottom:2px;padding-left:7px;padding-right:7px;font-size:16px !important;font-family: Montserrat-Medium !important">Массовое проставление данных</code>
    <div style="margin-bottom:5px">
        Массовое заполнение данных и ссылки на плеер. Поиск доступен по Кинопоиск ID и Imdb ID. Раздел имеет очень гибкие настройки в которых не сложно разобраться.
    </div>
    <img src="/images/module/002.png">
    <img src="/images/module/003.png">
</blockquote>
<blockquote style="margin-bottom:10px;background-color:#f8f9fa;padding:5px 10px">
    <code style="display:inline-block;margin-bottom:2px;padding-left:7px;padding-right:7px;font-size:16px !important;font-family: Montserrat-Medium !important">Автоматическое обновление</code>
    <div style="margin-bottom:5px">
        Автоматическое обновление данных и ссылки на плеер. Можно настроить обновление как при переходе на сайт, так и в планировщике задак cron. Удобные и понятные настройки для фильмов и сериалов отдельно. Есть возможность настроить автоматическое добавление фильмов и сериалов которых нет у вас на сайте при получении обновлений. Есть возможность добавить доп. поле типа Переключатель 'Да' или 'Нет' (Не обновлять) и указать его в настройках обновления, при добавлении и редактировании новости тогда вы сможете отключать обновление для нужных вам новостей на сайте. Так же вы можете настроить и вывести Блок обновлений сериалов у себя на сайте (если не включить обновление сериалов, то в блок не будут записываться обновления).
    </div>
    <img src="/images/module/006.png">
    <img src="/images/module/007.png">
</blockquote>
<blockquote style="margin-bottom:10px;background-color:#f8f9fa;padding:5px 10px">
    <code style="display:inline-block;margin-bottom:2px;padding-left:7px;padding-right:7px;font-size:16px !important;font-family: Montserrat-Medium !important">Мониторинг новинок</code>
    <div style="margin-bottom:5px">
        Раздел позволяет удобно отслеживать новинки в нашей базе. В верху есть поиск, в котором вы можете проверять наличие в нашей базе тех или иных фильмов и сериалов по Кинопоиск ID или Названию видео. Для удобства отслеживания у себя на сайте, есть стобец который определяет по Кинопоиск ID наличие новости с данным материалом у вас на сайте. К тому же вы легко и просто можете отмеченные результаты добавить к себе на сайт (новости попадают на модерацию), в низу списка для этого есть селектор выбора действий. Так же вы можете получить по выбранным результатам список Кинопоиск ID материалов.
    </div>
    <img src="/images/module/004.png">
    <img src="/images/module/005.png">
</blockquote>
<blockquote style="margin-bottom:10px;background-color:#f8f9fa;padding:5px 10px">
    <code style="display:inline-block;margin-bottom:2px;padding-left:7px;padding-right:7px;font-size:16px !important;font-family: Montserrat-Medium !important">Вывод плеера</code>
    <div style="margin-bottom:5px">
        Правильный способ вывода плеера в шаблоне (обязательно выводить именно так, если вы пользуетесь модулем)
    </div>
    <img src="/images/module/008.png">
    <img src="/images/module/014.png">
</blockquote>
<blockquote style="margin-bottom:10px;background-color:#f8f9fa;padding:5px 10px">
    <code style="display:inline-block;margin-bottom:2px;padding-left:7px;padding-right:7px;font-size:16px !important;font-family: Montserrat-Medium !important">Шаблоны СЕО данных</code>
    <div style="margin-bottom:5px">
        Настройки Шаблонов СЕО данных. Вы можете гибко настроить шаблоны заполнения СЕО данных, таких как ЧПУ УРЛ новости, Заголовок, Мета-заголовок и Мета-описание.
    </div>
    <img src="/images/module/009.png">
    <img src="/images/module/010.png">
</blockquote>
<blockquote style="margin-bottom:10px;background-color:#f8f9fa;padding:5px 10px">
    <code style="display:inline-block;margin-bottom:2px;padding-left:7px;padding-right:7px;font-size:16px !important;font-family: Montserrat-Medium !important">Свои названия Качеств видео и Переводов</code>
    <div style="margin-bottom:5px">
        Вы можете настроить запись в отдельные доп. поля своих названий Качеств видео и Переводов.
    </div>
    <img src="/images/module/011.png">
    <img src="/images/module/012.png">
</blockquote>
<blockquote style="margin-bottom:10px;background-color:#f8f9fa;padding:5px 10px">
    <code style="display:inline-block;margin-bottom:2px;padding-left:7px;padding-right:7px;font-size:16px !important;font-family: Montserrat-Medium !important">Форматированный Сезон и Серия</code>
    <div style="margin-bottom:5px">
        Вы можете настроить запись в отдельные доп. поля Форматированный Сезон и Серию сериала.
    </div>
    <img src="/images/module/013.png">
</blockquote>

<!-- <br>

<div id="ModuleUpdates">

    <h3>Обновление</h3>

    <blockquote style="margin-bottom:10px;background-color:#f8f9fa;padding:5px 10px">
        <code style="display:inline-block;margin-bottom:2px;padding-left:7px;padding-right:7px;font-size:16px !important;font-family: Montserrat-Medium !important">v3.1</code>
        <div style="margin-bottom:5px">
            Вы можете настроить запись в отдельные доп. поля Форматированный Сезон и Серию сериала.
        </div>
    </blockquote>

</div> -->