<!DOCTYPE html>
<html lang="ru">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>

    <link rel="stylesheet" href="/style/style-show.css">
    <script
        src="https://code.jquery.com/jquery-3.4.1.min.js"
        integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
        crossorigin="anonymous">
    </script>

    <script src="https://api.kholobok.biz/js/playerjs_fin.js"></script>

</head>
<body>
    

    <div id="player">
        <div id="proto" style="width:100%;height:100%;"></div>
    </div>


    <script>
        console.log({!! $playList !!});
        const domain = "{{ $domain }}";
        const player = new Playerjs({id:"proto", file: {!! $playList !!} });
        player.elements( {!! $dataPlayer !!} );

        console.log(domain);

        

        document.getElementById("proto").addEventListener("play", function(){
            $.post("https://api.kholobok.biz/apishow/show",{domain: domain});
            var timeout = Math.round(player.api("duration") * 0.02) * 1000;
            console.log(timeout, 'start');
            setTimeout(() => {
                $.post("https://api.kholobok.biz/apishow/fullshow",{domain: domain});
                console.log('end');
            }, timeout);
        });
    </script>



</body>
</html>