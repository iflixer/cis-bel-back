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

<h3>Код вывода плеера</h3>
<pre class="html-syntax">
<?php echo htmlspecialchars('<iframe src="//cdnhub.help/show/') . '<span style="color:#0d6efd">{resource}</span>/<span style="color:#198754">{id}</span>' . htmlspecialchars('" width="640" height="480" frameborder="0" allowfullscreen></iframe>'); ?>
</pre>

<br>

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
            <td><span style="color:#0d6efd">{resource}</span></td>
            <td>Тип ресурса по идентификатору которого будет поиск плеера</td>
            <td>Строка</td>
            <td><code>kinopoisk</code>, <code>imdb</code></td>
        </tr>
        <tr>
            <td><span style="color:#198754">{id}</span></td>
            <td>Идентификатор ресурса по которому будет поиск плеера</td>
            <td>Строка</td>
            <td><code>1</code> - <code>999999999</code>, <code>tt1</code> - <code>tt999999999</code></td>
        </tr>
    </tbody>
</table>

<br>

<h3>Примеры вывода плеера</h3>
<blockquote style="margin-bottom:10px;background-color:#f8f9fa;padding:5px 10px">
    Вывод плеера по Кинопоиск ID
    <pre class="html-syntax">
<?php echo htmlspecialchars('<iframe src="//cdnhub.help/show/kinopoisk/739642" width="640" height="480" frameborder="0" allowfullscreen></iframe>'); ?>
    </pre>
</blockquote>
<blockquote style="background-color:#f8f9fa;padding:5px 10px">
    Вывод плеера по Imdb ID
    <pre class="html-syntax">
<?php echo htmlspecialchars('<iframe src="//cdnhub.help/show/imdb/tt2719848" width="640" height="480" frameborder="0" allowfullscreen></iframe>'); ?>
    </pre>
</blockquote>

<br>

<h3>Доступные GET параметры</h3>
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
            <td>translation</td>
            <td>Идентификатор перевода</td>
            <td>Число</td>
            <td><code>1</code> - <code>999999999</code></td>
        </tr>
        <tr>
            <td>season</td>
            <td>Номер сезона</td>
            <td>Число</td>
            <td><code>1</code> - <code>999999999</code></td>
        </tr>
        <tr>
            <td>episode</td>
            <td>Номер серии</td>
            <td>Число</td>
            <td><code>1</code> - <code>999999999</code></td>
        </tr>
        <tr>
            <td>no_controls</td>
            <td>Скрыть все элементы управления</td>
            <td>Число</td>
            <td><code>1</code></td>
        </tr>
        <tr>
            <td>no_control_translations</td>
            <td>Скрыть селектор выбора переводов</td>
            <td>Число</td>
            <td><code>1</code></td>
        </tr>
        <tr>
            <td>no_control_seasons</td>
            <td>Скрыть селектор выбора сезонов</td>
            <td>Число</td>
            <td><code>1</code></td>
        </tr>
        <tr>
            <td>no_control_episodes</td>
            <td>Скрыть селектор выбора сезонов и серий</td>
            <td>Число</td>
            <td><code>1</code></td>
        </tr>
    </tbody>
</table>

<br>

<h3>Примеры использования</h3>
<blockquote style="margin-bottom:10px;background-color:#f8f9fa;padding:5px 10px">
    Указание определенного перевода
    <pre class="html-syntax">
//cdnhub.help/show/1?translation=516
</pre>
</blockquote>
<blockquote style="margin-bottom:10px;background-color:#f8f9fa;padding:5px 10px">
    Указание определенного перевода со скрытием селектора выбора
    <pre class="html-syntax">
//cdnhub.help/show/1?translation=516&no_control_translations=1
</pre>
</blockquote>
<blockquote style="margin-bottom:10px;background-color:#f8f9fa;padding:5px 10px">
    Указание определенного перевода и сезона
    <pre class="html-syntax">
//cdnhub.help/show/1?translation=516&season=2
</pre>
</blockquote>
<blockquote style="margin-bottom:10px;background-color:#f8f9fa;padding:5px 10px">
    Указание определенного перевода и сезона со скрытием селекторов выбора
    <pre class="html-syntax">
//cdnhub.help/show/1?translation=516&season=2&no_control_translations=1&no_control_seasons=1
</pre>
</blockquote>
<blockquote style="margin-bottom:10px;background-color:#f8f9fa;padding:5px 10px">
    Указание определенного перевода, сезона и серии
    <pre class="html-syntax">
//cdnhub.help/show/1?translation=516&season=2&episode=21
</pre>
</blockquote>
<blockquote style="margin-bottom:10px;background-color:#f8f9fa;padding:5px 10px">
    Указание определенного перевода, сезона и серии со скрытием всех селекторов выбора
    <pre class="html-syntax">
//cdnhub.help/show/1?translation=516&season=2&episode=21&no_controls=1
</pre>
</blockquote>
<blockquote style="background-color:#f8f9fa;padding:5px 10px">
    Указание определенного перевода, сезона и серии со скрытием селекторов выбора только сезона и серии
    <pre class="html-syntax">
//cdnhub.help/show/1?translation=516&season=2&episode=21&no_control_episodes=1
</pre>
</blockquote>