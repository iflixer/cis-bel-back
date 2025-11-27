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
            <td>Уникальный идентификатор</td>
        </tr>
        <tr>
            <td>title</td>
            <td>Название</td>
        </tr>
    </tbody>
</table>

<br>

<h3>Примеры использования</h3>
<blockquote style="background-color:#f8f9fa;padding:5px 10px">
    <code style="display:inline-block;"><a href="https://api0.flixcdn.biz/api/translations?token={!! $token !!}" target="_blank">https://api0.flixcdn.biz/api/translations?token={!! $token !!}</a></code>
    <pre class="html-syntax">
{
    "result": [
        {
            "id": 1,
            "title": "English (Оригинал)"
        },
        {
            "id": 2,
            "title": "test"
        },
        {
            "id": 3,
            "title": "AlisaDirilis (авторский одноголосый)"
        },
        {
            "id": 4,
            "title": "Anika (авторский одноголосый)"
        },
        {
            "id": 5,
            "title": "Bars MacAdams (авторский одноголосый)"
        },
        {
            "id": 6,
            "title": "BeniAffet (авторский одноголосый)"
        },
        {
            "id": 7,
            "title": "Berial (авторский одноголосый)"
        },
        …
    ]
}
</pre>
</blockquote>