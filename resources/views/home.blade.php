<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>

    <link rel="stylesheet" href="/style/style-index.css">

    <script src="https://use.fontawesome.com/2f2a471d84.js"></script>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.0/css/all.css">

    <link rel="stylesheet" href="https://cdn.lineicons.com/1.0.1/LineIcons.min.css">

    <script src="/js/vue.js"></script>
    <script src="/js/axios.min.js"></script>

    

</head>
<body>
    <div id="content">

        <component-menu :data="menu"></component-menu>

        <main>
            <component-header :data="header"></component-header>
        
            {{ $title }}<br>
            {{ $tupe }}<br>
            {{ $user }}<br>
            <a href="/logout">Out</a>

        </main>

    </div>

    <script>
        var menu = {!! $menu !!} ;
        var header = {!! $header !!} ;
        {!! $components !!}
    </script>
    


    <script src="/js/index.js"></script>
    

</body>
</html>