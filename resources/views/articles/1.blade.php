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

<h3>Ваш API-токен</h3>
<pre class="html-syntax">
{!! $token !!}
</pre>

<br>

<h3>Доступные API методы</h3>
<table class="table">
    <thead>
        <tr>
            <th>Метод</th>
            <th>Описание</th>
            <th>Пример запроса</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code><a href="/articles/8" class="code-link" title="Документация метода &laquo;search&raquo;">search</a></code></td>
            <td>Поиск по нашей базе</td>
            <td><a href="https://api0.flixcdn.biz/api/search?token={!! $token !!}" target="_blank">https://api0.flixcdn.biz/api/search?token={!! $token !!}</a></td>
        </tr>
        <tr>
            <td><code><a href="/articles/9" class="code-link" title="Документация метода &laquo;translations&raquo;">translations</a></code></td>
            <td>Список всех доступных переводов</td>
            <td><a href="https://api0.flixcdn.biz/api/translations?token={!! $token !!}" target="_blank">https://api0.flixcdn.biz/api/translations?token={!! $token !!}</a></td>
        </tr>
        <tr>
            <td><code><a href="/articles/10" class="code-link" title="Документация метода &laquo;updates&raquo;">updates</a></code></td>
            <td>Список обновлений фильмов и сериалов</td>
            <td><a href="https://api0.flixcdn.biz/api/updates?token={!! $token !!}" target="_blank">https://api0.flixcdn.biz/api/updates?token={!! $token !!}</a></td>
        </tr>
    </tbody>
</table>