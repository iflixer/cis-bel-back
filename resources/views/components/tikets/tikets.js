


const statusTiket = {
    data: function(){return {}},
    props: ['status'],
    template: '\
    <div class="tikets-s__stat">\
        <span v-if="status == 1" class="tikets-s__item-stat tikets-s__item-stat--blue">Открыт</span>\
        <span v-if="status == 2" class="tikets-s__item-stat tikets-s__item-stat--yellow">Рассмотрение</span>\
        <span v-if="status == 3" class="tikets-s__item-stat tikets-s__item-stat--red">Отклонен</span>\
        <span v-if="status == 4" class="tikets-s__item-stat">Закрыт</span>\
    </div>',
    methods: {
    }
}

const tupeTiket = {
    data: function(){return {}},
    props: ['tupe'],
    template: '\
    <div class="tikets-s__tupe">\
        <span v-if="tupe == `tiket`" class="tikets-s__item-tupe">Обращение</span>\
        <span v-if="tupe == `domain`" class="tikets-s__item-tupe">Домен</span>\
        <span v-if="tupe == `film`" class="tikets-s__item-tupe">Фильм</span>\
        <span v-if="tupe == `pay`" class="tikets-s__item-tupe">Выплаты</span>\
    </div>',
    methods: {
    }
}





Vue.component('component-tikets', {
    template: '{{html}}',
    props: ['data'],
    components:{
        'status-tiket': statusTiket,
        'tupe-tiket': tupeTiket
    },
    data: function(){return{

        modal: false,
        modalPage: {
            film: false,
            tiket: false
        },
        titleTiket: "",
        textTiket: "",

        tiketsList: null,
        tiketShow: false,
        tiketMessages: null,
        tiketIndex: null,

        textMessage: '',

        pageTikets: false

    }},

    created: function () {
        this.init();
    },

    methods: {

        init: function(){
            this.getMethod('tikets.get', {
                close: this.pageTikets
            },(function(response){
                console.log(response);
                this.tiketsList = response.data;
            }).bind(this));
        },

        getTikets: function(tupe){
            this.getMethod('tikets.get', {
                close: this.pageTikets,
                tupe: tupe
            },(function(response){
                // console.log(response);
                this.tiketsList = response.data;
            }).bind(this));
        },

        openModal: function(modal){
            this.modal = true;
            this.modalPage[modal] = true;
        },
        closeModal: function(){
            this.modal = false;
            for (const key in this.modalPage) {
                this.modalPage[key] = false;
            }
        },


        openTiket: function(id, index){
            this.tiketShow = true;
            this.tiketIndex = index;
            this.getMessageList(id);
        },

        getMessageList: function(id){
            this.tiketMessages = null;
            this.getMethod('tikets.getId', {
                id: id
            },(function(response){
                // console.log(response);
                this.tiketMessages = response.data;
                
                this.tiketMessages.map((function(el){ 
                    el.created_at = this.getDataS(el.created_at);
                    return el;
                }).bind(this));
            }).bind(this));
        },

        closeTiket: function(){
            this.tiketShow = false;
        },

        openPageTikets: function(open){
            this.pageTikets = open;
            this.init();
        },

        sendTiket: function(){
            this.postMethod('tikets.add', {
                tupe: 'tiket',
                title: this.titleTiket,
                message: this.textTiket
            }, (function(response){
                //console.log(response);
                this.closeModal();
                this.init();
            }).bind(this));
        },

        sendTiketVideo: function(){
            this.postMethod('tikets.add', {
                tupe: 'film',
                title: 'Заказ фильма: ' + this.titleTiket,
                message: this.textTiket
            }, (function(response){
                //console.log(response);
                this.closeModal();
                this.init();
            }).bind(this));
        },

        sendMessage: function(){
            const id = this.tiketsList[this.tiketIndex].id;

            this.postMethod('tikets.addMessage', {
                id: id,
                message: this.textMessage
            }, (function(response){
                // console.log(response);
                this.getMessageList(id);
                this.textMessage = "";
            }).bind(this));
        },


        statusNotDomain: function(){
            this.statusPutTiket(3); 
        },
        statusYesDomain: function(){

            let id = JSON.parse( this.tiketsList[this.tiketIndex].data ).id;
            
            this.statusPutTiket(4); 

            this.postMethod('tikets.addMessage', {
                id: this.tiketsList[this.tiketIndex].id,
                message: "Ваш домен принят."
            }, (function(response){
                //console.log(response);
            }).bind(this));


            this.postMethod('domains.complit', {
                id: id
            }, (function(response){
                //console.log(response);
            }).bind(this));

        },
        

        statusCloseTiket: function(){
            this.statusPutTiket(4); 
        },
        statusRassmotrTiket: function(){
            this.statusPutTiket(2); 
        },

        statusPutTiket: function(nom){
            const id = this.tiketsList[this.tiketIndex].id;
            this.getMethod('tikets.statPut', {
                id: id,
                status: nom
            },(function(response){
                // console.log(response);
                this.init();
            }).bind(this));
        },

        getDataS: function(string){
            let data = new Date(Date.parse(string));
            let stringData = data.getFullYear();

            stringData += '.' + (data.getMonth() + 1);
            stringData += '.' + (data.getDate());
            stringData += ' ' + (data.getHours());
            stringData += ':' + (data.getMinutes());

            return stringData;
        }
        
    }
});