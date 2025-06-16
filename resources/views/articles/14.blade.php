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

<h3>actualize.js</h3>
<p>Скрипт предназначен для автоматического определения не работающего или заблокированного домена и последующей подгрузки на лету плеера с актуальным рабочим доменом.</p>

<br>

<p><code><a href="https://sys.cdnhubstream.pro/actualize.js" target="_blank" class="code-link">actualize.js</a></code></p>

<br>

<blockquote style="background-color:#f8f9fa;padding:5px 10px">
    <strong style="color:#d39e00"><u>Внимание!</u> Если у вас установлен наш актуальный DLE Модуль, то скрипт по умолчанию будет подключен и задействован.</strong>
</blockquote>
<blockquote style="background-color:#f8f9fa;padding:5px 10px">
    <strong style="color: rgb(240, 102, 102);"><u>Важно!</u> Настоятельно рекомендуем подключать скрипт, во избежание проблем с неработающим плеером.</strong>
</blockquote>

<br>

<h3>Инструкция (для тех у кого не установлен DLE Модуль, либо ваш сайт работает на движке DLE)</h3>

<br>

<p>1. Фрейму с нашим плеером добавляем атрибут id="cdnhub"</p>

<blockquote style="background-color:#f8f9fa;padding:5px 10px">
пример, как должно получиться ниже
<pre class="html-syntax">
<?php echo htmlspecialchars('<iframe src="https://cdn0.cdnhubstream.pro/show/1" id="cdnhub" frameborder="0" allowfullscreen></iframe>'); ?>
</pre>
</blockquote>

<br>

<p>2. Подключить скаченный скрипт перед закрытием тега <code><?php echo htmlspecialchars('</body>'); ?></code></p>

<blockquote style="background-color:#f8f9fa;padding:5px 10px">
пример, как должно получиться ниже (для DLE в main.tpl)
<pre class="html-syntax">
<?php echo htmlspecialchars('<script src="{THEME}/js/actualize.js"></script>
</body>'); ?>
</pre>
</blockquote>

<blockquote style="background-color:#f8f9fa;padding:5px 10px">
пример, как должно получиться ниже (для сайта на другом движке либо самопис)
<pre class="html-syntax">
<?php echo htmlspecialchars('<script src="/js/actualize.js"></script>
</body>'); ?>
</pre>
</blockquote>