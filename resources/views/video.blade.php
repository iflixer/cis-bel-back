<!DOCTYPE html>
<html lang="ru">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">


    <title>kholobok.biz</title>


    <link rel="stylesheet" href="/style/style-index.css">
    
    <link rel="stylesheet" href="/style/style-messages.css">
    <link rel="stylesheet" href="/style/style-menu.css">
    <link rel="stylesheet" href="/style/style-panels.css">
    <link rel="stylesheet" href="/style/style-header.css">
    <link rel="stylesheet" href="/style/style-videos.css">
    <link rel="stylesheet" href="/style/style-player.css">
    <link rel="stylesheet" href="/style/style-tikets.css">

    <link rel="stylesheet" href="/style/style-grid.css">
    <link rel="stylesheet" href="/style/style-display.css">
    <link rel="stylesheet" href="/style/style-forms.css">


    <script src="/js/vue.js"></script>
    <script src="/js/vue-color.min.js"></script>
    

    <script src="https://use.fontawesome.com/2f2a471d84.js"></script>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.0/css/all.css">


    <link rel="stylesheet" href="https://cdn.lineicons.com/1.0.1/LineIcons.min.css">

    
    <script src="/js/vue.js"></script>
    <script src="/js/axios.min.js"></script>

    <script src="https://api.kholobok.biz/js/playerjs_fin.js"></script>

</head>
<body>

    

    <div id="content">

        <component-menu :data="menu"></component-menu>

        <component :is="nameComponent" :data="component">
            <component-header :data="header">
                <component-message 
                    v-for="(message, index) in messages" 
                    :index="index" 
                    :value="message" 
                    :key="index"
                ></component-message>
            </component-header>
        </component>
            
    </div>


    <!-- Загрузка Vue компонентов -->
    <script>
        {!! $components !!}
        Vue.component('compact-picker', VueColor.Chrome);
    </script>


    <!-- Загрузка Vue данных -->
    <script>
        Vue.mixin({
            data: function () {return {
                api_key: "{!! $key !!}", // Ключ доступа
                csrf_token: "{!! csrf_token() !!}", // Токен защиты форм
            }},
            
            methods: {
                getMethod: function(method, params, callback) {
                    var t = this;
                    
                    // https://kholobok.biz/api/getVideo?account_key=
                    axios.get('https://kholobok.biz/api/' + method + '?account_key=' + this.api_key , {
                        params: params
                    }).then(function(response){
                        //console.log(response.data);
                        callback(response.data);
                        if(typeof response.data.messages != "undefined" && response.data.messages.length != 0){
                            t.$root.$emit('getMessage', response.data.messages);
                        }
                    }).catch(function (error){
                        console.log(error);
                    });
                },
                
                postMethod: function(method, params, callback) {
                    var t = this;

                    axios.post('https://kholobok.biz/api/' + method + '?account_key=' + this.api_key , params).then(function(response){
                        //console.log(response.data);
                        callback(response.data);
                        if(typeof response.data.messages != "undefined" && response.data.messages.length != 0){
                            t.$root.$emit('getMessage', response.data.messages);
                        }

                    }).catch(function (error){
                        console.log(error);
                    });
                }
                
            }
        });



        var vue = new Vue({
            el:"#content",
            created: function () {
                console.log('200');

                // this.getNewTiket();

                this.getMethod('tikets.get', {
                    close: this.pageTikets
                },(function(response){
                    // console.log(response.response);
                    this.mtiketsMessages = response.response;
                }).bind(this));

            },
            data: {

                menu: {!! $menu !!}, // Данные меню
                header: {!! $header !!}, // Данные хедара
                messages: {!! $messages !!}, // Сообщения
                component: {!! $component !!}, // Компонент
                nameComponent: "{!! $nameComponent !!}", // Название используемого компонента

                mtiketsMessages: null
            },
            methods: {

                getNewTiket: function(){
                    setInterval( (function(){
                        this.getMethod('tikets.get', {
                            close: this.pageTikets
                        },(function(response){
                            
                            console.log(JSON.stringify(this.mtiketsMessages) === JSON.stringify(response.response));

                        }).bind(this));
                    }).bind(this), 500);
                }
            },
            computed:{
                
            },
            mounted: function () {
                this.$nextTick(function () {

                    // событие
                    // добавление сообщения в хедар
                    this.$root.$on('getMessage', function(data){
                        for(let i=0; i<data.length ;i++){
                            this.messages.push(data[i]);
                        }
                    });

                    // удаление сообщения
                    this.$root.$on('deleteMesage', function(index){
                        this.messages.splice(index, 1);
                    });





                    // односторонний уневерсальный запрос
                    this.$root.$on('updateUrl', function(url){

                        axios.get('https://kholobok.biz/' + url, {
                            params: {
                                account_key: this.api_key
                            }
                        }).then(function(response){
                            console.log(response.data);
                        }).catch(function (error){
                            console.log(error);
                        });
                    });


                })
            }
        });
    </script>
    

</body>
</html>