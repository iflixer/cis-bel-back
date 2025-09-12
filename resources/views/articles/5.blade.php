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

    h3.doch3 {
        margin-bottom: 20px
    }

    .spoiler-container {
        max-width: 100%;
        margin: 10px 0;
        font-family: sans-serif;
    }

    /* Hide the checkbox */
    .spoiler-toggle {
        display: none;
    }

    /* Style the label as a button */
    .spoiler-label {
        width: 100%;
        display: inline-block;
        user-select: none;
        border-radius: 6px;
        cursor: pointer;
        background-color: #f8f9fa;
        font-size: 16px !important;
        font-family: "Montserrat-Medium" !important;
        color: #606266;
        position: relative;
        padding: 8px 10px;
        margin: 0 0 5px;
    }

    .spoiler-label:after {
        display: block;
        content: "\e6e1";
        color: #c0c4cc;
        font-size: 14px;
        cursor: pointer;
        font-family: 'element-icons' !important;
        font-style: normal;
        font-weight: 400;
        font-variant: normal;
        text-transform: none;
        position: absolute;
        top: 9px;
        right: 13px;
        padding: 0;
        transition: all 0.25s ease;
        transform: rotate(180deg);
    }

    /* Spoiler content */
    .spoiler-content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.5s ease, padding 0.3s ease;
        background-color: #f4f4f4;
        padding: 0 15px;
        border-left: 3px solid #444;
    }

    .spoiler-content p {
        line-height: 140%;
        padding: 10px 0 20px;
    }

    /* When checkbox is checked, show content */
    .spoiler input[type="checkbox"]:checked ~ .spoiler-content {
        max-height: 5000px; /* Adjust based on your content size */
        padding: 10px 15px;
        margin-bottom: 15px;
    }

    .spoiler-toggle:checked + .spoiler-label {
        background-color: #a4f8d7;
    }

    .spoiler-toggle:checked + .spoiler-label:after {
        transform: rotate(0deg);
    }


</style>


<h3 class="doch3">Модуль CDNHub для DLE</h3>
<p>Модуль позволяет производить поиск по базе CDNHub как при добавлении новости вручную, так и при массовом добавлении
    плееров через соотвествующий раздел.
    <br>
    <br>
    Так же есть возможность автоматизировать запуск модуля, позволяя ему самому обновлять плееры при выходе нового
    качества фильма или новой серии сериала.
    <br>
    <br>
    Есть возможность отслеживать новинки из админпанели модуля раздел &laquo;Мониторинг новинок&raquo;,
    а так же добавлять выбранные сразу себе на сайт (новости попадают на модерацию).
    <br>
    <br>
    Так же есть возможность вывести блок обновлений сериалов, шаблон блока поддерживает все теги из кратких новостей.
</p>

<br>

<h3 class="doch3">Требования</h3>
<p><code>DLE v10.2+</code> <code>UTF-8</code></p>

<br>

<h3 class="doch3">Архив с модулем</h3>
<p><a href="https://futmax.info/dle-plugins/dle-plugins/cdnhub-v3.2.zip">cdnhub-v3.2.zip</a> [1,85 МБ] &middot; <a
            href="/articles/15">История
        обновлений модуля</a></p>
<div class="spoiled">
    <strong style="color: rgb(240, 102, 102);"><u>Важно!</u> Делайте бэкап базы данных перед установкой или обновлением
        модулей.</strong>
</div>
<blockquote style="background-color:#f8f9fa;padding:5px 10px">
    <strong style="color:#d39e00"><u>Внимание!</u> Если не сохраняются настройки модуля, выставите права 666 на файл
        cdnhub/config.php</strong>
</blockquote>

<br>

<h3 class="doch3">Инструкции</h3>
<p><code><a href="/articles/11" class="code-link">Ручная установка модуля</a></code></p>
<p><code><a href="/articles/12" class="code-link">Автоматическая установка модуля</a></code></p>
<p><code><a href="/articles/13" class="code-link">Обновление модуля</a></code></p>

<br>

