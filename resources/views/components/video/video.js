Vue.component('component-video', {
    template: '{{html}}',
    props: ['data'],
    data: function(){return{

        videos: null,
        count: null,
        countries: null,
        genres: null,

        search: '',
        panelGenres: false,
        panelCountries: false,
        panelYears: false,
        range: [
            { name:'Old', to:0, do:1950},
            { name:'50+', to:1950, do:1960},
            { name:'60+', to:1960, do:1970},
            { name:'70+', to:1970, do:1980},
            { name:'80+', to:1980, do:1990},
            { name:'90+', to:1990, do:2000},
            { name: '2000', to:2000, do:2001}
        ],
        rangeId: 0,

        updateOffset: this.data.videodb.count_vdb,
        steps: 2,
        next: null,
        preolaoded: false,

        updateUpOffset: 0,
        preolaodedUp: false,
        stepsUp: 0,

        paginations: [],
        paginCount: 20,
        page: 1,

        modal: false,
        modalPage: {
            film: false,
            add: false,
            info: false,
            tiket: false
        },
        filmId: 0,
        filmIndex: null,

        updateFilmData: null,
        updateFilmFlag: false,

        tiket: {
            text: '',
            title: '',
            data: {}
        }

    }},

    created: function () {
        console.log('start video component');
        // хеш для страницы
        if( /page.*?(\d+)/.exec( window.location.hash ) != null){
            this.page = /page.*?(\d+)/.exec( window.location.hash )[1];
        }
        // массив для фильтра года
        for (let i = 2001; i <= new Date().getFullYear(); i++) {
            this.range.push({name:i, to:i, do:i, check: false});
        }
        this.range.push({name:'Все', to:0, do:new Date().getFullYear(), check: true});
        this.range = this.range.reverse();
        // стартовая выборка списка фильмов
        this.videosGet(this.page);
    },

    watch: {
        updateOffset: function () {
            if(this.progress == 100){
                this.preolaoded = false;
                this.data.videodb.count_vdb = this.updateOffset;
                this.videosGet(this.page);
            }
        },
        updateUpOffset: function () {
            if(this.progressUp == 100){
                // this.preolaodedUp = false;
                this.videosGet(this.page);
            }
        }
    },

    computed: {
        progress: function () {
            var rezult = (this.updateOffset - this.data.videodb.count_vdb) / (50 * this.steps) * 100;
            return Math.round(rezult);
        },
        progressUp: function () {
            var rezult = 100 / (this.data.videodb.count_vdb / this.updateUpOffset);
            return Math.floor(rezult);
        },
        offsetPage: function () {
            return this.paginCount * (this.page - 1);
        },
    },

    mounted: function () {
        this.$nextTick(function () {})
    },

    methods: {

        // Пердпросмотр фильма
        showFilm: function(id){
            this.filmId = id;
            this.modal = true;
            this.modalPage.film = true;
        },

        // Информация о фильме
        showInfo: function(index){
            this.filmIndex = index;
            this.modal = true;
            this.modalPage.info = true;
        },

        // Добавление фильма
        addFilm: function(){
            this.modal = true;
            this.modalPage.add = true;
        },

        // Редактирование фильма
        updateFilm: function(){
            this.updateFilmFlag = true; // Показать интерфейс
            this.updateFilmData = { ...this.videos[this.filmIndex] }; // Получить данные
            let lock = { // Новый объект для блокировки
                'RU':false,
                'UA':false,
                'SNG':false,
                'FULL':false
            };
            if(this.updateFilmData.lock != null){ // Обновление объекта на основе данных
                this.updateFilmData.lock.split(',').map(function(el){ lock[el] = true; });
            }
            this.updateFilmData.lock = lock; // Замена строки на объект
        },

        // Сохранить изменения
        updateFilmSave: function(){
            this.updateFilmFlag = false;

            let lock = [];
            for(let key in this.updateFilmData.lock){
                if(this.updateFilmData.lock[key]){
                    lock.push(key); 
                }
            }
            this.updateFilmData.lock = lock.join(',');

            console.log({ element: JSON.stringify(this.updateFilmData)});
            this.postMethod('updateVideo', { 
                element: JSON.stringify(this.updateFilmData)
            }, function(response){
                console.log(response);
            });
        },


        
        // Закрыть все модалки
        closeModal: function(){
            this.filmId = 0;
            this.modal = false;
            this.filmIndex = null;
            
            for (const key in this.modalPage) {
                this.modalPage[key] = false;
            }

            this.updateFilmFlag = false;
            this.updateFilmData = null;
        },

        // Скопировать адрес
        copyAdress: function(id){
            console.log(navigator);
            navigator.clipboard.writeText('<iframe src="https://kholobok.biz/show/' + id + '" frameborder="0" width="610" height="370" allowfullscreen></iframe>');
            this.$root.$emit('getMessage', [{ tupe: "info", message: "Элемент скопирован" }] );
        },

        // Очистить фильтр
        filterClear: function(){
            this.search = '';
            if( this.countries != null){
                this.countries = this.modificationResponse(this.countries);
            }
            if( this.genres != null){
                this.genres = this.modificationResponse(this.genres);
            }
            this.videosGet();
        },



        /*
        // Движение мыши на ползунке деапазона
        rangeMoveMous: function(e){
            if(this.range.flagMove){
                if( (this.range.cursorPosition - e.clientX) > 0 ){
                    // right
                    if(this.range.positionMin < (this.range.width - 10) && this.range.activSpoter == 'Min'){
                        this.range.positionMin += (this.range.cursorPosition - e.clientX);
                    }
                    if(this.range.positionMax < this.range.positionMin - 20 && this.range.activSpoter == 'Max'){
                        this.range.positionMax += (this.range.cursorPosition - e.clientX);
                    }
                }else if( (this.range.cursorPosition - e.clientX) < 0 ){
                    // left
                    if(this.range.positionMin > this.range.positionMax + 20 && this.range.activSpoter == 'Min'){
                        this.range.positionMin += (this.range.cursorPosition - e.clientX);
                    }
                    if(this.range.positionMax > -6 && this.range.activSpoter == 'Max'){
                        this.range.positionMax += (this.range.cursorPosition - e.clientX);
                    }
                }
                this.range.cursorPosition = e.clientX;
            }
        },
        rangeDownMous: function(e, spoter){
            this.range.activSpoter = spoter;
            this.range.flagMove = true;
            this.range.cursorPosition = e.clientX;
        },
        */



        clickBtnUpdateVDB: function(){
            this.preolaodedUp = true;
            this.updateAddVideoDB();
        },


        updateAddVideoDB: function(){
            // Проверка рекурсивного случая
            if(this.updateUpOffset < this.data.videodb.count_vdb){
                // Запрос к api
                this.postMethod('addVideoDB', {
                    offset: this.updateUpOffset
                }, (function(response){
                    console.log(response);

                    this.updateUpOffset = this.updateUpOffset + 50;
                    this.stepsUp = this.stepsUp + response.data.steps;
                    this.updateAddVideoDB(); // Рекурсивный вызов
                }).bind(this));
            }
        },


        clickBtnVDB: function(){
            this.preolaoded = true;
            this.addVideoDB(this.steps);
        },

        // Рекурсивная загрузка данных
        addVideoDB: function(steps){
            if(steps != 0){ // Проверка рекурсивного случая
                steps = steps - 1;
                
                // Запрос к api
                console.log(this.updateOffset);
                this.postMethod('addVideoDB', {
                    offset: this.updateOffset
                }, (function(response){
                    console.log(response);
                    this.updateOffset = this.updateOffset + 50;
                    this.addVideoDB(steps); // Рекурсивный вызов
                }).bind(this));
            }
        },

        // Загрузка списка видео
        videosGet: function(page = 1){
            // var t = this;


            this.page = page;
            window.location.hash = 'page'+ page;

            // Параметры запроса
            let params = {
                // account_key: this.data.api_key,
                limit: this.paginCount,
                offset: this.offsetPage,
                search: this.search
            };
            
            // Добавить строку стран
            if( this.countries != null && this.countries.filter(function(e){return e.show}).length != 0){
                params['countries'] = this.countries.reduce(function(acc,e){ if(e.show){acc.push(e.name)} return acc;}, []).join(',');
            }
            // Добавить строку жанров
            if( this.genres != null && this.genres.filter(function(e){return e.show}).length != 0){
                params['genres'] = this.genres.reduce(function(acc,e){ if(e.show){acc.push(e.name)} return acc;}, []).join(',');
            }
            // Добавить года
            if(this.range.length > 7){
                params['years'] = this.range[this.rangeId].to + ',' + this.range[this.rangeId].do;
            }
            
            this.postMethod('getVideo', params, (function(response){

                console.log(response);
                this.count = response.data.count;
                this.videos = response.data.items;
                
                this.getPaginations();
                this.$refs.articles__scrol.scrollTop = 0;

                if(
                    this.countries == null && 
                    this.genres == null && 
                    typeof response.data.countries != "undefined" && 
                    typeof response.data.genres != "undefined"
                ){
                    this.countries = this.modificationResponse(response.data.countries);
                    this.genres = this.modificationResponse(response.data.genres);
                }
            }).bind(this));
        },


        modificationResponse: function(object){
            // object.reduce()

            return object.map(function(element){ 
                return { id: element.id, name: element.name, show: false };
            });
        },



        openTiket: function(id, name){
            this.modal = true;
            this.modalPage.tiket = true;

            this.tiket.title = `Обновление видеофаила "${name}"`;
            this.tiket.data.idFilm = id;
        },
        sendTiket: function(){
            this.postMethod('tikets.add', {
                tupe: 'film',
                title: this.tiket.title,
                message: this.tiket.text,
                data: JSON.stringify( this.tiket.data )
            }, (function(response){
                this.closeModal();
            }).bind(this));
        },




        getPaginations: function(){

            var rez = Math.ceil( this.count / this.paginCount);
            this.paginations = [];



            function leftPagin(t){
                if(t.page == 1){ t.paginations.push({content: '<', page: 0, look: true}); 
                }else{ t.paginations.push({content: '<', page: +t.page - 1, look: false}); }
            }
            function rightPagin(t){
                if(t.page == rez){ t.paginations.push({content: '>', page: 0, look: true});
                }else{ t.paginations.push({content: '>', page: +t.page + 1,look: false}); }
            }

            if(rez < 8){

                leftPagin(this);

                for (let i = 1; i <= rez; i++) {
                    if(i == this.page){
                       this.paginations.push({content: i, page: 0, look: true}); 
                    }else{
                        this.paginations.push({content: i, page: i, look: false}); 
                    }
                }

                rightPagin(this);

            }else if(rez > 7 && rez <= 20){

                if( (this.page > 0 && this.page < 3) || (this.page > (rez - 2) && this.page <= rez) ){
                    leftPagin(this);

                    for (let i = 1; i < 4; i++) {
                        if(i == this.page){
                           this.paginations.push({content: i, page: 0, look: true}); 
                        }else{
                            this.paginations.push({content: i, page: i, look: false}); 
                        }
                    }

                    this.paginations.push({content: '...', page: 0, look: true}); 

                    for (let i = rez - 2; i <= rez; i++) {
                        
                        if(i == this.page){
                            this.paginations.push({content: i, page: 0, look: true}); 
                        }else{
                            this.paginations.push({content: i, page: i, look: false}); 
                        }
                    }

                    rightPagin(this);

                }else if( this.page > 2 && this.page < (rez - 1) ){

                    leftPagin(this);
                    this.paginations.push({content: 1, page: 1, look: false});
                    this.paginations.push({content: '...', page: 0, look: true});
                    this.paginations.push({content: +this.page - 1, page: +this.page - 1, look: false});
                    this.paginations.push({content: this.page, page: this.page, look: true});
                    this.paginations.push({content: +this.page + 1, page: +this.page + 1, look: false});
                    this.paginations.push({content: '...', page: 0, look: true});
                    this.paginations.push({content: rez, page: rez, look: false});
                    rightPagin(this);

                }

            }else if(rez > 20){

                console.log('zdes');

                if( (this.page > 0 && this.page < 6) || (this.page > (rez - 5) && this.page <= rez) ){

                    leftPagin(this);

                    for (let i = 1; i < 6; i++) {
                        if(i == this.page){
                           this.paginations.push({content: i, page: 0, look: true}); 
                        }else{
                            this.paginations.push({content: i, page: i, look: false}); 
                        }
                    }

                    this.paginations.push({content: '...', page: 0, look: true}); 

                    let rezs = Math.ceil(rez / 2) + 2;
                    for (let i = rezs - 5; i <= rezs; i++) {
                        if(i == this.page){
                            this.paginations.push({content: i, page: 0, look: true}); 
                        }else{
                            this.paginations.push({content: i, page: i, look: false}); 
                        }
                    }

                    this.paginations.push({content: '...', page: 0, look: true}); 

                    for (let i = rez - 5; i <= rez; i++) {
                        if(i == this.page){
                            this.paginations.push({content: i, page: 0, look: true}); 
                        }else{
                            this.paginations.push({content: i, page: i, look: false}); 
                        }
                    }

                    rightPagin(this);

                }else if( this.page > 5 && this.page < (rez - 4) ){

                    leftPagin(this);

                    this.paginations.push({content: 1, page: 1, look: false});
                    this.paginations.push({content: 2, page: 2, look: false});
                    this.paginations.push({content: 3, page: 3, look: false});

                    this.paginations.push({content: '...', page: 0, look: true});

                    this.paginations.push({content: +this.page - 4, page: +this.page - 4, look: false});
                    this.paginations.push({content: +this.page - 3, page: +this.page - 3, look: false});
                    this.paginations.push({content: +this.page - 2, page: +this.page - 2, look: false});
                    this.paginations.push({content: +this.page - 1, page: +this.page - 1, look: false});

                    this.paginations.push({content: this.page, page: this.page, look: true});

                    this.paginations.push({content: +this.page + 1, page: +this.page + 1, look: false});
                    this.paginations.push({content: +this.page + 2, page: +this.page + 2, look: false});
                    this.paginations.push({content: +this.page + 3, page: +this.page + 3, look: false});
                    this.paginations.push({content: +this.page + 4, page: +this.page + 4, look: false});

                    this.paginations.push({content: '...', page: 0, look: true});

                    this.paginations.push({content: rez-2, page: rez-2, look: false});
                    this.paginations.push({content: rez-1, page: rez-1, look: false});
                    this.paginations.push({content: rez, page: rez, look: false});

                    rightPagin(this);

                }

            }

        }
        
    }



});