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

<p>1. Залить все файлы из папки <code>upload</code> в корень сайта</p>

<br>

<p>2. Перейти по ссылке <code>http://ваш-сайт.ру/cdnhub_installation.php</code> (после удалить этот файл)</p>

<br>

<p>3. Открыть файл <code style="color:#A0522D">engine/init.php</code></p>
<blockquote style="background-color:#f8f9fa;padding:5px 10px">
    найти этот код:
    <pre class="html-syntax">
require_once (DLEPlugins::Check(ENGINE_DIR . '/modules/functions.php'));
</pre>
</blockquote>
<blockquote style="background-color:#f8f9fa;padding:5px 10px">
    после него вставить этот код:
    <pre class="html-syntax">
// CDNHub init -> Begin

require_once ROOT_DIR . '/cdnhub/init.php';

// CDNHub Init -> End
</pre>
</blockquote>

<br>

<p>4. Открыть файл <code style="color:#A0522D">engine/modules/show.full.php</code></p>
<blockquote style="background-color:#f8f9fa;padding:5px 10px">
    найти этот код:
    <pre class="html-syntax">
else $tpl->load_template( 'fullstory.tpl' );
</pre>
</blockquote>
<blockquote style="background-color:#f8f9fa;padding:5px 10px">
    после него вставить этот код:
    <pre class="html-syntax">
// CDNHub View -> Begin

$cdnhub->view(array('player'));

// CDNHub View -> End
</pre>
</blockquote>

<br>

<p>5. Открыть файл <code style="color:#A0522D">engine/inc/addnews.php</code></p>
<blockquote style="background-color:#f8f9fa;padding:5px 10px">
    найти этот код:
    <pre class="html-syntax">
// End XFields Call
</pre>
</blockquote>
<blockquote style="background-color:#f8f9fa;padding:5px 10px">
    после него вставить этот код:
    <pre class="html-syntax">
// CDNHub Search -> Begin

require_once ROOT_DIR . '/cdnhub/admin/widgets/search.php';

// CDNHub Search -> End
</pre>
</blockquote>

<br>

<p>6. Открыть файл <code style="color:#A0522D">engine/inc/editnews.php</code></p>
<blockquote style="background-color:#f8f9fa;padding:5px 10px">
    найти этот код:
    <pre class="html-syntax">
// End XFields Call
</pre>
</blockquote>
<blockquote style="background-color:#f8f9fa;padding:5px 10px">
    после него вставить этот код:
    <pre class="html-syntax">
// CDNHub Search -> Begin

require_once ROOT_DIR . '/cdnhub/admin/widgets/search.php';

// CDNHub Search -> End
</pre>
</blockquote>

<br>

<p>7. Открыть файл <code style="color:#A0522D">engine/modules/main.php</code></p>
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
<blockquote style="background-color:#f8f9fa;padding:5px 10px">
    далее найти этот код:
    <pre class="html-syntax">
GzipOut();
</pre>
</blockquote>
<blockquote style="background-color:#f8f9fa;padding:5px 10px">
    перед ним вставить этот код:
    <pre class="html-syntax">
// CDNHub Update -> Begin

if (!intval($cdnhub->config['update']['type']))
    $cdnhub->update();

// CDNHub Update -> End
</pre>
</blockquote>

<br>

<p>8. Готово</p>