<h3 class="doch3">Основные возможности модуля</h3>
<div class="mainoptions">

    <div class="spoiler">
        <input type="checkbox" id="spoiler00" class="spoiler-toggle">
        <label for="spoiler00" class="spoiler-label">Начало работы</label>
        <div class="spoiler-content">
            <p> 1. Активировать модуль в общих настройках</p>
            <p> 2. Ввести ключ API</p>
            <p> 3. Сохранить настройки</p>
            <br>
            <br>
            <img src="/images/module/0001.png">
            <img src="/images/module/0002.png">
        </div>
    </div>

    <div class="spoiler">
        <input type="checkbox" id="spoiler0" class="spoiler-toggle">
        <label for="spoiler0" class="spoiler-label">Маппинг полей</label>
        <div class="spoiler-content">
            <p>Перед началом импорта необходимо назначить соответствие полей "Настройки модуля" -> "Настройки
                доп.полей"</p>
            <img src="/images/module/017.png">
            <br>
            <br>
            <p>А так же способ получения и соответствие жанров</p>
            <br>
            <img src="/images/module/019.png">
            <img src="/images/module/018.png">
        </div>
    </div>

    <div class="spoiler">
        <input type="checkbox" id="spoiler1" class="spoiler-toggle">
        <label for="spoiler1" class="spoiler-label">Ручной поиск и заполнение данных</label>
        <div class="spoiler-content">
            <p> Заполнение данных и ссылки на плеер из нашей базы. Доступно в добавлении и редактировании новости в
                админпанели сайта.
                Поиск возможен по Кинопоиск ID, Imdb ID и Названию видео. <br><br>Так же если у вас стоит модуль Parser
                Kinopoisk возможен поиск по Кинопоиск ID указаному в этом модуле.
                С права в результатах поиска есть кнопока &laquo;Проставить данные&raquo;, при клике на которую модуль
                заполнит все поля новости указанные в настройках модуля.
            </p>
            <img src="/images/module/001.png">

        </div>
    </div>

    <div class="spoiler">
        <input type="checkbox" id="spoiler2" class="spoiler-toggle">
        <label for="spoiler2" class="spoiler-label">Массовое проставление данных</label>
        <div class="spoiler-content">
            <p>Массовое заполнение данных и ссылки на плеер. Поиск доступен по Кинопоиск ID и Imdb ID.
                Раздел имеет очень гибкие настройки в которых не сложно разобраться.

            </p>
            <img src="/images/module/002.png">
        </div>
    </div>

    <div class="spoiler">
        <input type="checkbox" id="spoiler3" class="spoiler-toggle">
        <label for="spoiler3" class="spoiler-label">Автоматическое обновление</label>
        <div class="spoiler-content">
            <p> Автоматическое обновление данных и ссылки на плеер. Можно настроить обновление как при переходе на сайт,
                так
                и в планировщике задач cron.
                <br>
                <br>
                Удобные и понятные настройки для фильмов и сериалов отдельно. Есть возможность
                настроить автоматическое добавление фильмов и сериалов которых нет у вас на сайте при получении
                обновлений.
                <br>
                <br>
                Есть возможность добавить доп. поле типа Переключатель 'Да' или 'Нет' (Не обновлять) и указать его в
                настройках обновления, при добавлении и редактировании новости тогда вы сможете отключать обновление для
                нужных вам новостей на сайте.
                <br>
            </p>
            <img src="/images/module/002.png">
            <br>
            <br>
            <p>
                Так же вы можете настроить и вывести Блок обновлений сериалов у себя на сайте
                (если не включить обновление сериалов, то в блок не будут записываться обновления).
            </p>
            <img src="/images/module/003.png">
        </div>
    </div>

    <div class="spoiler">
        <input type="checkbox" id="spoiler4" class="spoiler-toggle">
        <label for="spoiler4" class="spoiler-label">Мониторинг новинок</label>
        <div class="spoiler-content">
            <p> Раздел позволяет удобно отслеживать новинки в нашей базе.
                <br>
                <br>
                В верху есть поиск, в котором вы можете проверять наличие в нашей базе тех или иных фильмов и сериалов
                по Кинопоиск ID или Названию видео.
                <br>
                <br>
                Для удобства отслеживания у себя на сайте, есть стобец который определяет по Кинопоиск ID наличие
                новости с данным
                материалом у вас на сайте.
                <br>
                <br>
                К тому же вы легко и просто можете отмеченные результаты добавить к себе на сайт (новости попадают на
                модерацию)<strong>*</strong>,
                в низу списка для этого есть селектор выбора действий. Так же вы можете получить по выбранным
                результатам список Кинопоиск ID материалов.
            </p>
            <img src="/images/module/016.png">

            <p><strong>*В версии 3.3 </strong> - добавлена возможность сразу публиковать новинки, без модерации -
                активируется в "Настройки модуля" -> "Настройки обновления."</p>
            <img src="/images/module/015.png">

        </div>
    </div>


    <div class="spoiler">
        <input type="checkbox" id="spoiler5" class="spoiler-toggle">
        <label for="spoiler5" class="spoiler-label">Вывод плеера</label>
        <div class="spoiler-content">
            <p> Правильный способ вывода плеера в шаблоне (обязательно выводить именно так, если вы пользуетесь
                модулем)</p>
            <img src="/images/module/0008.png">
            <img src="/images/module/014.png">
        </div>
    </div>

    <div class="spoiler">
        <input type="checkbox" id="spoiler6" class="spoiler-toggle">
        <label for="spoiler6" class="spoiler-label">Шаблоны СЕО данных</label>
        <div class="spoiler-content">
            <p> Настройки Шаблонов СЕО данных. Вы можете гибко настроить шаблоны заполнения СЕО данных, таких как ЧПУ
                УРЛ
                новости, Заголовок, Мета-заголовок и Мета-описание. <br>
                "Настройки модуля" -> "Шаблоны СЕО данных" </p>
            <img src="/images/module/0009.png">
        </div>
    </div>

    <div class="spoiler">
        <input type="checkbox" id="spoiler7" class="spoiler-toggle">
        <label for="spoiler7" class="spoiler-label">Свои названия Качеств видео и Переводов</label>
        <div class="spoiler-content">
            <p> Вы можете настроить запись в отдельные доп. поля своих названий Качеств видео и Переводов.<br>
                "Настройки модуля" -> "Настройки доп. полей" </p>
            <img src="/images/module/011.png">
            <img src="/images/module/012.png">
        </div>
    </div>


    <div class="spoiler">
        <input type="checkbox" id="spoiler8" class="spoiler-toggle">
        <label for="spoiler8" class="spoiler-label">Форматированный
            Сезон и Серия</label>
        <div class="spoiler-content">
            <p> Вы можете настроить запись в отдельные доп. поля Форматированный Сезон и Серию сериала.<br>
                "Настройки модуля" -> "Настройки жанров"</p>
            <img src="/images/module/013.png">
        </div>
    </div>

</div>

