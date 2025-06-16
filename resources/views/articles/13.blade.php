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

<p>1. Залить все файлы из папки <code>upload</code> в корень сайта с заменой, кроме файлов <code>cdnhub_installation.php</code> (файл для ручной установки модуля) и <code>cdnhub/config.php</code> (файл конфигурации модуля)</p>
<blockquote style="background-color:#f8f9fa;padding:5px 10px">
    <strong style="color: rgb(240, 102, 102);"><u>Осторожно!</u> Если зальете файл конфигурации модуля из архива в корень сайта с заменой, слетят все ранее установленные вами настройки модуля.</strong>
</blockquote>

<br>

<p>2! Если первоначальная установка модуля была через "Управление плагинами" в админапанели DLE, делаем следующее:</p>
<blockquote style="background-color:#f8f9fa;padding:5px 10px">
Заходим в "Управление плгинами" в админпанели DLE, далее в списке удаляем наш плагин "CDNHub v.3.0", после устанавливаем новый плагин через файл "cdnhub.xml" в архиве с модулем.
</blockquote>

<br>

<p>3! Если первоначальная установка модуля была произведена по ручной инструкции из файда "installation(manual)[DLE 10.2-12.1].txt", делаем следующее:</p>

<p>&nbsp;&nbsp;&nbsp;&nbsp;Открыть файл <code style="color:#A0522D">engine/modules/main.php</code></p>
<blockquote style="background-color:#f8f9fa;padding:5px 10px">
    находим следующий код:
    <pre class="html-syntax">
$tpl->compile ( 'main' );
</pre>
</blockquote>
<blockquote style="background-color:#f8f9fa;padding:5px 10px">
    перед ним вставляем этот код:
    <pre class="html-syntax">
// CDNHub Script -> Begin

$cdnhub->view(array('script'));

// CDNHub Script -> End
</pre>
</blockquote>

<p>4. Готово</p>